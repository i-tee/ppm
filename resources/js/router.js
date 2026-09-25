import { createRouter, createWebHistory } from "vue-router";
import Welcome from "./components/Welcome.vue";
import Register from "./components/Register.vue";
import Dashboard from "./components/Dashboard.vue";
import ResetPassword from "./components/ResetPassword.vue";
import NotFound from "./components/NotFound.vue";
import { useAuthStore } from "./stores/auth";

// Дочерние экраны дашборда - лениво, чтобы при заходе на один экран не
// тянуть код всех остальных (Welcome/Dashboard остаются статическими).
const Overview = () => import("./components/dashboard/Overview.vue");
const Promocodes = () => import("./components/dashboard/Promocodes.vue");
const Influencer = () => import("./components/dashboard/Influencer.vue");
const Agent = () => import("./components/dashboard/Agent.vue");
const Requisite = () => import("./components/dashboard/Requisite.vue");
const Wholesale = () => import("./components/dashboard/Wholesale.vue");
const Distributor = () => import("./components/dashboard/Distributor.vue");
const PartnerApplications = () =>
  import("./components/dashboard/PartnerApplications.vue");
const RequisiteVerification = () =>
  import("./components/dashboard/RequisiteVerification.vue");
const PayoutResolve = () => import("./components/dashboard/PayoutResolve.vue");
const Types = () => import("./components/dashboard/Types.vue");
const Impersonate = () => import("./components/dashboard/Impersonate.vue");
const ReferralLinks = () => import("./components/dashboard/ReferralLinks.vue");
const Account = () => import("./components/dashboard/Account.vue");
// import Dev from "./components/dashboard/Dev.vue";

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
        path: "payout-resolve",
        name: "PayoutResolve",
        component: PayoutResolve,
      },
      {
        path: "impersonate",
        name: "Impersonate",
        component: Impersonate,
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
