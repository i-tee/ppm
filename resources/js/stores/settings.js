// resources/js/stores/settings.js
// Настройки партнёрской программы (GET /api/ps). Не зависят от пользователя,
// поэтому грузятся один раз за сессию и раздаются всем экранам из одного места.
import { defineStore } from "pinia";
import api from "@/api";

export const useSettingsStore = defineStore("settings", {
  state: () => ({
    data: null,
    loading: false,
    loaded: false,
    error: null,
    promise: null,
  }),

  actions: {
    // Повторные вызовы, пока promise не разрешился (или данные уже загружены),
    // запроса не делают и отдают тот же promise/данные.
    load() {
      if (this.loaded) {
        return Promise.resolve(this.data);
      }
      if (this.promise) {
        return this.promise;
      }

      this.loading = true;
      this.error = null;

      this.promise = api
        .get("/ps")
        .then((response) => {
          this.data = response.data;
          this.loaded = true;
          return this.data;
        })
        .catch((error) => {
          this.error = error.response?.data?.message || error.message;
          throw error;
        })
        .finally(() => {
          this.loading = false;
          this.promise = null;
        });

      return this.promise;
    },

    reset() {
      this.data = null;
      this.loading = false;
      this.loaded = false;
      this.error = null;
      this.promise = null;
    },
  },
});
