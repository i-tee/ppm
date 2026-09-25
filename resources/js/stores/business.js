// resources/js/stores/business.js
// Кеш тяжёлого GET /api/user/business-data (сервер ходит в удалённую Joomla
// и в основной бэкенд). Повторный заход на экран показывает кеш сразу,
// а если ему больше STALE_MS - тихо обновляет в фоне, не блокируя интерфейс.
import { defineStore } from "pinia";
import api from "@/api";

const STALE_MS = 60000;

export const useBusinessStore = defineStore("business", {
  state: () => ({
    data: null,
    loadedAt: 0,
    loading: false,
    error: null,
    promise: null,
  }),

  getters: {
    isStale: (state) => !state.loadedAt || Date.now() - state.loadedAt > STALE_MS,
  },

  actions: {
    // force: true - принудительная синхронная перезагрузка (после действий
    // пользователя вроде создания промокода). Без force: если кеш свежий -
    // отдаём его сразу без запроса; если протух - отдаём кеш сразу, но
    // запускаем тихое обновление в фоне.
    async load({ force = false } = {}) {
      if (force) {
        // Дожидаемся текущего запроса (если он есть) перед новым - иначе
        // при force вернулся бы promise, стартовавший ДО действия
        // пользователя (например, создания промокода), и он отдал бы
        // старые данные.
        if (this.promise) {
          await this.promise.catch(() => {});
        }
        return this._fetch();
      }
      if (this.promise) {
        return this.promise;
      }
      if (this.data) {
        if (this.isStale) {
          // Фоновое обновление - ошибку уже сохраняем в this.error, здесь
          // просто гасим, чтобы не было unhandled rejection.
          this._fetch().catch(() => {});
        }
        return Promise.resolve(this.data);
      }
      return this._fetch();
    },

    _fetch() {
      if (this.promise) {
        return this.promise;
      }

      this.loading = !this.data;
      this.error = null;

      this.promise = api
        .get("/user/business-data")
        .then((response) => {
          this.data = response.data;
          this.loadedAt = Date.now();
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
      this.loadedAt = 0;
      this.loading = false;
      this.error = null;
      this.promise = null;
    },
  },
});
