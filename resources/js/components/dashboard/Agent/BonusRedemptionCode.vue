<template>
  <div>
    <p>{{ $t('coupons.info_text') }}</p>
    <ul class="mt-2">
      <li v-for="item in infoItems" :key="item" class="mb-1">
        • {{ $t(item) }}
      </li>
    </ul>
  </div>
</template>

<script setup>
const infoItems = [
  'coupons.info_items.item1',
  'coupons.info_items.item2',
  'coupons.info_items.item3'
]
</script>