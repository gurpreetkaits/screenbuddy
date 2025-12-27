<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-orange-500 border-t-transparent"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <div class="w-20 h-20 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
          <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ error }}</h3>
        <a href="/" class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-colors inline-block">
          Go Home
        </a>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else class="h-screen flex flex-col p-2 sm:p-4">
      <!-- Top Navigation -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0 mb-3 sm:mb-4 flex-shrink-0">
        <a href="/" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors">
          <svg class="w-8 h-8 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/>
          </svg>
          <span class="font-bold text-xl text-gray-900">ScreenSense</span>
        </a>

        <button
          @click="copyShareLink"
          class="flex items-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-sm font-medium transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
          </svg>
          {{ copied ? 'Copied!' : 'Share' }}
        </button>
      </div>

      <div class="flex flex-col lg:flex-row gap-4 sm:gap-6 flex-1 min-h-0">
        <!-- Video Section -->
        <div class="flex-1 flex flex-col min-w-0 min-h-0">
          <!-- Video Player -->
          <div
            class="relative rounded-2xl overflow-hidden bg-black shadow-2xl"
            @mousemove="showControls"
            @mouseleave="hideControlsDelayed"
            ref="playerContainer"
          >
            <video
              ref="videoRef"
              class="w-full h-full object-contain max-h-[90vh]"
              :src="video.url"
              preload="metadata"
              @click="togglePlay"
              @dblclick="toggleFullscreen"
              @timeupdate="updateProgress"
              @loadedmetadata="onVideoLoaded"
              @loadeddata="onVideoLoaded"
              @durationchange="onVideoLoaded"
              @canplay="onVideoLoaded"
              @ended="onVideoEnded"
              @seeking="isBuffering = true"
              @seeked="isBuffering = false"
              @waiting="isBuffering = true"
              @play="isPlaying = true"
              @pause="isPlaying = false"
              @error="onVideoError"
              playsinline
            ></video>

            <!-- Buffering -->
            <div v-if="isBuffering" class="absolute inset-0 flex items-center justify-center bg-black/40 pointer-events-none">
              <div class="w-14 h-14 border-4 border-white/20 border-t-orange-500 rounded-full animate-spin"></div>
            </div>

            <!-- Big Play Button -->
            <transition name="fade">
              <div
                v-if="!isPlaying && !isBuffering && showBigPlayButton"
                class="absolute inset-0 flex items-center justify-center bg-black/30 cursor-pointer"
                @click="togglePlay"
              >
                <div class="w-20 h-20 rounded-full bg-orange-600 hover:bg-orange-500 flex items-center justify-center shadow-2xl transform hover:scale-110 transition-all">
                  <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                  </svg>
                </div>
              </div>
            </transition>

            <!-- Skip Indicators -->
            <transition name="skip-left">
              <div v-if="showSkipBack" class="absolute left-8 top-1/2 -translate-y-1/2 pointer-events-none">
                <div class="flex flex-col items-center text-white bg-black/70 backdrop-blur-sm rounded-full p-4 shadow-xl">
                  <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M11 18V6l-8.5 6 8.5 6zm.5-6l8.5 6V6l-8.5 6z"/>
                  </svg>
                  <span class="text-sm font-bold mt-1">{{ skipBackAmount }}s</span>
                </div>
              </div>
            </transition>

            <transition name="skip-right">
              <div v-if="showSkipForward" class="absolute right-8 top-1/2 -translate-y-1/2 pointer-events-none">
                <div class="flex flex-col items-center text-white bg-black/70 backdrop-blur-sm rounded-full p-4 shadow-xl">
                  <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M4 18l8.5-6L4 6v12zm9-12v12l8.5-6L13 6z"/>
                  </svg>
                  <span class="text-sm font-bold mt-1">{{ skipForwardAmount }}s</span>
                </div>
              </div>
            </transition>

            <!-- Controls -->
            <transition name="fade">
              <div
                v-show="controlsVisible || !isPlaying"
                class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent pt-16 pb-4 px-4"
              >
                <!-- Progress Bar -->
                <div
                  class="progress-bar group relative h-2 bg-white/30 rounded-full mb-4 cursor-pointer hover:h-3 transition-all"
                  @click.stop="seek"
                  @mousedown.stop="startSeeking"
                  @mousemove="updateHoverTime"
                  @mouseleave="hoverTime = null"
                  ref="progressBar"
                >
                  <div class="absolute top-0 left-0 h-full bg-white/40 rounded-full pointer-events-none" :style="{ width: bufferedPercent + '%' }"></div>
                  <div class="absolute top-0 left-0 h-full bg-orange-500 rounded-full pointer-events-none" :style="{ width: progressPercent + '%' }"></div>
                  <div
                    class="absolute top-1/2 -translate-y-1/2 w-4 h-4 bg-orange-500 rounded-full shadow-lg scale-0 group-hover:scale-100 transition-transform pointer-events-none"
                    :style="{ left: `calc(${progressPercent}% - 8px)` }"
                  ></div>
                  <div
                    v-if="hoverTime !== null"
                    class="absolute -top-10 px-2 py-1 bg-black text-white text-xs rounded transform -translate-x-1/2 pointer-events-none"
                    :style="{ left: hoverPercent + '%' }"
                  >
                    {{ formatTime(hoverTime) }}
                  </div>
                </div>

                <!-- Controls Row -->
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <!-- Play/Pause -->
                    <button @click.stop="togglePlay" class="w-10 h-10 flex items-center justify-center text-white hover:text-orange-400 transition-colors">
                      <svg v-if="isPlaying" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                      </svg>
                      <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                      </svg>
                    </button>

                    <!-- Rewind 5s -->
                    <button @click.stop="skip(-5)" class="relative w-10 h-10 flex items-center justify-center text-white/80 hover:text-white transition-colors group" title="Rewind 5s">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/>
                      </svg>
                      <span class="absolute -bottom-0.5 text-[9px] font-bold">5</span>
                    </button>

                    <!-- Forward 5s -->
                    <button @click.stop="skip(5)" class="relative w-10 h-10 flex items-center justify-center text-white/80 hover:text-white transition-colors group" title="Forward 5s">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"/>
                      </svg>
                      <span class="absolute -bottom-0.5 text-[9px] font-bold">5</span>
                    </button>

                    <!-- Volume -->
                    <div class="flex items-center gap-1 group/vol">
                      <button @click.stop="toggleMute" class="w-10 h-10 flex items-center justify-center text-white/80 hover:text-white transition-colors">
                        <svg v-if="isMuted || volume === 0" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                        </svg>
                      </button>
                      <input
                        type="range" min="0" max="1" step="0.05" v-model="volume" @input="updateVolume"
                        class="w-0 group-hover/vol:w-20 opacity-0 group-hover/vol:opacity-100 transition-all accent-orange-500"
                      />
                    </div>

                    <!-- Time -->
                    <span class="text-white/80 text-sm ml-2">
                      {{ formatTime(currentTime) }} / {{ formatTime(duration) }}
                    </span>
                  </div>

                  <div class="flex items-center gap-2">
                    <!-- Speed -->
                    <div class="relative" ref="speedMenuRef">
                      <button
                        @click.stop.prevent="toggleSpeedMenu"
                        class="px-3 py-1.5 text-white/80 hover:text-white text-sm font-medium rounded hover:bg-white/10 transition-colors"
                      >
                        {{ playbackSpeed }}x
                      </button>
                      <div
                        v-show="showSpeedMenu"
                        class="absolute bottom-full right-0 mb-2 py-2 bg-gray-900 rounded-xl shadow-2xl border border-white/20 min-w-[120px] z-50"
                      >
                        <button
                          v-for="speed in speedOptions"
                          :key="speed"
                          @click.stop.prevent="setPlaybackSpeed(speed)"
                          class="w-full px-4 py-2.5 text-left text-sm hover:bg-white/10 transition-colors flex items-center justify-between"
                          :class="playbackSpeed === speed ? 'text-orange-400 bg-white/5' : 'text-white'"
                        >
                          <span>{{ speed }}x</span>
                          <svg v-if="playbackSpeed === speed" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                          </svg>
                        </button>
                      </div>
                    </div>

                    <!-- PiP -->
                    <button @click.stop="togglePiP" class="w-10 h-10 flex items-center justify-center text-white/80 hover:text-white transition-colors" title="Picture in Picture">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                        <rect x="11" y="10" width="9" height="6" rx="1" fill="currentColor"/>
                      </svg>
                    </button>

                    <!-- Fullscreen -->
                    <button @click.stop="toggleFullscreen" class="w-10 h-10 flex items-center justify-center text-white/80 hover:text-white transition-colors" title="Fullscreen (F)">
                      <svg v-if="!isFullscreen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                      </svg>
                      <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </transition>
          </div>

          <!-- Video Info -->
          <div class="mt-3 sm:mt-4 flex-shrink-0">
            <h1 class="text-base sm:text-lg font-bold text-gray-900">{{ video.title }}</h1>
            <div class="flex items-center gap-2 sm:gap-3 mt-1 text-gray-500 text-xs sm:text-sm">
              <span>{{ formatDate(video.created_at) }}</span>
              <span>•</span>
              <span>{{ formatTime(video.duration) }}</span>
            </div>

            <!-- Engagement Stats -->
            <div class="flex items-center gap-4 mt-3 text-sm text-gray-400">
              <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                </svg>
                <span>{{ totalReactions }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span>{{ comments.length }}</span>
              </div>
            </div>

            <!-- Reactions -->
            <div class="flex flex-wrap items-center gap-2 mt-3">
              <button
                v-for="(data, type) in reactions"
                :key="type"
                @click="toggleReaction(type)"
                class="flex items-center gap-1 px-2 py-1 rounded-full transition-all text-xs sm:text-sm"
                :class="userReactions.includes(type) ? 'bg-orange-600 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
              >
                <span>{{ data.emoji }}</span>
                <span v-if="data.count" class="text-xs">{{ data.count }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Comments Sidebar -->
        <div class="w-full lg:w-80 flex-shrink-0 flex flex-col min-h-0 lg:max-h-screen">
          <div class="bg-white border border-gray-200 rounded-2xl p-3 sm:p-4 flex flex-col flex-1 min-h-0 max-h-96 lg:max-h-full shadow-sm">
            <h2 class="text-base font-semibold text-gray-900 mb-3 flex-shrink-0">Comments ({{ comments.length }})</h2>

            <!-- Comment Input (Authenticated users only) -->
            <div v-if="isAuthenticated" class="flex gap-3 mb-4 flex-shrink-0">
              <img
                v-if="currentUser?.avatar"
                :src="currentUser.avatar"
                :alt="currentUser.name"
                class="w-7 h-7 rounded-full object-cover flex-shrink-0"
              />
              <div v-else class="w-7 h-7 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                {{ userInitial }}
              </div>
              <div class="flex-1">
                <textarea
                  v-model="newComment"
                  placeholder="Add a comment..."
                  rows="2"
                  @keydown.enter.exact.prevent="addComment"
                  class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 resize-none"
                ></textarea>
                <div class="flex justify-end mt-2">
                  <button
                    @click="addComment"
                    :disabled="!newComment.trim() || isSavingComment"
                    class="px-4 py-1.5 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition-colors"
                  >
                    {{ isSavingComment ? 'Saving...' : 'Comment' }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Sign In Prompt (Non-authenticated users) -->
            <div v-else class="mb-4 p-4 bg-gray-50 rounded-xl border border-gray-200 flex-shrink-0">
              <p class="text-gray-500 text-sm mb-3">Sign in to leave a comment</p>
              <button
                @click="loginToComment"
                class="flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-white rounded-lg hover:bg-gray-100 transition-colors border border-gray-200"
              >
                <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                  <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                  <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                  <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                  <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Sign in with Google
              </button>
            </div>

            <!-- Comments List -->
            <div class="flex-1 overflow-y-auto min-h-0">
              <div v-if="comments.length === 0" class="text-center py-6 text-gray-400">
                <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <p class="text-xs">No comments yet</p>
                <p class="text-xs mt-1">Be the first to comment!</p>
              </div>

              <div v-else class="space-y-3">
                <div v-for="comment in comments" :key="comment.id" class="flex gap-2">
                  <img
                    v-if="comment.author_avatar"
                    :src="comment.author_avatar"
                    :alt="comment.author_name"
                    class="w-7 h-7 rounded-full object-cover flex-shrink-0"
                  />
                  <div v-else class="w-7 h-7 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                    {{ comment.author_name.charAt(0).toUpperCase() }}
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <span class="text-gray-900 text-xs font-medium">{{ comment.author_name }}</span>
                      <span class="text-gray-400 text-xs">{{ formatCommentTime(comment.created_at) }}</span>
                    </div>
                    <p class="text-gray-600 text-xs mt-0.5">{{ comment.content }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Keyboard Shortcuts -->
      <div class="flex items-center justify-center gap-6 mt-4 text-gray-400 text-xs flex-shrink-0">
        <span><kbd class="px-1.5 py-0.5 bg-gray-100 rounded border">Space</kbd> Play/Pause</span>
        <span><kbd class="px-1.5 py-0.5 bg-gray-100 rounded border">←</kbd><kbd class="px-1.5 py-0.5 bg-gray-100 rounded border ml-1">→</kbd> Skip 5s</span>
        <span><kbd class="px-1.5 py-0.5 bg-gray-100 rounded border">F</kbd> Fullscreen</span>
        <span><kbd class="px-1.5 py-0.5 bg-gray-100 rounded border">M</kbd> Mute</span>
      </div>
    </div>

    <!-- Toast -->
    <transition name="toast">
      <div
        v-if="toast"
        class="fixed bottom-8 left-1/2 -translate-x-1/2 px-5 py-3 bg-white text-gray-900 rounded-xl text-sm font-medium shadow-2xl border border-gray-200 flex items-center gap-2"
      >
        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        {{ toast }}
      </div>
    </transition>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/stores/auth'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8888'

export default {
  name: 'SharedVideoView',
  setup() {
    const route = useRoute()
    const auth = useAuth()
    const token = computed(() => route.params.token)
    const isAuthenticated = computed(() => auth.isAuthenticated.value)
    const currentUser = computed(() => auth.user.value)
    const userInitial = computed(() => (currentUser.value?.name || 'U').charAt(0).toUpperCase())

    const video = ref({})
    const loading = ref(true)
    const error = ref(null)
    const comments = ref([])
    const reactions = ref({})
    const userReactions = ref([])

    const videoRef = ref(null)
    const progressBar = ref(null)
    const speedMenuRef = ref(null)
    const playerContainer = ref(null)

    const isPlaying = ref(false)
    const isBuffering = ref(false)
    const isMuted = ref(false)
    const isFullscreen = ref(false)
    const volume = ref(1)
    const currentTime = ref(0)
    const duration = ref(0)
    const bufferedPercent = ref(0)
    const playbackSpeed = ref(1)
    const speedOptions = [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2]
    const controlsVisible = ref(true)
    const hoverTime = ref(null)
    const hoverPercent = ref(0)
    const showSpeedMenu = ref(false)
    const showBigPlayButton = ref(true)
    const copied = ref(false)
    const toast = ref(null)
    const showSkipBack = ref(false)
    const showSkipForward = ref(false)
    const skipBackAmount = ref(5)
    const skipForwardAmount = ref(5)

    const newComment = ref('')
    const isSavingComment = ref(false)

    let controlsTimeout = null
    let toastTimeout = null
    let skipTimeout = null

    const progressPercent = computed(() => {
      if (!duration.value) return 0
      return (currentTime.value / duration.value) * 100
    })

    const totalReactions = computed(() => {
      return Object.values(reactions.value).reduce((sum, r) => sum + (r.count || 0), 0)
    })

    const sessionId = ref(localStorage.getItem('screensense_session') || Math.random().toString(36).substring(2))
    if (!localStorage.getItem('screensense_session')) {
      localStorage.setItem('screensense_session', sessionId.value)
    }

    const fetchVideo = async () => {
      loading.value = true
      try {
        const response = await fetch(`${API_BASE_URL}/api/share/video/${token.value}`)
        if (!response.ok) throw new Error('Video not available')
        const data = await response.json()
        video.value = data.video
        comments.value = data.video.comments || []
        reactions.value = data.video.reactions || {}
        duration.value = data.video.duration || 0
      } catch (err) {
        error.value = err.message || 'Failed to load video'
      } finally {
        loading.value = false
      }
    }

    const togglePlay = () => {
      if (!videoRef.value) return
      if (isPlaying.value) {
        videoRef.value.pause()
      } else {
        videoRef.value.play()
        showBigPlayButton.value = false
      }
    }

    const updateProgress = () => {
      if (!videoRef.value) return
      currentTime.value = videoRef.value.currentTime
      if (videoRef.value.buffered.length > 0) {
        bufferedPercent.value = (videoRef.value.buffered.end(videoRef.value.buffered.length - 1) / duration.value) * 100
      }
    }

    const onVideoLoaded = () => {
      if (!videoRef.value) return
      const videoDuration = videoRef.value.duration
      const apiDuration = video.value.duration

      if (isFinite(videoDuration) && videoDuration > 0) {
        duration.value = videoDuration
        isBuffering.value = false
      } else if (apiDuration && apiDuration > 0) {
        duration.value = apiDuration
        isBuffering.value = false
      }

      if (videoRef.value) {
        videoRef.value.playbackRate = playbackSpeed.value
      }
    }

    const onVideoError = (event) => {
      console.error('Video error:', event)
      isBuffering.value = false
    }

    const onVideoEnded = () => {
      isPlaying.value = false
      showBigPlayButton.value = true
    }

    const seekToPosition = (clientX) => {
      const video = videoRef.value
      const bar = progressBar.value
      if (!video || !bar) return

      const videoDuration = isFinite(video.duration) && video.duration > 0
        ? video.duration
        : duration.value

      if (!videoDuration || !isFinite(videoDuration) || videoDuration <= 0) return

      const rect = bar.getBoundingClientRect()
      const percent = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width))
      const newTime = percent * videoDuration
      video.currentTime = newTime
      currentTime.value = newTime
    }

    const seek = (e) => {
      seekToPosition(e.clientX)
    }

    const startSeeking = (e) => {
      e.preventDefault()
      const video = videoRef.value
      if (!video) return

      const wasPlaying = !video.paused
      if (wasPlaying) video.pause()

      const onMouseMove = (moveEvent) => {
        seekToPosition(moveEvent.clientX)
      }

      const onMouseUp = () => {
        document.removeEventListener('mousemove', onMouseMove)
        document.removeEventListener('mouseup', onMouseUp)
        if (wasPlaying && video) video.play()
      }

      document.addEventListener('mousemove', onMouseMove)
      document.addEventListener('mouseup', onMouseUp)
      seekToPosition(e.clientX)
    }

    const updateHoverTime = (e) => {
      if (!progressBar.value) return
      const rect = progressBar.value.getBoundingClientRect()
      const percent = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width))
      hoverPercent.value = percent * 100
      hoverTime.value = percent * duration.value
    }

    const skip = (seconds) => {
      const video = videoRef.value
      if (!video) return

      const videoDuration = isFinite(video.duration) && video.duration > 0
        ? video.duration
        : duration.value

      if (!videoDuration || !isFinite(videoDuration) || videoDuration <= 0) return

      const currentVideoTime = video.currentTime || 0
      const newTime = Math.max(0, Math.min(videoDuration, currentVideoTime + seconds))
      video.currentTime = newTime
      currentTime.value = newTime

      const actualSkip = Math.abs(Math.round(seconds))
      if (seconds < 0) {
        skipBackAmount.value = actualSkip
        showSkipBack.value = true
        if (skipTimeout) clearTimeout(skipTimeout)
        skipTimeout = setTimeout(() => { showSkipBack.value = false }, 600)
      } else if (seconds > 0) {
        skipForwardAmount.value = actualSkip
        showSkipForward.value = true
        if (skipTimeout) clearTimeout(skipTimeout)
        skipTimeout = setTimeout(() => { showSkipForward.value = false }, 600)
      }
    }

    const toggleMute = () => {
      if (!videoRef.value) return
      isMuted.value = !isMuted.value
      videoRef.value.muted = isMuted.value
    }

    const updateVolume = () => {
      if (!videoRef.value) return
      videoRef.value.volume = volume.value
      isMuted.value = volume.value === 0
    }

    const toggleSpeedMenu = () => {
      showSpeedMenu.value = !showSpeedMenu.value
    }

    const setPlaybackSpeed = (speed) => {
      playbackSpeed.value = speed
      showSpeedMenu.value = false
      if (videoRef.value) {
        videoRef.value.playbackRate = speed
      }
    }

    const toggleFullscreen = async () => {
      try {
        if (!document.fullscreenElement) {
          await playerContainer.value.requestFullscreen()
          isFullscreen.value = true
        } else {
          await document.exitFullscreen()
          isFullscreen.value = false
        }
      } catch (err) {}
    }

    const togglePiP = async () => {
      try {
        if (document.pictureInPictureElement) {
          await document.exitPictureInPicture()
        } else if (videoRef.value) {
          await videoRef.value.requestPictureInPicture()
        }
      } catch (err) {}
    }

    const showControls = () => {
      controlsVisible.value = true
      if (controlsTimeout) clearTimeout(controlsTimeout)
    }

    const hideControlsDelayed = () => {
      if (controlsTimeout) clearTimeout(controlsTimeout)
      controlsTimeout = setTimeout(() => {
        if (isPlaying.value && !showSpeedMenu.value) controlsVisible.value = false
      }, 3000)
    }

    const formatTime = (seconds) => {
      if (!seconds || isNaN(seconds) || !isFinite(seconds)) return '0:00'
      const mins = Math.floor(seconds / 60)
      const secs = Math.floor(seconds % 60)
      return `${mins}:${secs.toString().padStart(2, '0')}`
    }

    const formatDate = (dateStr) => {
      if (!dateStr) return ''
      const date = new Date(dateStr)
      return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
    }

    const formatCommentTime = (dateStr) => {
      if (!dateStr) return ''
      const date = new Date(dateStr)
      const now = new Date()
      const diff = now - date
      const minutes = Math.floor(diff / 60000)
      if (minutes < 1) return 'Just now'
      if (minutes < 60) return `${minutes}m ago`
      const hours = Math.floor(minutes / 60)
      if (hours < 24) return `${hours}h ago`
      const days = Math.floor(hours / 24)
      return `${days}d ago`
    }

    const showToast = (msg) => {
      toast.value = msg
      if (toastTimeout) clearTimeout(toastTimeout)
      toastTimeout = setTimeout(() => { toast.value = null }, 2000)
    }

    const copyShareLink = async () => {
      try {
        await navigator.clipboard.writeText(window.location.href)
        copied.value = true
        showToast('Link copied!')
        setTimeout(() => { copied.value = false }, 3000)
      } catch (err) {}
    }

    const toggleReaction = async (type) => {
      try {
        const response = await fetch(`${API_BASE_URL}/api/share/video/${token.value}/reactions`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ type, session_id: sessionId.value })
        })
        const data = await response.json()

        if (data.action === 'added') {
          userReactions.value.push(type)
          if (reactions.value[type]) reactions.value[type].count++
        } else {
          userReactions.value = userReactions.value.filter(r => r !== type)
          if (reactions.value[type]) reactions.value[type].count--
        }
      } catch (err) {
        console.error('Failed to toggle reaction:', err)
      }
    }

    const addComment = async () => {
      if (!newComment.value.trim() || !isAuthenticated.value || isSavingComment.value) return

      isSavingComment.value = true
      const commentText = newComment.value.trim()
      newComment.value = ''

      try {
        const response = await fetch(`${API_BASE_URL}/api/share/video/${token.value}/comments`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${auth.token.value}`
          },
          body: JSON.stringify({
            content: commentText,
            author_name: currentUser.value?.name || 'Anonymous'
          })
        })
        const data = await response.json()
        comments.value.unshift(data.comment)
        showToast('Comment added!')
      } catch (err) {
        console.error('Failed to add comment:', err)
        newComment.value = commentText
        showToast('Failed to save comment')
      } finally {
        isSavingComment.value = false
      }
    }

    const loginToComment = () => {
      localStorage.setItem('auth_redirect', window.location.pathname)
      auth.loginWithGoogle()
    }

    const handleKeydown = (e) => {
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return

      const key = e.key

      if (key === 'ArrowLeft') {
        e.preventDefault()
        skip(-5)
        return
      }
      if (key === 'ArrowRight') {
        e.preventDefault()
        skip(5)
        return
      }
      if (key === 'ArrowUp') {
        e.preventDefault()
        volume.value = Math.min(1, Number(volume.value) + 0.1)
        updateVolume()
        showToast(`Volume: ${Math.round(volume.value * 100)}%`)
        return
      }
      if (key === 'ArrowDown') {
        e.preventDefault()
        volume.value = Math.max(0, Number(volume.value) - 0.1)
        updateVolume()
        showToast(`Volume: ${Math.round(volume.value * 100)}%`)
        return
      }

      if (key >= '0' && key <= '9') {
        e.preventDefault()
        const percent = parseInt(key) * 10
        const video = videoRef.value
        if (video && duration.value) {
          const newTime = (percent / 100) * duration.value
          video.currentTime = newTime
          currentTime.value = newTime
          showToast(`Jumped to ${percent}%`)
        }
        return
      }

      switch (key.toLowerCase()) {
        case ' ':
        case 'k':
          e.preventDefault()
          togglePlay()
          break
        case 'f':
          e.preventDefault()
          toggleFullscreen()
          break
        case 'm':
          e.preventDefault()
          toggleMute()
          showToast(isMuted.value ? 'Muted' : 'Unmuted')
          break
        case 'j':
          e.preventDefault()
          skip(-10)
          break
        case 'l':
          e.preventDefault()
          skip(10)
          break
        case 'i':
          e.preventDefault()
          togglePiP()
          break
      }
    }

    const handleClickOutside = (e) => {
      if (speedMenuRef.value && !speedMenuRef.value.contains(e.target)) {
        showSpeedMenu.value = false
      }
    }

    const handleFullscreenChange = () => {
      isFullscreen.value = !!document.fullscreenElement
    }

    onMounted(() => {
      fetchVideo()
      document.addEventListener('keydown', handleKeydown)
      document.addEventListener('click', handleClickOutside)
      document.addEventListener('fullscreenchange', handleFullscreenChange)
    })

    onUnmounted(() => {
      document.removeEventListener('keydown', handleKeydown)
      document.removeEventListener('click', handleClickOutside)
      document.removeEventListener('fullscreenchange', handleFullscreenChange)
      if (controlsTimeout) clearTimeout(controlsTimeout)
      if (toastTimeout) clearTimeout(toastTimeout)
      if (skipTimeout) clearTimeout(skipTimeout)
    })

    return {
      video, loading, error, comments, reactions, userReactions, totalReactions,
      videoRef, progressBar, speedMenuRef, playerContainer,
      isPlaying, isBuffering, isMuted, isFullscreen, volume, currentTime, duration,
      bufferedPercent, progressPercent, playbackSpeed, speedOptions, controlsVisible,
      hoverTime, hoverPercent, showSpeedMenu, showBigPlayButton,
      copied, toast, showSkipBack, showSkipForward, skipBackAmount, skipForwardAmount,
      newComment, isSavingComment, isAuthenticated, currentUser, userInitial,
      togglePlay, updateProgress, onVideoLoaded, onVideoError, onVideoEnded, seek, startSeeking,
      updateHoverTime, skip, toggleMute, updateVolume, toggleSpeedMenu, setPlaybackSpeed,
      toggleFullscreen, togglePiP, showControls, hideControlsDelayed,
      formatTime, formatDate, formatCommentTime, copyShareLink, toggleReaction, addComment, loginToComment
    }
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.toast-enter-active { transition: all 0.3s ease; }
.toast-leave-active { transition: all 0.2s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translate(-50%, 20px); }

.skip-left-enter-active, .skip-left-leave-active,
.skip-right-enter-active, .skip-right-leave-active { transition: all 0.3s ease; }
.skip-left-enter-from, .skip-left-leave-to { opacity: 0; transform: translate(-20px, -50%); }
.skip-right-enter-from, .skip-right-leave-to { opacity: 0; transform: translate(20px, -50%); }

.progress-bar { transition: height 0.15s ease; }
.progress-bar:hover { height: 6px; }

input[type="range"] { -webkit-appearance: none; height: 4px; background: rgba(255,255,255,0.2); border-radius: 2px; }
input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; width: 12px; height: 12px; background: #f97316; border-radius: 50%; cursor: pointer; }
</style>
