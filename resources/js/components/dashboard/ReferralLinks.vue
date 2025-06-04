<template>
  <div>
    <va-card>
      <va-card-title>{{ $t('dashboard.referral_links') }}</va-card-title>
      <va-card-content>
        <va-list>
          <va-list-item v-for="(link, index) in referralLinks" :key="index">
            <span>{{ link.url }}</span>
            <va-button size="small" color="secondary" class="ml-2" @click="copyLink(link.url)">
              {{ $t('dashboard.copy') }}
            </va-button>
          </va-list-item>
          <va-list-item>
            <va-button color="primary" @click="generateLink">
              {{ $t('dashboard.generate_link') }}
            </va-button>
          </va-list-item>
        </va-list>
      </va-card-content>
    </va-card>
  </div>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'ReferralLinks',
  data() {
    return {
      referralLinks: [
        { url: 'https://example.com/ref/12345' },
        { url: 'https://example.com/ref/4567890' },
      ],
    };
  },
  methods: {
    copyLink(url) {
      navigator.clipboard.writeText(url).then(() => {
        this.$vaToast.init({
          message: this.$t('dashboard.copied'),
          color: 'success',
        });
      });
    },
    generateLink() {
      const newLink = `https://example.com/ref/${Math.floor(Math.random() * 100000)}`;
      this.referralLinks.push({ url: newLink });
      this.$vaToast.init({
        message: this.$t('dashboard.link_generated'),
        color: 'success',
      });
    },
  },
});
</script>