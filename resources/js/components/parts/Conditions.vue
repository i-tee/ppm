<template>
  <div class="conditions">
    <header class="text-center mb-8">
      <h1>Партнёрская программа "Агент"</h1>
      <p class="subtitle">
        Зарабатывайте, рекомендуя наши товары. Создавайте промокоды, делитесь ссылками и получайте вознаграждение за каждую успешную покупку.
      </p>
    </header>

    <div class="content">
      <section class="mb-8">
        <h2>Общие положения</h2>
        <p>
          Партнёрская программа "Агент" — это возможность для наших партнёров получать вознаграждение за привлечение новых клиентов. 
          Зарегистрировавшись в программе, вы получаете доступ к инструментам для создания промокодов и отслеживания статистики.
        </p>
        <p>
          Участие в программе подразумевает полное согласие с настоящими условиями.
        </p>
      </section>

      <section class="mb-8">
        <h2>Как работает программа</h2>

        <h3>1. Регистрация и принятие оферты</h3>
        <p>
          Для участия в программе необходимо зарегистрироваться в Личном кабинете, подтвердить email и принять публичную оферту. 
          После этого вы получаете полный доступ ко всем инструментам партнёрской программы.
        </p>

        <h3>2. Создание промокодов и ссылок</h3>
        <p>
          В Личном кабинете вы можете создавать неограниченное количество персональных промокодов. 
          Для каждого промокода система автоматически генерирует уникальную партнёрскую ссылку вида:
        </p>
        <code>{{ couponBaseUrl }}UNIQUE_CODE</code>
        <p>
          Эта ссылка является альтернативным способом активации промокода.
        </p>

        <h3>3. Настройка промокода</h3>
        <p>При создании промокода вы указываете:</p>
        <ul>
          <li><strong>Общую скидку (X%)</strong> — максимальный лимит устанавливается компанией ({{ maxDiscount }}%).</li>
          <li>
            <strong>Распределение скидки</strong>:
            <ul>
              <li><strong>Клиентская доля (Y%)</strong> — скидка для конечного покупателя</li>
              <li><strong>Партнёрская доля (Z%)</strong> — ваше вознаграждение</li>
            </ul>
          </li>
        </ul>
        <p>Формула распределения: <strong>X% = Y% + Z%</strong></p>
        <div class="example">
          <p><strong>Пример:</strong> {{ discountExample }}</p>
        </div>

        <h3>4. Использование промокодов</h3>
        <p>Клиент может:</p>
        <ul>
          <li>Ввести промокод вручную при оформлении заказа на сайте</li>
          <li>Или перейти по вашей партнёрской ссылке — в этом случае промокод применится автоматически</li>
        </ul>
        <p>В обоих случаях клиент получает установленную вами скидку (Y%).</p>

        <h3>5. Начисление бонусов</h3>
        <p>
          После успешной оплаты и обработки заказа, ваша доля (Z%) зачисляется на бонусный счёт в Личном кабинете — 
          независимо от способа активации промокода (вручную или по ссылке).
        </p>

        <h3>6. Бонусные промокоды</h3>
        <p>
          Вы можете создавать специальные промокоды "на сумму", которые будут списывать средства с вашего бонусного счёта. Особенности:
        </p>
        <ul>
          <li>Номинал такого промокода для клиента должен быть на {{ bonusPercentage }}% ВЫШЕ, чем сумма, списанная с вашего счёта</li>
          <li>Это стимулирует партнёров использовать бонусы для продвижения, а не выводить их</li>
        </ul>
        <div class="example">
          <p><strong>Пример:</strong> {{ bonusExample }}</p>
        </div>

        <h3>7. Вывод средств</h3>
        <p>{{ payoutDescription }}</p>
        
        <table>
          <thead>
            <tr>
              <th>Статус партнёра</th>
              <th>Комиссия при выводе</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="partnerType in partnerTypes" :key="partnerType.id">
              <td>{{ getPartnerTypeName(partnerType.name) }}</td>
              <td>
                <span v-if="partnerType.tax > 0">{{ partnerType.tax }}% от суммы вывода</span>
                <span v-else>0% комиссии</span>
              </td>
            </tr>
          </tbody>
        </table>

        <p><strong>Методы выплаты:</strong></p>
        <ul>
          <li>Физлицам и самозанятым — на банковскую карту</li>
          <li>ИП и ООО — на расчётный счёт по реквизитам</li>
        </ul>

        <h3>8. Аналитика</h3>
        <p>
          В Личном кабинете вы видите детальную статистику по каждому промокоду:
        </p>
        <ul>
          <li>Количество использований</li>
          <li>Информация о заказе с промокодом</li>
          <li>Общая сумма начисленных бонусов</li>
        </ul>
      </section>

      <section class="mb-8">
        <h2>Ограничения и правила</h2>

        <h3>Финансовые лимиты</h3>
        <ul>
          <li>Максимальная общая скидка (X%) устанавливается компанией и составляет {{ maxDiscount }}%</li>
          <li>Минимальная сумма для вывода средств: {{ minPayout }} ₽</li>
          <li>Коэффициент для бонусных промокодов: {{ bonusKoef }} (номинал промокода на {{ bonusPercentage }}% выше суммы списания)</li>
        </ul>

        <h3>Юридические аспекты</h3>
        <ul>
          <li>Участие в программе регулируется публичной офертой, принимаемой при регистрации</li>
          <li>Компания оставляет за собой право изменять условия программы и лимиты</li>
          <li>Нарушение правил программы может привести к блокировке аккаунта</li>
        </ul>

        <h3>Налоговые обязательства</h3>
        <ul>
          <li v-for="partnerType in partnerTypes" :key="'tax-' + partnerType.id">
            <strong>{{ getPartnerTypeName(partnerType.name) }}:</strong> 
            <span v-if="partnerType.tax > 0"> комиссия {{ partnerType.tax }}% при выводе</span>
            <span v-else> комиссия не взимается, налоги уплачиваются самостоятельно</span>
          </li>
        </ul>
      </section>

      <section class="mb-8">
        <h2>Контакты и поддержка</h2>
        <p>
          Если у вас возникли вопросы по работе партнёрской программы, обращайтесь в службу поддержки:
        </p>
        <ul>
          <li>Email: partners@avicenna.com.ru</li>
          <li>Часы работы: пн-пт с 9:00 до 18:00 по московскому времени</li>
        </ul>
      </section>
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  apiData: {
    type: Object,
    required: true,
  },
});

// Основные данные
const agentData = computed(() => {
  return props.apiData.cooperation_types.find(type => type.name === 'agent') || {};
});

const partnerTypes = computed(() => {
  return props.apiData.partner_types || [];
});

// Вычисляемые значения
const maxDiscount = computed(() => agentData.value.max_discount || 20);
const minPayout = computed(() => agentData.value.min_payout || 500);
const bonusKoef = computed(() => agentData.value.bonus_koef || 1.2);
const bonusPercentage = computed(() => Math.round((bonusKoef.value - 1) * 100));
const couponBaseUrl = computed(() => agentData.value.coupon_base_url || 'https://avicenna.com.ru/buy?_prcp=');

// Длинные текстовые блоки
const discountExample = computed(() => {
  const productPrice = 1000;
  const totalDiscount = productPrice * maxDiscount.value / 100;
  const clientDiscountPercent = Math.floor(maxDiscount.value * 0.7);
  const clientDiscount = productPrice * clientDiscountPercent / 100;
  const partnerDiscountPercent = maxDiscount.value - clientDiscountPercent;
  const partnerDiscount = productPrice * partnerDiscountPercent / 100;
  
  return `Товар стоит ${productPrice} ₽. Вы создаёте промокод с общей скидкой ${maxDiscount.value}% (${totalDiscount} ₽). Вы решаете дать клиенту скидку ${clientDiscountPercent}% (${clientDiscount} ₽), а себе зачислить ${partnerDiscountPercent}% (${partnerDiscount} ₽).`;
});

const bonusExample = computed(() => {
  const baseAmount = 1000;
  const bonusAmount = Math.round(baseAmount * bonusKoef.value);
  return `Вы списываете ${baseAmount} ₽ со своего счёта. Клиент получает промокод на ${bonusAmount} ₽.`;
});

const payoutDescription = computed(() => {
  return `Вы можете подать заявку на вывод средств с бонусного счёта на свои реквизиты. Минимальная сумма для вывода: ${minPayout.value} ₽. Комиссия при выводе зависит от вашего статуса:`;
});

// Функция для получения читаемого названия типа партнёра
const getPartnerTypeName = (typeName) => {
  const names = {
    'individual': 'Физическое лицо',
    'self-employed': 'Самозанятый',
    'entrepreneur': 'Индивидуальный предприниматель (ИП)',
    'company': 'ООО'
  };
  return names[typeName] || typeName;
};
</script>

<style scoped>
.conditions {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
  line-height: 1.6;
}

header {
  margin-bottom: 2rem;
}

h1 {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 1rem;
}

h2 {
  font-size: 1.5rem;
  font-weight: bold;
  margin: 2rem 0 1rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e5e7eb;
}

h3 {
  font-size: 1.25rem;
  font-weight: bold;
  margin: 1.5rem 0 0.5rem 0;
}

.subtitle {
  font-size: 1.125rem;
  color: #6b7280;
}

p {
  margin-bottom: 1rem;
}

ul, ol {
  margin: 1rem 0;
  padding-left: 2rem;
}

li {
  list-style: circle;
  margin-bottom: 0.5rem;
}

code {
  display: block;
  background: #f3f4f6;
  padding: 1rem;
  border-radius: 0.375rem;
  margin: 1rem 0;
  font-family: monospace;
  border: 1px solid #e5e7eb;
}

.example {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 0.375rem;
  margin: 1rem 0;
  border-left: 4px solid #d1d5db;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin: 1rem 0;
}

th, td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

th {
  background: #f9fafb;
  font-weight: bold;
}

footer {
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
  color: #6b7280;
}

.text-center {
  text-align: center;
}

.text-sm {
  font-size: 0.875rem;
}

.border-t {
  border-top: 1px solid #e5e7eb;
}

.pt-4 {
  padding-top: 1rem;
}

.mt-8 {
  margin-top: 2rem;
}

.mb-8 {
  margin-bottom: 2rem;
}
</style>