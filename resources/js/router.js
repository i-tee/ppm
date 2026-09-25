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
        meta: { roles: ["partner"] },
      },
      {
        path: "promocodes",
        name: "Promocodes",
        component: Promocodes,
        meta: { roles: ["partner"] },
      },
      {
        path: "influencer",
        name: "Influencer",
        component: Influencer,
        meta: { roles: ["partner"] },
      },
      {
        path: "agent",
        name: "Agent",
        component: Agent,
        meta: { roles: ["partner"] },
      },
      {
        path: "requisite",
        name: "Requisite",
        component: Requisite,
        meta: { roles: ["partner"] },
      },
      {
        path: "wholesale",
        name: "Wholesale",
        component: Wholesale,
        meta: { roles: ["partner"] },
      },
      {
        path: "distributor",
        name: "Distributor",
        component: Distributor,
        meta: { roles: ["partner"] },
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
        meta: { roles: ["finance"] },
      },
      {
        path: "payout-resolve",
        name: "PayoutResolve",
        component: PayoutResolve,
        meta: { roles: ["finance"] },
      },
      {
        path: "impersonate",
        name: "Impersonate",
        component: Impersonate,
        meta: { roles: ["admin"] },
      },
      {
        path: "partner-applications",
        name: "PartnerApplications",
        component: PartnerApplications,
        meta: { roles: ["admin"] },
      },
      {
        path: "types",
        name: "Types",
        component: Types,
        meta: { roles: ["partner"] },
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
        meta: { roles: ["partner"] },
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

// «Домашний» экран роли — используется для редиректа при нехватке прав.
function homeRouteFor(authStore) {
  if (authStore.isAdmin) return { name: "PartnerApplications" };
  if (authStore.isAccountant) return { name: "PayoutResolve" };
  return { name: "Overview" };
}

function roleAllows(role, authStore) {
  if (role === "admin") return authStore.isAdmin;
  if (role === "finance") return authStore.canManageFinance;
  if (role === "partner") return !authStore.isStaff;
  return false;
}

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

    const requiredRoles = to.meta.roles;
    if (requiredRoles && !requiredRoles.some((role) => roleAllows(role, authStore))) {
      return next(homeRouteFor(authStore));
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
