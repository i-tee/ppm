// resources/js/utils/statistics.js
// Чистые функции для экрана «Статистика» (этап 1.5) - без Vue/Pinia, чтобы
// их можно было проверить отдельно от компонента.

const DAY_MS = 24 * 60 * 60 * 1000;

// Joomla отдаёт order_date строкой 'YYYY-MM-DD HH:MM:SS' (без 'T' - часть
// движков парсит такую строку неправильно или как UTC). У заказов нового
// сайта (source: 'backend') формат даты на момент этапа 1.5 не сверен на
// реальных данных (см. docs/cabinet-map.md §4а) - парсим оба варианта.
export function parseOrderDate(value) {
  if (!value) return null;
  if (value instanceof Date) return isNaN(value.getTime()) ? null : value;
  const str = String(value).trim();
  const normalized = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(str) ? str.replace(' ', 'T') : str;
  const date = new Date(normalized);
  return isNaN(date.getTime()) ? null : date;
}

function startOfDay(date) {
  const d = new Date(date);
  d.setHours(0, 0, 0, 0);
  return d;
}

function endOfDay(date) {
  const d = new Date(date);
  d.setHours(23, 59, 59, 999);
  return d;
}

function toLocalYmd(date) {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

export const PERIOD_PRESETS = ['7d', '30d', '3m', 'this_month', 'last_month', 'year', 'all', 'custom'];

// Границы периода включительно. `now` - для тестируемости.
export function getPeriodRange(preset, { now = new Date(), customStart = null, customEnd = null } = {}) {
  const today = endOfDay(now);

  switch (preset) {
    case '7d':
      return { start: startOfDay(new Date(now.getTime() - 6 * DAY_MS)), end: today };
    case '30d':
      return { start: startOfDay(new Date(now.getTime() - 29 * DAY_MS)), end: today };
    case '3m': {
      const start = new Date(now);
      start.setMonth(start.getMonth() - 3);
      return { start: startOfDay(start), end: today };
    }
    case 'this_month':
      return { start: startOfDay(new Date(now.getFullYear(), now.getMonth(), 1)), end: today };
    case 'last_month': {
      const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
      const end = new Date(now.getFullYear(), now.getMonth(), 0);
      return { start: startOfDay(start), end: endOfDay(end) };
    }
    case 'year': {
      const start = new Date(now);
      start.setFullYear(start.getFullYear() - 1);
      return { start: startOfDay(start), end: today };
    }
    case 'all':
      return { start: null, end: null };
    case 'custom':
      return {
        start: customStart ? startOfDay(new Date(customStart)) : null,
        end: customEnd ? endOfDay(new Date(customEnd)) : today,
      };
    default:
      return { start: startOfDay(new Date(now.getTime() - 29 * DAY_MS)), end: today };
  }
}

// Предыдущий период той же длины, сразу перед текущим - для сравнения ↑/↓.
// Для «Всё время» (start === null) сравнение не имеет смысла - null.
export function getPreviousPeriodRange({ start, end }) {
  if (!start || !end) return null;
  const length = end.getTime() - start.getTime();
  return {
    start: new Date(start.getTime() - length - 1),
    end: new Date(start.getTime() - 1),
  };
}

export function filterOrdersByPeriod(orders, { start, end } = {}) {
  // «Всё время» (обе границы null) - без фильтра по дате вообще, чтобы точно
  // совпадать с сервером (там orders_count считает все заказы, включая с
  // неразбираемой/пустой датой). Для остальных периодов заказ без даты
  // однозначно не попадает в диапазон.
  if (!start && !end) return orders;
  return orders.filter((order) => {
    const date = parseOrderDate(order.order_date);
    if (!date) return false;
    if (start && date < start) return false;
    if (end && date > end) return false;
    return true;
  });
}

export function filterOrdersByCoupon(orders, couponId) {
  if (!couponId || couponId === 'all') return orders;
  return orders.filter((order) => String(order.coupon_id) === String(couponId));
}

// Заказы, за которые партнёру начислен кешбек (cashback > 0) - так же, как
// считает сервер в JoomlaCoupon::credits() (total_accruals).
function earningOrders(orders) {
  return orders.filter((order) => Number(order.cashback || 0) > 0);
}

// ordersCount - все заказы периода (включая cashback <= 0, сторно/возвраты),
// как orders_count на сервере. accrued - сумма cashback > 0, как total_accruals.
// avgOrder - средний чек среди заказов, за которые начислен кешбек.
export function computeTotals(orders) {
  const ordersCount = orders.length;
  const earning = earningOrders(orders);
  const accrued = earning.reduce((sum, o) => sum + Number(o.cashback || 0), 0);
  const orderSum = earning.reduce((sum, o) => sum + Number(o.order_total || 0), 0);
  const avgOrder = earning.length ? orderSum / earning.length : 0;
  return { accrued, ordersCount, avgOrder };
}

export function computePayout(payoutRequests, { start, end } = {}) {
  return (payoutRequests || [])
    .filter((r) => Number(r.status) === 20)
    .filter((r) => {
      if (!start && !end) return true;
      const date = parseOrderDate(r.created_at);
      if (!date) return false;
      if (start && date < start) return false;
      if (end && date > end) return false;
      return true;
    })
    .reduce((sum, r) => sum + Number(r.withdrawal_amount || 0), 0);
}

// Тип сравнения с прошлым периодом для UI:
// - 'none' - сравнивать не с чем (нет предыдущего периода, «Всё время»);
// - 'new' - в прошлом периоде было 0, сейчас больше 0 - показываем «новое»
//   без процента (делить на 0 нечем);
// - 'neutral' - оба периода 0, либо процент округляется к 0.0 - «–»,
//   без стрелки;
// - 'value' - обычный процент разницы.
export function describeChange(current, previous) {
  if (previous === null || previous === undefined) return { kind: 'none' };
  if (previous === 0) {
    return current === 0 ? { kind: 'neutral' } : { kind: 'new' };
  }
  const percent = ((current - previous) / previous) * 100;
  if (Math.round(percent * 10) / 10 === 0) return { kind: 'neutral' };
  return { kind: 'value', percent };
}

// Для «Всё время» period.start/end - null (нет фиксированной границы), но
// графику нужен реальный диапазон - берём минимальную/максимальную дату
// среди самих заказов. null, если дат нет вообще (заказов нет - для этого
// есть отдельная заглушка «За этот период начислений нет»).
export function getOrdersDateBounds(orders) {
  let start = null;
  let end = null;
  for (const order of orders) {
    const date = parseOrderDate(order.order_date);
    if (!date) continue;
    if (!start || date < start) start = date;
    if (!end || date > end) end = date;
  }
  if (!start || !end) return null;
  return { start: startOfDay(start), end: endOfDay(end) };
}

export const CHART_STEPS = ['day', 'week', 'month'];

// Шаг графика по умолчанию по длине периода.
export function autoChartStep({ start, end } = {}) {
  if (!start || !end) return 'month';
  const days = (end.getTime() - start.getTime()) / DAY_MS;
  if (days <= 45) return 'day';
  if (days <= 420) return 'week';
  return 'month';
}

function bucketKey(date, step) {
  if (step === 'month') {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
  }
  if (step === 'week') {
    const d = startOfDay(date);
    const day = (d.getDay() + 6) % 7; // понедельник = 0
    d.setDate(d.getDate() - day);
    return toLocalYmd(d);
  }
  return toLocalYmd(date);
}

function bucketLabel(key, step) {
  if (step === 'month') {
    const [year, month] = key.split('-');
    return `${month}.${year}`;
  }
  const [year, month, day] = key.split('-');
  return `${day}.${month}.${year.slice(2)}`;
}

function nextBucketStart(date, step) {
  const d = new Date(date);
  if (step === 'day') d.setDate(d.getDate() + 1);
  else if (step === 'week') d.setDate(d.getDate() + 7);
  else d.setMonth(d.getMonth() + 1);
  return d;
}

function firstBucketStart(start, step) {
  if (step !== 'week') return startOfDay(start);
  const d = startOfDay(start);
  const day = (d.getDay() + 6) % 7;
  d.setDate(d.getDate() - day);
  return d;
}

// Столбцы графика: начисления (cashback > 0) + число заказов по бакетам,
// включая пустые (нулём - чтобы были видны провалы), от start до end.
export function buildChartBuckets(orders, { start, end } = {}, step) {
  if (!start || !end) return { labels: [], accruals: [], ordersCount: [] };

  const sums = new Map();
  const counts = new Map();

  for (const order of orders) {
    const date = parseOrderDate(order.order_date);
    if (!date) continue;
    const key = bucketKey(date, step);
    counts.set(key, (counts.get(key) || 0) + 1);
    const cashback = Number(order.cashback || 0);
    if (cashback > 0) {
      sums.set(key, (sums.get(key) || 0) + cashback);
    }
  }

  const labels = [];
  const accruals = [];
  const ordersCount = [];
  const seen = new Set();

  let cursor = firstBucketStart(start, step);
  while (cursor <= end) {
    const key = bucketKey(cursor, step);
    if (!seen.has(key)) {
      seen.add(key);
      labels.push(bucketLabel(key, step));
      accruals.push(sums.get(key) || 0);
      ordersCount.push(counts.get(key) || 0);
    }
    cursor = nextBucketStart(cursor, step);
  }

  return { labels, accruals, ordersCount };
}
