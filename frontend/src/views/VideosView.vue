<template>
  <div class="bg-white min-h-full">
    <!-- Header -->
    <div class="border-b border-gray-200">
      <div class="px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8">
        <!-- Breadcrumb -->
        <div class="flex items-center text-sm text-gray-500 mb-6">
          <span>Library</span>
          <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
          <span class="text-gray-900 font-medium">Videos</span>
        </div>

        <!-- Title and Actions -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6 sm:mb-8">
          <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4 w-full sm:w-auto">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Videos</h1>
            <div class="hidden md:flex items-center gap-2">
              <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 rounded-lg">
                <img
                  v-if="currentUser?.avatar"
                  :src="currentUser.avatar"
                  :alt="currentUser.name"
                  class="w-6 h-6 rounded-full object-cover"
                />
                <div v-else class="w-6 h-6 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-xs font-semibold">
                  {{ userInitial }}
                </div>
                <span class="text-sm text-gray-700 font-medium">{{ currentUser?.name || 'My Library' }}</span>
              </div>
            </div>
          </div>
          <button
            @click="goToRecord"
            class="inline-flex items-center px-4 sm:px-5 py-2 sm:py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium shadow-sm transition-colors duration-200 text-sm sm:text-base w-full sm:w-auto justify-center"
            :disabled="!canRecord"
            :class="{ 'opacity-50 cursor-not-allowed': !canRecord }"
          >
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <circle cx="10" cy="10" r="6"/>
            </svg>
            <span class="hidden sm:inline">Record new video</span>
            <span class="sm:hidden">Record</span>
          </button>
        </div>

        <!-- Subscription Quota Banner -->
        <div v-if="subscription" class="mt-6 mb-4">
          <!-- Free tier at/near limit -->
          <div
            v-if="!subscription.is_active && subscription.remaining_quota !== null && subscription.remaining_quota <= 0"
            class="bg-gradient-to-r from-orange-50 to-red-50 border border-orange-200 rounded-lg p-4"
          >
            <div class="flex items-start justify-between">
              <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                </div>
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-900 mb-1">Video Limit Reached</h3>
                  <p class="text-sm text-gray-600">
                    You've used all {{ subscription.videos_count }} of your free videos. Upgrade to Pro for unlimited recordings.
                  </p>
                </div>
              </div>
              <button
                @click="openUpgradeModal"
                class="flex-shrink-0 px-4 py-2 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white rounded-lg font-medium text-sm transition-all"
              >
                Upgrade to Pro
              </button>
            </div>
          </div>
        </div>

        <!-- Tabs and Controls -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <!-- Date Filter Tabs -->
          <div class="flex gap-2 sm:gap-3 overflow-x-auto w-full sm:w-auto">
            <button
              v-for="filter in dateFilters"
              :key="filter.id"
              @click="setDateFilter(filter.id)"
              class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors whitespace-nowrap"
              :class="activeDateFilter === filter.id
                ? 'bg-orange-100 text-orange-700'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            >
              {{ filter.label }}
            </button>

            <!-- Custom Date Picker -->
            <div v-if="activeDateFilter === 'custom'" class="flex items-center gap-2">
              <input
                type="date"
                v-model="customDateFrom"
                class="px-2 py-1 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
              />
              <span class="text-gray-400">to</span>
              <input
                type="date"
                v-model="customDateTo"
                class="px-2 py-1 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
              />
            </div>
          </div>

          <div class="flex items-center gap-2 sm:gap-4 w-full sm:w-auto justify-end">
            <!-- View Toggle -->
            <div class="flex items-center gap-1 border border-gray-300 rounded-lg p-1">
              <button
                @click="viewMode = 'grid'"
                class="p-1.5 rounded transition-colors"
                :class="viewMode === 'grid' ? 'bg-orange-100 text-orange-600' : 'hover:bg-gray-50 text-gray-600'"
                title="Grid view"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
              </button>
              <button
                @click="viewMode = 'list'"
                class="p-1.5 rounded transition-colors"
                :class="viewMode === 'list' ? 'bg-orange-100 text-orange-600' : 'hover:bg-gray-50 text-gray-600'"
                title="List view"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
              </button>
            </div>

            <!-- Sort Dropdown -->
            <div class="relative" ref="sortDropdownRef">
              <button
                @click="showSortDropdown = !showSortDropdown"
                class="flex items-center gap-1 sm:gap-2 text-xs sm:text-sm text-gray-600 hover:text-gray-900 px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <span class="hidden sm:inline">Sort:</span>
                <span class="font-medium text-gray-900">{{ currentSortLabel }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>

              <!-- Sort Dropdown Menu -->
              <div
                v-show="showSortDropdown"
                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
              >
                <button
                  v-for="option in sortOptions"
                  :key="option.id"
                  @click="setSortOption(option.id)"
                  class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center justify-between"
                  :class="sortBy === option.id ? 'text-orange-600 bg-orange-50' : 'text-gray-700'"
                >
                  {{ option.label }}
                  <svg v-if="sortBy === option.id" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <main class="px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-24">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-orange-600 border-t-transparent"></div>
        <p class="mt-4 text-gray-600">Loading videos...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-24">
        <div class="w-20 h-20 mx-auto mb-8 bg-red-100 rounded-full flex items-center justify-center">
          <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ error }}</h3>
        <button
          @click="fetchVideos"
          class="inline-flex items-center px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium shadow-sm transition-colors duration-200"
        >
          Try Again
        </button>
      </div>

      <!-- Videos Grid View -->
      <div v-else-if="filteredVideos.length > 0 && viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 sm:gap-6">
        <div
          v-for="video in paginatedVideos"
          :key="video.id"
          class="group cursor-pointer border border-gray-200 rounded-xl p-3 hover:border-orange-300 hover:shadow-md transition-all duration-200"
          @click="openVideo(video.id)"
        >
          <!-- Thumbnail -->
          <div class="relative aspect-video rounded-xl overflow-hidden bg-gray-900 mb-4">
            <img
              v-if="video.thumbnail"
              :src="video.thumbnail"
              :alt="video.title"
              class="w-full h-full object-cover"
              loading="lazy"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
              <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
              </svg>
            </div>

            <!-- Duration Badge -->
            <div class="absolute top-3 right-3 bg-black bg-opacity-80 backdrop-blur-sm text-white text-xs px-2.5 py-1 rounded-md font-medium">
              {{ formatDuration(video.duration) }}
            </div>


            <!-- Hover Overlay -->
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100">
              <div class="transform scale-90 group-hover:scale-100 transition-transform duration-200 mb-4">
                <button class="w-14 h-14 rounded-full bg-white flex items-center justify-center shadow-lg hover:scale-110 transition-transform">
                  <svg class="w-6 h-6 text-gray-900 ml-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/>
                  </svg>
                </button>
              </div>

              <div class="flex items-center gap-2 transform translate-y-2 group-hover:translate-y-0 transition-transform duration-200">
                <button @click.stop="shareVideo(video)" class="w-9 h-9 flex items-center justify-center bg-white/90 hover:bg-white text-gray-800 rounded-full shadow-lg transition-all hover:scale-110" title="Share">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                  </svg>
                </button>
                <button @click.stop="downloadVideo(video)" class="w-9 h-9 flex items-center justify-center bg-white/90 hover:bg-white text-gray-800 rounded-full shadow-lg transition-all hover:scale-110" title="Download">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                  </svg>
                </button>
                <button @click.stop="deleteVideo(video)" class="w-9 h-9 flex items-center justify-center bg-red-500/90 hover:bg-red-500 text-white rounded-full shadow-lg transition-all hover:scale-110" title="Delete">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Video Info -->
          <div class="space-y-3">
            <div class="flex items-center gap-5 text-sm text-gray-500">
              <div class="flex items-center gap-1.5" :title="`${video.reactions_count || 0} reactions`">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                </svg>
                <span>{{ video.reactions_count || 0 }}</span>
              </div>
              <div class="flex items-center gap-1.5" :title="`${video.comments_count || 0} comments`">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span>{{ video.comments_count || 0 }}</span>
              </div>
              <div class="flex items-center gap-1.5" :title="`${video.views_count || 0} views`">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <span>{{ video.views_count || 0 }}</span>
              </div>
            </div>
            <h3 class="font-medium text-gray-900 line-clamp-2 group-hover:text-orange-600 transition-colors leading-snug">
              {{ video.title }}
            </h3>
            <div class="flex items-center justify-between text-sm pt-1">
              <div class="flex items-center gap-2">
                <img v-if="currentUser?.avatar" :src="currentUser.avatar" :alt="currentUser.name" class="w-6 h-6 rounded-full object-cover"/>
                <div v-else class="w-6 h-6 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-xs font-semibold">
                  {{ userInitial }}
                </div>
                <span class="text-gray-600">{{ currentUser?.name || 'You' }}</span>
              </div>
              <span class="text-gray-500">{{ formatDate(video.createdAt) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Videos List View -->
      <div v-else-if="filteredVideos.length > 0 && viewMode === 'list'" class="space-y-3">
        <div
          v-for="video in paginatedVideos"
          :key="video.id"
          class="group cursor-pointer flex items-center gap-4 p-4 border border-gray-200 rounded-xl hover:border-orange-300 hover:shadow-md transition-all duration-200"
          @click="openVideo(video.id)"
        >
          <!-- Thumbnail -->
          <div class="relative w-48 flex-shrink-0 aspect-video rounded-lg overflow-hidden bg-gray-900">
            <img
              v-if="video.thumbnail"
              :src="video.thumbnail"
              :alt="video.title"
              class="w-full h-full object-cover"
              loading="lazy"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
              <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
              </svg>
            </div>
            <div class="absolute bottom-2 right-2 bg-black bg-opacity-80 text-white text-xs px-2 py-0.5 rounded font-medium">
              {{ formatDuration(video.duration) }}
            </div>
          </div>

          <!-- Video Info -->
          <div class="flex-1 min-w-0">
            <h3 class="font-medium text-gray-900 group-hover:text-orange-600 transition-colors truncate text-lg">
              {{ video.title }}
            </h3>
            <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
              <span>{{ formatDate(video.createdAt) }}</span>
              <div class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <span>{{ video.views_count || 0 }}</span>
              </div>
              <div class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span>{{ video.comments_count || 0 }}</span>
              </div>
              <div class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                </svg>
                <span>{{ video.reactions_count || 0 }}</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button @click.stop="shareVideo(video)" class="p-2 text-gray-500 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors" title="Share">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
              </svg>
            </button>
            <button @click.stop="downloadVideo(video)" class="p-2 text-gray-500 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors" title="Download">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
            </button>
            <button @click.stop="deleteVideo(video)" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="showPagination && filteredVideos.length > 0" class="mt-8 sm:mt-12 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-gray-200 pt-4 sm:pt-6">
        <div class="text-xs sm:text-sm text-gray-600 text-center sm:text-left">
          Showing <span class="font-medium">{{ (currentPage - 1) * itemsPerPage + 1 }}</span> to
          <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, filteredVideos.length) }}</span> of
          <span class="font-medium">{{ filteredVideos.length }}</span> videos
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="prevPage"
            :disabled="currentPage === 1"
            class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg border transition-colors"
            :class="currentPage === 1
              ? 'border-gray-200 text-gray-400 cursor-not-allowed'
              : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Previous
          </button>

          <div class="flex items-center gap-1">
            <template v-for="page in pageNumbers" :key="page">
              <span v-if="page === '...'" class="px-3 py-2 text-gray-500">...</span>
              <button
                v-else
                @click="goToPage(page)"
                class="w-10 h-10 text-sm font-medium rounded-lg transition-colors"
                :class="page === currentPage
                  ? 'bg-orange-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100'"
              >
                {{ page }}
              </button>
            </template>
          </div>

          <button
            @click="nextPage"
            :disabled="currentPage === totalPages"
            class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg border transition-colors"
            :class="currentPage === totalPages
              ? 'border-gray-200 text-gray-400 cursor-not-allowed'
              : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
          >
            Next
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!loading && filteredVideos.length === 0" class="text-center py-24">
        <div class="w-20 h-20 mx-auto mb-8 bg-gray-100 rounded-full flex items-center justify-center">
          <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ videos.length === 0 ? 'No videos yet' : 'No videos match your filter' }}</h3>
        <p class="text-gray-600 mb-8">{{ videos.length === 0 ? 'Start creating your first screen recording' : 'Try adjusting your date filter' }}</p>
        <button
          v-if="videos.length === 0"
          @click="goToRecord"
          class="inline-flex items-center px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium shadow-sm transition-colors duration-200"
        >
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <circle cx="10" cy="10" r="6"/>
          </svg>
          Record Your First Video
        </button>
        <button
          v-else
          @click="clearFilters"
          class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium shadow-sm transition-colors duration-200"
        >
          Clear Filters
        </button>
      </div>
    </main>

    <!-- Delete Video Modal -->
    <SBDeleteModal
      v-model="showDeleteModal"
      title="Delete Video"
      :message="`Are you sure you want to delete '${videoToDelete?.title}'? This cannot be undone.`"
      :loading="isDeleting"
      @confirm="confirmDeleteVideo"
      @cancel="showDeleteModal = false"
    />

    <!-- Upgrade Modal -->
    <SBUpgradeModal
      :show="showUpgradeModal"
      @close="showUpgradeModal = false"
      @success="handleUpgradeSuccess"
    />

    <!-- Recording Modal -->
    <SBRecordingModal
      :show="showRecordingModal"
      @close="showRecordingModal = false"
      @recording-complete="handleRecordingComplete"
      @upgrade="handleRecordingUpgrade"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useAuth } from '@/stores/auth'
import videoService from '@/services/videoService'
import toast from '@/services/toastService'
import SBDeleteModal from '@/components/Global/SBDeleteModal.vue'
import SBUpgradeModal from '@/components/Global/SBUpgradeModal.vue'
import SBRecordingModal from '@/components/Global/SBRecordingModal.vue'

export default {
  name: 'VideosView',
  components: {
    SBDeleteModal,
    SBUpgradeModal,
    SBRecordingModal
  },
  setup() {
    const auth = useAuth()
    const currentUser = computed(() => auth.user.value)
    const userInitial = computed(() => (currentUser.value?.name || 'U').charAt(0).toUpperCase())

    // Subscription state
    const subscription = computed(() => auth.subscription.value)
    const canRecord = computed(() => {
      if (!subscription.value) return true // Allow if not loaded yet
      return subscription.value.can_record
    })

    const videos = ref([])
    const viewMode = ref('grid')
    const loading = ref(false)
    const error = ref(null)

    // Delete modal state
    const showDeleteModal = ref(false)
    const videoToDelete = ref(null)
    const isDeleting = ref(false)

    // Upgrade modal state
    const showUpgradeModal = ref(false)

    // Recording modal state
    const showRecordingModal = ref(false)

    // Sort state
    const sortBy = ref('date_desc')
    const showSortDropdown = ref(false)
    const sortDropdownRef = ref(null)

    const sortOptions = [
      { id: 'date_desc', label: 'Newest First' },
      { id: 'date_asc', label: 'Oldest First' },
      { id: 'title_asc', label: 'Title A-Z' },
      { id: 'title_desc', label: 'Title Z-A' },
      { id: 'duration_desc', label: 'Longest First' },
      { id: 'duration_asc', label: 'Shortest First' },
      { id: 'views_desc', label: 'Most Viewed' },
      { id: 'reactions_desc', label: 'Most Reactions' }
    ]

    const currentSortLabel = computed(() => {
      const option = sortOptions.find(o => o.id === sortBy.value)
      return option ? option.label : 'Newest First'
    })

    // Date filter state
    const activeDateFilter = ref('all')
    const customDateFrom = ref('')
    const customDateTo = ref('')

    const dateFilters = [
      { id: 'all', label: 'All' },
      { id: 'today', label: 'Today' },
      { id: 'yesterday', label: 'Yesterday' },
      { id: 'week', label: 'This Week' },
      { id: 'month', label: 'This Month' },
      { id: 'custom', label: 'Custom' }
    ]

    // Pagination
    const currentPage = ref(1)
    const itemsPerPage = 15

    // Filtered and sorted videos
    const filteredVideos = computed(() => {
      let result = [...videos.value]

      // Apply date filter
      const now = new Date()
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
      const yesterday = new Date(today)
      yesterday.setDate(yesterday.getDate() - 1)

      if (activeDateFilter.value === 'today') {
        result = result.filter(v => v.createdAt >= today)
      } else if (activeDateFilter.value === 'yesterday') {
        result = result.filter(v => v.createdAt >= yesterday && v.createdAt < today)
      } else if (activeDateFilter.value === 'week') {
        const weekAgo = new Date(today)
        weekAgo.setDate(weekAgo.getDate() - 7)
        result = result.filter(v => v.createdAt >= weekAgo)
      } else if (activeDateFilter.value === 'month') {
        const monthAgo = new Date(today)
        monthAgo.setMonth(monthAgo.getMonth() - 1)
        result = result.filter(v => v.createdAt >= monthAgo)
      } else if (activeDateFilter.value === 'custom' && customDateFrom.value && customDateTo.value) {
        const from = new Date(customDateFrom.value)
        const to = new Date(customDateTo.value)
        to.setHours(23, 59, 59, 999)
        result = result.filter(v => v.createdAt >= from && v.createdAt <= to)
      }

      // Apply sorting
      result.sort((a, b) => {
        switch (sortBy.value) {
          case 'date_desc':
            return b.createdAt - a.createdAt
          case 'date_asc':
            return a.createdAt - b.createdAt
          case 'title_asc':
            return a.title.localeCompare(b.title)
          case 'title_desc':
            return b.title.localeCompare(a.title)
          case 'duration_desc':
            return (b.duration || 0) - (a.duration || 0)
          case 'duration_asc':
            return (a.duration || 0) - (b.duration || 0)
          case 'views_desc':
            return (b.views_count || 0) - (a.views_count || 0)
          case 'reactions_desc':
            return (b.reactions_count || 0) - (a.reactions_count || 0)
          default:
            return b.createdAt - a.createdAt
        }
      })

      return result
    })

    // Reset page when filters change
    watch([activeDateFilter, customDateFrom, customDateTo, sortBy], () => {
      currentPage.value = 1
    })

    const totalPages = computed(() => Math.ceil(filteredVideos.value.length / itemsPerPage))

    const paginatedVideos = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage
      const end = start + itemsPerPage
      return filteredVideos.value.slice(start, end)
    })

    const showPagination = computed(() => filteredVideos.value.length > itemsPerPage)

    const goToPage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    }

    const nextPage = () => {
      if (currentPage.value < totalPages.value) {
        goToPage(currentPage.value + 1)
      }
    }

    const prevPage = () => {
      if (currentPage.value > 1) {
        goToPage(currentPage.value - 1)
      }
    }

    const pageNumbers = computed(() => {
      const pages = []
      const total = totalPages.value
      const current = currentPage.value

      if (total <= 7) {
        for (let i = 1; i <= total; i++) {
          pages.push(i)
        }
      } else {
        pages.push(1)
        if (current > 3) pages.push('...')
        for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
          pages.push(i)
        }
        if (current < total - 2) pages.push('...')
        pages.push(total)
      }

      return pages
    })

    const setDateFilter = (filterId) => {
      activeDateFilter.value = filterId
      if (filterId !== 'custom') {
        customDateFrom.value = ''
        customDateTo.value = ''
      }
    }

    const setSortOption = (optionId) => {
      sortBy.value = optionId
      showSortDropdown.value = false
    }

    const clearFilters = () => {
      activeDateFilter.value = 'all'
      customDateFrom.value = ''
      customDateTo.value = ''
      sortBy.value = 'date_desc'
    }

    // Close dropdown when clicking outside
    const handleClickOutside = (event) => {
      if (sortDropdownRef.value && !sortDropdownRef.value.contains(event.target)) {
        showSortDropdown.value = false
      }
    }

    const fetchVideos = async () => {
      loading.value = true
      currentPage.value = 1
      error.value = null

      try {
        const fetchedVideos = await videoService.getVideos()
        videos.value = fetchedVideos.map(video => ({
          id: video.id,
          title: video.title,
          duration: video.duration,
          createdAt: new Date(video.created_at),
          thumbnail: video.thumbnail || null,
          views_count: video.views_count || 0,
          comments_count: video.comments_count || 0,
          reactions_count: video.reactions_count || 0,
          url: video.url,
          isPublic: video.is_public,
          shareUrl: video.share_url
        }))
      } catch (err) {
        console.error('Failed to fetch videos:', err)
        error.value = 'Failed to load videos. Please try again.'
        videos.value = []
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      fetchVideos()
      auth.fetchSubscription() // Fetch subscription status
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    const goToRecord = () => {
      showRecordingModal.value = true
    }

    const handleRecordingComplete = (video) => {
      // Video recorded successfully, will redirect to video page
      showRecordingModal.value = false
    }

    const handleRecordingUpgrade = () => {
      // User clicked upgrade in recording modal, open upgrade modal
      showRecordingModal.value = false
      showUpgradeModal.value = true
    }

    const openVideo = (id) => {
      window.location.href = `/video/${id}`
    }

    const shareVideo = async (video) => {
      if (video.shareUrl) {
        try {
          await navigator.clipboard.writeText(video.shareUrl)
          toast.success('Share link copied to clipboard!')
        } catch (err) {
          console.error('Failed to copy:', err)
          toast.error('Failed to copy link. Please try again.')
        }
      }
    }

    const downloadVideo = async (video) => {
      if (!video.url) return

      try {
        toast.success('Starting download...')

        // Fetch the video as a blob
        const response = await fetch(video.url)
        const blob = await response.blob()

        // Create a blob URL and trigger download
        const blobUrl = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = blobUrl
        link.download = `${video.title || 'video'}.webm`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)

        // Clean up the blob URL
        window.URL.revokeObjectURL(blobUrl)

        toast.success('Download complete!')
      } catch (err) {
        console.error('Failed to download:', err)
        toast.error('Failed to download video. Please try again.')
      }
    }

    const deleteVideo = (video) => {
      videoToDelete.value = video
      showDeleteModal.value = true
    }

    const confirmDeleteVideo = async () => {
      if (!videoToDelete.value) return

      isDeleting.value = true
      try {
        await videoService.deleteVideo(videoToDelete.value.id)
        videos.value = videos.value.filter(v => v.id !== videoToDelete.value.id)
        toast.success('Video deleted successfully!')
        showDeleteModal.value = false
        videoToDelete.value = null
      } catch (err) {
        console.error('Failed to delete video:', err)
        toast.error('Failed to delete video. Please try again.')
      } finally {
        isDeleting.value = false
      }
    }

    const handleUpgradeSuccess = () => {
      showUpgradeModal.value = false
      auth.fetchSubscription() // Refresh subscription status
    }

    const openUpgradeModal = () => {
      showUpgradeModal.value = true
    }

    const formatDuration = (seconds) => {
      if (!seconds || isNaN(seconds)) return '0:00'
      const mins = Math.floor(seconds / 60)
      const secs = Math.floor(seconds % 60)
      return `${mins}:${secs.toString().padStart(2, '0')}`
    }

    const formatDate = (date) => {
      const now = new Date()
      const diffTime = Math.abs(now - date)
      const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))

      if (diffDays === 0) return 'Today'
      if (diffDays === 1) return 'Yesterday'
      if (diffDays < 7) return `${diffDays} days ago`
      if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`
      if (diffDays < 365) return `${Math.floor(diffDays / 30)} month${Math.floor(diffDays / 30) > 1 ? 's' : ''} ago`
      return `${Math.floor(diffDays / 365)} year${Math.floor(diffDays / 365) > 1 ? 's' : ''} ago`
    }

    return {
      currentUser,
      userInitial,
      subscription,
      canRecord,
      videos,
      filteredVideos,
      paginatedVideos,
      viewMode,
      loading,
      error,
      // Sort
      sortBy,
      showSortDropdown,
      sortDropdownRef,
      sortOptions,
      currentSortLabel,
      setSortOption,
      // Date filter
      activeDateFilter,
      customDateFrom,
      customDateTo,
      dateFilters,
      setDateFilter,
      clearFilters,
      // Pagination
      currentPage,
      itemsPerPage,
      totalPages,
      showPagination,
      pageNumbers,
      goToPage,
      nextPage,
      prevPage,
      // Methods
      fetchVideos,
      goToRecord,
      openVideo,
      shareVideo,
      downloadVideo,
      deleteVideo,
      confirmDeleteVideo,
      formatDuration,
      formatDate,
      // Delete modal
      showDeleteModal,
      videoToDelete,
      isDeleting,
      // Upgrade modal
      showUpgradeModal,
      handleUpgradeSuccess,
      openUpgradeModal,
      // Recording modal
      showRecordingModal,
      handleRecordingComplete,
      handleRecordingUpgrade
    }
  }
}
</script>
