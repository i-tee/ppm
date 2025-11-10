import { createRouter, createWebHistory } from "vue-router";
import Welcome from "./components/Welcome.vue";
import Register from "./components/Register.vue";
import Dashboard from "./components/Dashboard.vue";
import Overview from "./components/dashboard/Overview.vue";
import Promocodes from "./components/dashboard/Promocodes.vue";
// import Dev from "./components/dashboard/Dev.vue";
import Influencer from "./components/dashboard/Influencer.vue";
import Agent from "./components/dashboard/Agent.vue";
import Requisite from "./components/dashboard/Requisite.vue";
import Wholesale from "./components/dashboard/Wholesale.vue";
import Distributor from "./components/dashboard/Distributor.vue";
import PartnerApplications from "./components/dashboard/PartnerApplications.vue";
import RequisiteVerification from "./components/dashboard/RequisiteVerification.vue";
import Types from "./components/dashboard/Types.vue";
import ReferralLinks from "./components/dashboard/ReferralLinks.vue";
import Account from "./components/dashboard/Account.vue";
import ResetPassword from "./components/ResetPassword.vue";
import NotFound from "./components/NotFound.vue";
import { useAuthStore } from "./stores/auth";

const routes = [
  {
    path: "/",
    redirect: "/welcome",
  },
  {
    path: "/welcome",
    name: "welcome",
    component: Welcome,
    meta: { requiresAuth: false },
  },
  {
    path: "/register",
    name: "register",
    component: Register,
    meta: { requiresAuth: false },
  },
  {
    path: "/reset-password",
    name: "reset-password",
    component: ResetPassword,
    meta: { requiresAuth: false },
  },
  {
    path: "/dashboard",
    name: "dashboard",
    component: Dashboard,
    meta: { requiresAuth: true },
    children: [
      {
        path: "",
        name: "Overview",
        component: Overview,
      },
      {
        path: "promocodes",
        name: "Promocodes",
        component: Promocodes,
      },
      {
        path: "influencer",
        name: "Influencer",
        component: Influencer,
      },
      {
        path: "agent",
        name: "Agent",
        component: Agent,
      },
      {
        path: "requisite",
        name: "Requisite",
        component: Requisite,
      },
      {
        path: "wholesale",
        name: "Wholesale",
        component: Wholesale,
      },
      {
        path: "distributor",
        name: "Distributor",
        component: Distributor,
      },
      // {
      //   path: "dev",
      //   name: "Dev",
      //   component: Dev,
      // },
      {
        path: "requisite-verification",
        name: "RequisiteVerification",
        component: RequisiteVerification,
      },
      {
        path: "partner-applications",
        name: "PartnerApplications",
        component: PartnerApplications,
      },
      {
        path: "types",
        name: "Types",
        component: Types,
      },
      {
        path: "account",
        name: "Account",
        component: Account,
      },
      {
        path: "referral-links",
        name: "ReferralLinks",
        component: ReferralLinks,
      },
    ],
  },
  {
    path: "/:pathMatch(.*)*",
    name: "not-found",
    component: NotFound,
    meta: { requiresAuth: false },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  const urlParams = new URLSearchParams(window.location.search);
  const token = urlParams.get("token");
  const email = urlParams.get("email");
  const verified = urlParams.get("email_verified") === "1";

  // Устанавливаем токен и минимальные данные
  if (token && email && !authStore.token) {
    authStore.token = token;
    authStore.user = { email, email_verified: verified };
    localStorage.setItem("auth_token", token);

    // Немедленно подтягиваем полные данные пользователя
    try {
      await authStore.fetchUser();
    } catch (error) {
      console.error("Failed to fetch user:", error);
      authStore.logout();
    }
  }

  if (to.meta.requiresAuth && !authStore.token) {
    return next({ name: "welcome" });
  }

  if (authStore.isAuthenticated) {
    if (to.name === "welcome" || to.name === "register") {
      return next({ name: "Account" });
    }
    if (to.name === "reset-password" && !to.query.token) {
      return next({ name: "not-found" });
    }
    try {
      if (!authStore.user) {
        await authStore.fetchUser();
      }
    } catch (error) {
      authStore.logout();
      return next({ name: "welcome" });
    }
    return next();
  }

  if (
    to.name !== "welcome" &&
    to.name !== "register" &&
    to.name !== "reset-password"
  ) {
    return next({ name: "welcome" });
  }

  return next();
});

export default router;
