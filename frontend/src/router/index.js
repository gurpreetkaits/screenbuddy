import { createRouter, createWebHistory } from "vue-router";

const routes = [
  {
    path: "/",
    redirect: "/videos"
  },
  {
    path: "/login",
    name: "Login",
    component: () => import("../views/LoginView.vue"),
    meta: { guest: true }
  },
  {
    path: "/",
    component: () => import("../components/Layout/AppLayout.vue"),
    meta: { requiresAuth: true },
    children: [
      {
        path: "videos",
        name: "Videos",
        component: () => import("../views/VideosView.vue"),
      },
      {
        path: "record",
        name: "Record",
        component: () => import("../views/StreamRecordView.vue"),
      },
      {
        path: "record-legacy",
        name: "RecordLegacy",
        component: () => import("../views/RecordView.vue"),
      },
      {
        path: "profile",
        name: "Profile",
        component: () => import("../views/ProfileView.vue"),
      },
      {
        path: "subscription",
        name: "Subscription",
        component: () => import("../views/SubscriptionView.vue"),
      },
      {
        path: "subscription/success",
        name: "SubscriptionSuccess",
        component: () => import("../views/SubscriptionSuccessView.vue"),
      },
    ]
  },
  {
    path: "/video/:id",
    name: "VideoPlayer",
    component: () => import("../views/VideoPlayerView.vue"),
    meta: { requiresAuth: true }
  },
  {
    path: "/share/video/:token",
    name: "SharedVideo",
    component: () => import("../views/SharedVideoView.vue"),
    // Public - no auth required
  },
  // Blog routes moved to Laravel (server-rendered for SEO)
  // Access blog at: /blog and /blog/:slug via Laravel routes
  {
    path: "/auth/callback",
    name: "AuthCallback",
    component: () => import("../views/AuthCallbackView.vue"),
  },
  {
    path: "/:pathMatch(.*)*",
    component: () => import("../views/NotFoundView.vue"),
  },
];


export const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation guard
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('auth_token')
  const isAuthenticated = !!token

  // Route requires authentication
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!isAuthenticated) {
      // Redirect to login with return URL
      next({
        name: 'Login',
        query: { redirect: to.fullPath }
      })
      return
    }
  }

  // Route is for guests only (like login page)
  if (to.matched.some(record => record.meta.guest)) {
    if (isAuthenticated) {
      // Already logged in, redirect to videos
      next({ name: 'Videos' })
      return
    }
  }

  next()
})
