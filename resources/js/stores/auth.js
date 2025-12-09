// resources/js/stores/auth.js
import { defineStore } from "pinia";
import api from "@/api";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
    token: localStorage.getItem("auth_token") || null,
    loading: false,
    error: null,
  }),

  actions: {
    async login(credentials) {
      if (this.loading) return;
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post("/login", credentials);
        const { user, token } = response.data;
        this.user = user;
        this.token = token;
        localStorage.setItem("auth_token", token);
      } catch (error) {
        this.error = error.response?.data?.message || "Ошибка авторизации";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchUser() {
      if (!this.token) {
        this.user = null;
        return;
      }
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get("/user");
        this.user = response.data;
      } catch (error) {
        this.user = null;
        this.token = null;
        localStorage.removeItem("auth_token");
      } finally {
        this.loading = false;
      }
    },

    async updateUser(data) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.put("/user", data);
        this.user = response.data;
        return true;
      } catch (error) {
        this.error =
          error.response?.data?.message || "Ошибка обновления данных";
        return false;
      } finally {
        this.loading = false;
      }
    },

    async logout(router) {
      // Принимаем router как параметр
      this.loading = true;
      this.error = null;
      try {
        await api.post("/logout");
        this.user = null;
        this.token = null;
        localStorage.removeItem("auth_token");
        if (router) {
          router.push("/login"); // Редирект через переданный router
        }
      } catch (error) {
        this.error = error.response?.data?.message || "Ошибка при выходе";
      } finally {
        this.loading = false;
      }
    },

    async requestPasswordReset(email) {
      this.loading = true;
      try {
        const response = await api.post("/forgot-password", { email });
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async resetPassword(token, newPassword, email, passwordConfirmation) {
      this.loading = true;
      try {
        console.log("Sending reset request with:", {
          token,
          email,
          password: newPassword,
          password_confirmation: passwordConfirmation,
        });
        const response = await api.post("/reset-password", {
          token: token,
          email: email,
          password: newPassword,
          password_confirmation: passwordConfirmation, // Добавляем подтверждение
        });
        const { user, token: newToken } = response.data;
        this.user = user;
        this.token = newToken;
        localStorage.setItem("auth_token", newToken);
        return true;
      } catch (error) {
        console.log(
          "Reset error on server:",
          error.response?.data || error.message
        );
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async uploadAvatar(formData) {
      this.loading = true;
      try {
        const response = await api.post("/user/avatar", formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        });

        // Обновляем только аватар у текущего пользователя
        if (this.user) {
          this.user.avatar = response.data.user.avatar;
          this.user.avatar_url = response.data.avatar_url; // Добавляем вычисляемый URL
        }

        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || "Ошибка загрузки аватара";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async impersonate(userId) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post(`/admin/impersonate/${userId}`); // ✅ Правильно
        const { token, user } = response.data;

        // Сохраняем original перед сменой
        localStorage.setItem("original_auth_token", this.token);
        localStorage.setItem("original_user", JSON.stringify(this.user));

        // Меняем на новый
        this.token = token;
        this.user = user;
        localStorage.setItem("auth_token", token);
        localStorage.setItem("impersonated_user", JSON.stringify(user));
        localStorage.setItem("is_impersonating", "true");

        return true;
      } catch (error) {
        this.error = error.response?.data?.error || "Ошибка impersonation";
        return false;
      } finally {
        this.loading = false;
      }
    },

    async stopImpersonation() {
      this.loading = true;
      this.error = null;
      try {
        // Сначала стоп на бэке (чтобы удалить токен)
        await api.post("/admin/impersonate/stop"); // ✅ Исправлено!

        // Восстанавливаем original
        const originalToken = localStorage.getItem("original_auth_token");
        const originalUser = localStorage.getItem("original_user");
        if (originalToken && originalUser) {
          this.token = originalToken;
          this.user = JSON.parse(originalUser);
          localStorage.setItem("auth_token", originalToken);
        }

        // Очищаем
        localStorage.removeItem("original_auth_token");
        localStorage.removeItem("original_user");
        localStorage.removeItem("impersonated_user");
        localStorage.removeItem("is_impersonating");

        return true;
      } catch (error) {
        this.error = error.response?.data?.error || "Ошибка stop impersonation";
        return false;
      } finally {
        this.loading = false;
      }
    },
  },

  getters: {
    isAuthenticated: (state) => !!state.token,
    currentUser: (state) => state.user,
    isImpersonating: (state) =>
      localStorage.getItem("is_impersonating") === "true",
    isAdmin: (state) =>
      state.user &&
      (state.user.effective_access_levels?.includes(1) ||
        state.user.effective_access_levels?.includes(2)),
  },
});
