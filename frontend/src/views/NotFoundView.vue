<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center items-center">
    <div class="text-center">
      <h1 class="text-9xl font-bold text-gray-300">404</h1>
      <h2 class="text-2xl font-semibold text-gray-900 mb-4">Page not found</h2>
      <p class="text-gray-600 mb-8">Sorry, we couldn't find the page you're looking for.</p>
      <router-link
        to="/videos"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors duration-200"
      >
        Go back home
      </router-link>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NotFoundView'
}
</script>
