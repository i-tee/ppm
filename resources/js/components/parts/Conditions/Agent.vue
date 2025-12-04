<template>
  <div class="max-w-4xl mx-auto px-4 py-8 text-gray-800">

    <header class="text-center mb-10">
      <h1 class="text-3xl font-bold mb-4">Партнёрская программа "Агент"</h1>
      <p class="text-lg text-gray-600">
        Зарабатывайте, рекомендуя качественные продукты. Создавайте промокоды, делитесь ссылками и получайте
        вознаграждение за каждую успешную покупку.
      </p>
    </header>

    <div class="space-y-10">

      <section>
        <h2 class="text-2xl font-semibold border-b pb-2 mb-4">Общие положения</h2>
        <p class="mb-4">
          Партнёрская программа "Агент" — это возможность для наших партнёров получать вознаграждение за привлечение
          новых клиентов. Зарегистрировавшись в программе, вы получаете доступ к инструментам для создания промокодов и
          отслеживания
          статистики.
        </p>
        <p>Участие в программе подразумевает полное согласие с настоящими условиями.</p>
      </section>

      <section>
        <h2 class="text-2xl font-semibold border-b pb-2 mb-4">Как работает программа</h2>

        <h3 class="text-xl font-semibold mt-6 mb-2">1. Регистрация и принятие оферты</h3>
        <p class="mb-4">
          Для участия в программе необходимо зарегистрироваться в Личном кабинете, подтвердить email и принять публичную
          оферту. После этого вы получаете полный доступ ко всем инструментам партнёрской программы.
        </p>

        <h3 class="text-xl font-semibold mt-6 mb-2">2. Создание промокодов и ссылок</h3>
        <p class="mb-2">
          В Личном кабинете вы можете создавать неограниченное количество персональных промокодов.
          Для каждого промокода система автоматически генерирует уникальную партнёрскую ссылку вида:
        </p>
        <code class="block bg-gray-100 border border-gray-300 rounded p-3 font-mono text-sm">
          {{ couponBaseUrl }}UNIQUE_CODE
        </code>

        <h3 class="text-xl font-semibold mt-6 mb-2">3. Настройка промокода</h3>
        <p class="mb-2">При создании промокода вы указываете:</p>
        <ul class="list-disc pl-6 space-y-1">
          <li>Скидку для клиента</li>
          <li>Размер кешбэка</li>
          <li>
            <strong>Вы распределяете выгоду между собой и клиентом</strong>:
            <ul class="list-disc pl-6 mt-1">
              <li><strong>Клиентская доля (Y%)</strong> — скидка для конечного покупателя</li>
              <li><strong>Партнёрская доля (Z%)</strong> — ваше вознаграждение</li>
            </ul>
          </li>
          <li><strong>Общая скидка (X%)</strong> — максимальный лимит устанавливается компанией. Сейчас это {{
            maxDiscount }}%. Может быть увеличен индивидуально</li>
        </ul>
        <p class="mt-2">Формула распределения: <strong>X% = Y% + Z%</strong></p>
        <div class="bg-gray-50 border-l-4 border-gray-300 p-4 my-4">
          <p>
          <p><strong>Пример:</strong> Корзина товаров на сумму {{ formatPrice(productPrice) }}:</p>
          <p>Вы создаёте промокод с общей скидкой <b>{{ maxDiscount }}% </b>({{ formatPrice(totalDiscount) }}).</p>
          <p>Вы решаете дать клиенту скидку <b>{{ clientDiscountPercent }}% </b>({{ formatPrice(clientDiscount) }}).</p>
          <p>А себе зачислить <b>{{ partnerDiscountPercent }}% </b>({{ formatPrice(partnerDiscount) }}).</p>
          </p>
        </div>

        <div class="custom-block-demo" style="padding: 20px; margin: -40px 0;">
          <p class="text-primary font-bold text-center text-lg">Меняйте пропорции так, как выгодно в Вам!</p>
          <div class="custom-block-discount my-4">
            <div>
              <VaSlider v-model="clientDiscountPercent" :min="0" :max="maxDiscountPercent" :step="1" size="2rem" />
            </div>
            <div class="my-4"></div>
            <div>
              <!-- контейнер-ряд -->
              <div class="flex flex-row gap-4">
                <!-- колонка 1/2 -->
                <div class="text-white w-1/2 p-4 rounded-md" style="background: var(--va-primary)">
                  <div class="text-center">
                    <div>{{ $t('coupons.discountClient') }}</div>
                    <div class="text-lg font-bold mt-1">{{ formatPrice(clientDiscount) }}</div>
                  </div>
                </div>
                <!-- колонка 1/2 -->
                <div class="w-1/2 p-4 rounded-md" style="background-color: rgba(21, 78, 193, 0.2)">
                  <div class="text-center" style="color: var(--va-primary)">
                    <div>{{ $t('coupons.discountAgent') }}</div>
                    <div class="text-lg font-bold mt-1">
                      {{ formatPrice(partnerDiscount) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-yellow-50 border-l-4 border-yellow-300 p-4 my-4">
          <p><strong>ВАЖНО! Именно Вы определяете долю своей прибыли!</strong> Можете оставить все {{ maxDiscount }}%
            себе, или сделать скидку любимому клиенту в размере {{ maxDiscount }}%. Поделить пополам или в любых других
            пропорциях.</p>
          <p>😉 Создайте много промокодов для разных ситуаций</p>
        </div>

        <h3 class="text-xl font-semibold mt-6 mb-2">4. Использование промокодов</h3>
        <p class="mb-2">Клиент может:</p>
        <ul class="list-disc pl-6 space-y-1">
          <li>Ввести промокод вручную при оформлении заказа на сайте</li>
          <li>Или перейти по вашей партнёрской ссылке — в этом случае промокод применится автоматически</li>
        </ul>
        <p class="mt-2">В обоих случаях клиент получает установленную вами скидку (Y%).</p>

        <h3 class="text-xl font-semibold mt-6 mb-2">5. Начисление бонусов</h3>
        <p class="mb-4">
          После успешной оплаты и обработки заказа, ваша доля (Z%) зачисляется на бонусный счёт в Личном кабинете —
          независимо от способа активации промокода.
        </p>

        <h3 class="text-xl font-semibold mt-6 mb-2">6. Бонусные промокоды</h3>
        <p class="mb-2">
          Вы можете создавать специальные промокоды "на сумму", которые будут списывать средства с вашего бонусного
          счёта. Особенности:
        </p>
        <ul class="list-disc pl-6 space-y-1">
          <li>Номинал такого промокода для клиента будет на {{ bonusPercentage }}% ВЫШЕ, чем сумма, списанная с вашего
            счёта</li>
          <li>Выгоднее использовать бонусы получения продуктов, а не выводить средства</li>
        </ul>
        <div class="bg-gray-50 border-l-4 border-gray-300 p-4 my-4">
          <p><strong>Пример:</strong> {{ bonusExample }}</p>
        </div>

        <h3 class="text-xl font-semibold mt-6 mb-2">7. Вывод средств</h3>
        <p class="mb-4">{{ payoutDescription }}</p>

        <table class="w-full border-collapse border border-gray-300 text-sm">
          <thead>
            <tr class="bg-gray-100">
              <th class="border border-gray-300 px-4 py-2 text-left">Статус партнёра</th>
              <th class="border border-gray-300 px-4 py-2 text-left">Комиссия при выводе</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="partnerType in partnerTypes" :key="partnerType.id">
              <td class="border border-gray-300 px-4 py-2">{{ getPartnerTypeName(partnerType.name) }}</td>
              <td class="border border-gray-300 px-4 py-2">
                <span v-if="partnerType.tax > 0">{{ partnerType.tax }}% от суммы вывода</span>
                <span v-else>0% комиссии</span>
              </td>
            </tr>
          </tbody>
        </table>

        <p class="mt-4"><strong>Методы выплаты:</strong></p>
        <ul class="list-disc pl-6 space-y-1">
          <li>На расчётный счёт по реквизитам</li>
        </ul>

        <h3 class="text-xl font-semibold mt-6 mb-2">8. Аналитика</h3>
        <p>
          В Личном кабинете вы видите детальную статистику по каждому промокоду:
        </p>
        <ul class="list-disc pl-6 space-y-1">
          <li>Количество использований</li>
          <li>Информация о заказе с промокодом</li>
          <li>Общая сумма начисленных бонусов</li>
        </ul>
      </section>

      <section>
        <h2 class="text-2xl font-semibold border-b pb-2 mb-4">Ограничения и правила</h2>

        <h3 class="text-xl font-semibold mt-6 mb-2">Финансовые лимиты</h3>
        <ul class="list-disc pl-6 space-y-1">
          <li>Максимальная общая скидка (X%) устанавливается компанией и составляет {{ maxDiscount }}%</li>
          <li>Минимальная сумма для вывода средств: {{ minPayout }} ₽</li>
          <li>Коэффициент для бонусных промокодов: {{ bonusKoef }} (номинал промокода на {{ bonusPercentage }}% выше
            суммы списания)</li>
        </ul>

        <h3 class="text-xl font-semibold mt-6 mb-2">Юридические аспекты</h3>
        <ul class="list-disc pl-6 space-y-1">
          <li>Участие в программе регулируется публичной офертой, принимаемой при регистрации</li>
          <li>Компания оставляет за собой право изменять условия программы и лимиты</li>
          <li>Нарушение правил программы может привести к блокировке аккаунта</li>
        </ul>

        <h3 class="text-xl font-semibold mt-6 mb-2">Налоговые обязательства</h3>
        <ul class="list-disc pl-6 space-y-1">
          <li v-for="partnerType in partnerTypes" :key="'tax-' + partnerType.id">
            <strong>{{ getPartnerTypeName(partnerType.name) }}:</strong>
            <span v-if="partnerType.tax > 0"> комиссия {{ partnerType.tax }}% при выводе</span>
            <span v-else> комиссия не взимается, налоги уплачиваются самостоятельно</span>
          </li>
        </ul>
      </section>

      <section class="bg-gray-100 p-4 rounded-md text-sm text-gray-600">
        <p>{{ $t('disclaimer.agent') }}</p>
      </section>

      <section>
        <h2 class="text-2xl font-semibold border-b pb-2 mb-4">Контакты и поддержка</h2>
        <p class="mb-2">
          Если у вас возникли вопросы по работе партнёрской программы, обращайтесь в службу поддержки:
        </p>
        <ul class="list-disc pl-6 space-y-1">
          <li>Email: partners@avicenna.com.ru</li>
          <li>Часы работы: пн-пт с 9:00 до 18:00 по московскому времени</li>
        </ul>
      </section>

    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useBase } from '@/composables/useBase'

const { formatPrice } = useBase()

const props = defineProps({
  apiData: {
    type: Object,
    required: true,
  },
});

const agentData = computed(() => {
  return props.apiData.cooperation_types.find(type => type.name === 'agent') || {};
});

const partnerTypes = computed(() => {
  return props.apiData.partner_types || [];
});

const maxDiscount = computed(() => agentData.value.max_discount || 20);
const minPayout = computed(() => agentData.value.min_payout || 500);
const bonusKoef = computed(() => agentData.value.bonus_koef || 1.2);
const bonusPercentage = computed(() => Math.round((bonusKoef.value - 1) * 100));
const couponBaseUrl = computed(() => agentData.value.coupon_base_url || 'https://avicenna.com.ru/buy?_prcp=');

const productPrice = 10000
const maxDiscountPercent = computed(() => agentData.value.max_discount || 20)

const totalDiscount = computed(() => productPrice * maxDiscountPercent.value / 100)

const clientDiscountPercent = ref(12) // <-- ползунок будет управлять этим
const clientDiscount = computed(() => Math.round(productPrice * clientDiscountPercent.value / 100))
const partnerDiscount = computed(() => totalDiscount.value - clientDiscount.value)
const partnerDiscountPercent = computed(() => maxDiscountPercent.value - clientDiscountPercent.value)

const bonusExample = computed(() => {
  const baseAmount = 10000;
  const bonusAmount = Math.round(baseAmount * bonusKoef.value);
  return `Вы списываете ${baseAmount} ₽ со своего счёта. Клиент получает промокод на ${bonusAmount} ₽.`;
});

const payoutDescription = computed(() => {
  return `Вы можете подать заявку на вывод средств с бонусного счёта на свои реквизиты. Минимальная сумма для вывода: ${minPayout.value} ₽. Комиссия при выводе зависит от вашего статуса:`;
});

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
