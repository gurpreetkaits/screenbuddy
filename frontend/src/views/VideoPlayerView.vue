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
        <button @click="goBack" class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-colors">
          Go Back
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else class="h-screen flex flex-col p-2 sm:p-4">
      <!-- Top Navigation -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0 mb-3 sm:mb-4 flex-shrink-0">
        <button @click="goBack" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          <span class="font-medium">Back</span>
        </button>

        <!-- Video Navigation -->
        <div class="flex items-center gap-3">
          <!-- Previous Video -->
          <button
            @click="goToPreviousVideo"
            :disabled="!hasPrevious"
            class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="hasPrevious
              ? 'bg-gray-200 hover:bg-gray-300 text-gray-700'
              : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
            title="Previous video (P)"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <span class="hidden sm:inline">Previous</span>
          </button>

          <!-- Video Counter -->
          <span v-if="allVideos.length > 0" class="text-gray-500 text-sm px-2">
            {{ currentIndex + 1 }} / {{ allVideos.length }}
          </span>

          <!-- Next Video -->
          <button
            @click="goToNextVideo"
            :disabled="!hasNext"
            class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="hasNext
              ? 'bg-gray-200 hover:bg-gray-300 text-gray-700'
              : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
            title="Next video (N)"
          >
            <span class="hidden sm:inline">Next</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>

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
              :key="video.id"
              class="w-full h-full object-contain max-h-[90vh]"
              :src="video.url"
              preload="metadata"
              crossorigin="use-credentials"
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
                    <button @click.stop="skip(-5)" class="relative w-10 h-10 flex items-center justify-center text-white/80 hover:text-white transition-colors group" title="Rewind 5s (← or J for 10s)">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/>
                      </svg>
                      <span class="absolute -bottom-0.5 text-[9px] font-bold">5</span>
                    </button>

                    <!-- Forward 5s -->
                    <button @click.stop="skip(5)" class="relative w-10 h-10 flex items-center justify-center text-white/80 hover:text-white transition-colors group" title="Forward 5s (→ or L for 10s)">
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
            <!-- Editable Title -->
            <div class="flex items-center gap-2">
              <h1
                v-if="!isEditingTitle"
                @click="startEditingTitle"
                class="text-base sm:text-lg font-bold text-gray-900 cursor-pointer hover:text-orange-600 transition-colors"
                title="Click to edit title"
              >
                {{ video.title }}
                <svg class="w-4 h-4 inline-block ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
              </h1>
              <div v-else class="flex-1 flex items-center gap-2">
                <input
                  ref="titleInput"
                  v-model="editedTitle"
                  @keydown.enter="saveTitle"
                  @keydown.escape="cancelEditingTitle"
                  @blur="saveTitle"
                  class="flex-1 text-base sm:text-lg font-bold text-gray-900 bg-white border border-orange-500 rounded-lg px-3 py-1 focus:outline-none focus:ring-2 focus:ring-orange-500"
                  placeholder="Enter video title..."
                />
                <button
                  @click="saveTitle"
                  :disabled="isSavingTitle"
                  class="p-1.5 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-300 text-white rounded-lg transition-colors"
                  title="Save"
                >
                  <svg v-if="!isSavingTitle" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                  </svg>
                </button>
                <button
                  @click="cancelEditingTitle"
                  class="p-1.5 bg-gray-200 hover:bg-gray-300 text-gray-600 rounded-lg transition-colors"
                  title="Cancel"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>
            <div class="flex items-center gap-2 sm:gap-3 mt-1 text-gray-500 text-xs sm:text-sm">
              <span>{{ formatDate(video.createdAt) }}</span>
              <span>•</span>
              <span>{{ formatTime(video.duration) }}</span>
            </div>

            <!-- Engagement Stats -->
            <div class="flex items-center gap-4 mt-3 text-sm text-gray-400">
              <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                </svg>
                <span>{{ reactions.reduce((sum, r) => sum + r.count, 0) }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span>{{ comments.length }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <span>{{ video.views_count || 0 }}</span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2 mt-3">
              <button
                v-for="emoji in reactions"
                :key="emoji.icon"
                @click="addReaction(emoji.icon)"
                class="flex items-center gap-1 px-2 py-1 rounded-full transition-all text-xs sm:text-sm"
                :class="emoji.selected ? 'bg-orange-600 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
              >
                <span>{{ emoji.icon }}</span>
                <span v-if="emoji.count" class="text-xs">{{ emoji.count }}</span>
              </button>

              <div class="ml-auto flex items-center gap-1 sm:gap-2">
                <button
                  @click="downloadVideo"
                  class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors"
                  title="Download"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                  </svg>
                </button>

                <button
                  @click="deleteVideo"
                  class="p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-colors"
                  title="Delete"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Comments Sidebar -->
        <div class="w-full lg:w-80 flex-shrink-0 flex flex-col min-h-0 lg:max-h-screen">
          <div class="bg-white border border-gray-200 rounded-2xl p-3 sm:p-4 flex flex-col flex-1 min-h-0 max-h-96 lg:max-h-full shadow-sm">
            <h2 class="text-base font-semibold text-gray-900 mb-3 flex-shrink-0">Comments</h2>

            <!-- Comment Input -->
            <div class="flex gap-3 mb-4 flex-shrink-0">
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

            <!-- Comments List -->
            <div class="flex-1 overflow-y-auto min-h-0">
              <div v-if="comments.length === 0" class="text-center py-6 text-gray-400">
                <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <p class="text-xs">No comments yet</p>
              </div>

              <div v-else class="space-y-3">
                <div v-for="comment in comments" :key="comment.id" class="flex gap-2">
                  <img
                    v-if="comment.avatar"
                    :src="comment.avatar"
                    :alt="comment.author"
                    class="w-7 h-7 rounded-full object-cover flex-shrink-0"
                  />
                  <div v-else class="w-7 h-7 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                    {{ comment.author.charAt(0) }}
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <span class="text-gray-900 text-xs font-medium">{{ comment.author }}</span>
                      <span class="text-gray-400 text-xs">{{ comment.time }}</span>
                    </div>
                    <p class="text-gray-600 text-xs mt-0.5">{{ comment.text }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
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

    <!-- Delete Video Modal -->
    <SBDeleteModal
      v-model="showDeleteModal"
      title="Delete Video"
      message="Are you sure you want to delete this video? This cannot be undone."
      :loading="isDeleting"
      @confirm="confirmDeleteVideo"
      @cancel="showDeleteModal = false"
    />

  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/stores/auth'
import videoService from '@/services/videoService'
import SBDeleteModal from '@/components/Global/SBDeleteModal.vue'

export default {
  name: 'VideoPlayerView',
  components: {
    SBDeleteModal
  },
  setup() {
    const route = useRoute()
    const auth = useAuth()
    const currentUser = computed(() => auth.user.value)
    const userInitial = computed(() => (currentUser.value?.name || 'U').charAt(0).toUpperCase())

    const video = ref({})
    const loading = ref(true)
    const error = ref(null)

    // Navigation between videos
    const allVideos = ref([])
    const currentIndex = ref(-1)
    const hasPrevious = computed(() => currentIndex.value > 0)
    const hasNext = computed(() => currentIndex.value < allVideos.value.length - 1 && currentIndex.value !== -1)

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
    const controlsVisible = ref(true)
    const hoverTime = ref(null)
    const hoverPercent = ref(0)
    const showSpeedMenu = ref(false)
    const speedOptions = [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2]
    const showBigPlayButton = ref(true)
    const copied = ref(false)
    const toast = ref(null)
    const showSkipBack = ref(false)
    const showSkipForward = ref(false)
    const skipBackAmount = ref(5)
    const skipForwardAmount = ref(5)

    const newComment = ref('')
    const comments = ref([])
    const isLoadingComments = ref(false)
    const isSavingComment = ref(false)
    const viewTracked = ref(false)
    const watchStartTime = ref(null)

    // Delete modal state
    const showDeleteModal = ref(false)
    const isDeleting = ref(false)

    // Title editing state
    const isEditingTitle = ref(false)
    const editedTitle = ref('')
    const isSavingTitle = ref(false)
    const titleInput = ref(null)

    const reactions = ref([
      { icon: '👍', count: 0, selected: false },
      { icon: '❤️', count: 0, selected: false },
      { icon: '🎉', count: 0, selected: false },
      { icon: '🔥', count: 0, selected: false },
      { icon: '👀', count: 0, selected: false },
    ])

    let controlsTimeout = null
    let toastTimeout = null
    let skipTimeout = null

    const progressPercent = computed(() => {
      if (!duration.value) return 0
      return (currentTime.value / duration.value) * 100
    })

    const fetchVideo = async () => {
      loading.value = true
      error.value = null

      try {
        // Fetch current video from API (always fresh, no cache)
        const fetchedVideo = await videoService.getVideo(route.params.id)

        video.value = {
          id: fetchedVideo.id,
          title: fetchedVideo.title,
          description: fetchedVideo.description,
          duration: fetchedVideo.duration,
          url: fetchedVideo.url,
          shareUrl: fetchedVideo.share_url,
          createdAt: new Date(fetchedVideo.created_at),
        }

        // CRITICAL: Set duration immediately from API data
        // WebM files often have duration metadata at the END of file
        // So we use the duration we saved during recording
        if (fetchedVideo.duration && fetchedVideo.duration > 0) {
          duration.value = fetchedVideo.duration
        }

        // Fetch all videos for navigation
        const videos = await videoService.getVideos()
        allVideos.value = videos
        currentIndex.value = videos.findIndex(v => v.id === parseInt(route.params.id))
      } catch (err) {
        console.error('Failed to load video:', err)
        error.value = 'Failed to load video. Please try again.'
      } finally {
        loading.value = false
      }
    }

    const goToPreviousVideo = () => {
      if (hasPrevious.value) {
        const prevVideo = allVideos.value[currentIndex.value - 1]
        // Use full page reload instead of router navigation
        window.location.href = `/video/${prevVideo.id}`
      }
    }

    const goToNextVideo = () => {
      if (hasNext.value) {
        const nextVideo = allVideos.value[currentIndex.value + 1]
        // Use full page reload instead of router navigation
        window.location.href = `/video/${nextVideo.id}`
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

      // Priority 1: Use video element duration if valid
      if (isFinite(videoDuration) && videoDuration > 0) {
        duration.value = videoDuration
        isBuffering.value = false
      }
      // Priority 2: Use API duration (from database)
      else if (apiDuration && apiDuration > 0) {
        duration.value = apiDuration
        isBuffering.value = false
      }

      // Apply current playback speed
      if (videoRef.value) {
        videoRef.value.playbackRate = playbackSpeed.value
      }
    }

    const onVideoError = (event) => {
      console.error('❌ Video error:', event)
      isBuffering.value = false
      const video = videoRef.value
      if (video && video.error) {
        console.error('Error code:', video.error.code, 'Message:', video.error.message)
      }
    }

    const onVideoEnded = () => {
      isPlaying.value = false
      showBigPlayButton.value = true
    }

    const seekToPosition = (clientX) => {
      const video = videoRef.value
      const bar = progressBar.value
      if (!video || !bar) return

      // Use stored duration if video element duration not ready
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

      // Pause during seeking for smoother experience
      const wasPlaying = !video.paused
      if (wasPlaying) video.pause()

      const onMouseMove = (moveEvent) => {
        seekToPosition(moveEvent.clientX)
      }

      const onMouseUp = () => {
        document.removeEventListener('mousemove', onMouseMove)
        document.removeEventListener('mouseup', onMouseUp)
        // Resume if was playing
        if (wasPlaying && video) video.play()
      }

      document.addEventListener('mousemove', onMouseMove)
      document.addEventListener('mouseup', onMouseUp)

      // Initial seek
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

      // Use stored duration if video element duration not ready
      const videoDuration = isFinite(video.duration) && video.duration > 0
        ? video.duration
        : duration.value

      if (!videoDuration || !isFinite(videoDuration) || videoDuration <= 0) return

      const currentVideoTime = video.currentTime || 0
      const newTime = Math.max(0, Math.min(videoDuration, currentVideoTime + seconds))
      video.currentTime = newTime
      currentTime.value = newTime

      // Show skip indicator with the actual amount skipped
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

      const video = videoRef.value
      if (video) {
        video.playbackRate = speed
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

    const showToast = (msg) => {
      toast.value = msg
      if (toastTimeout) clearTimeout(toastTimeout)
      toastTimeout = setTimeout(() => { toast.value = null }, 2000)
    }

    const formatTime = (seconds) => {
      if (!seconds || isNaN(seconds) || !isFinite(seconds)) return '0:00'
      const mins = Math.floor(seconds / 60)
      const secs = Math.floor(seconds % 60)
      return `${mins}:${secs.toString().padStart(2, '0')}`
    }

    const formatDate = (date) => {
      if (!date) return ''
      return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
    }

    const copyShareLink = async () => {
      if (video.value.shareUrl) {
        try {
          await navigator.clipboard.writeText(video.value.shareUrl)
          copied.value = true
          showToast('Share link copied!')
          setTimeout(() => { copied.value = false }, 3000)
        } catch (err) {}
      }
    }

    const downloadVideo = () => {
      if (video.value.url) {
        const link = document.createElement('a')
        link.href = video.value.url
        link.download = `${video.value.title || 'video'}.webm`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
      }
    }

    const deleteVideo = () => {
      showDeleteModal.value = true
    }

    const confirmDeleteVideo = async () => {
      isDeleting.value = true
      try {
        await videoService.deleteVideo(video.value.id)
        showDeleteModal.value = false
        // Use full page reload instead of router navigation
        window.location.href = '/videos'
      } catch (err) {
        showToast('Failed to delete')
      } finally {
        isDeleting.value = false
      }
    }

    const goBack = () => {
      // Use full page reload instead of router navigation
      window.location.href = '/videos'
    }

    const startEditingTitle = () => {
      editedTitle.value = video.value.title
      isEditingTitle.value = true
      // Focus input after DOM update
      setTimeout(() => {
        if (titleInput.value) {
          titleInput.value.focus()
          titleInput.value.select()
        }
      }, 50)
    }

    const saveTitle = async () => {
      if (isSavingTitle.value) return

      const newTitle = editedTitle.value.trim()
      if (!newTitle) {
        cancelEditingTitle()
        return
      }

      // Skip if title hasn't changed
      if (newTitle === video.value.title) {
        isEditingTitle.value = false
        return
      }

      isSavingTitle.value = true
      try {
        await videoService.updateVideo(video.value.id, { title: newTitle })
        video.value.title = newTitle
        isEditingTitle.value = false
        showToast('Title updated!')
      } catch (err) {
        console.error('Failed to update title:', err)
        showToast('Failed to update title')
      } finally {
        isSavingTitle.value = false
      }
    }

    const cancelEditingTitle = () => {
      isEditingTitle.value = false
      editedTitle.value = video.value.title
    }

    const addReaction = (icon) => {
      const reaction = reactions.value.find(r => r.icon === icon)
      if (reaction) {
        reaction.selected = !reaction.selected
        reaction.count += reaction.selected ? 1 : -1
      }
    }

    const loadComments = async () => {
      if (!video.value.id) return
      isLoadingComments.value = true
      try {
        const fetchedComments = await videoService.getComments(video.value.id)
        comments.value = fetchedComments.map(comment => ({
          id: comment.id,
          author: comment.author_name,
          avatar: comment.author_avatar,
          text: comment.content,
          time: formatTimeAgo(comment.created_at),
          timestamp_seconds: comment.timestamp_seconds
        }))
      } catch (err) {
        console.error('Failed to load comments:', err)
      } finally {
        isLoadingComments.value = false
      }
    }

    const addComment = async () => {
      if (!newComment.value.trim() || isSavingComment.value) return

      isSavingComment.value = true
      const commentText = newComment.value.trim()
      newComment.value = '' // Clear immediately for better UX

      try {
        const savedComment = await videoService.addComment(video.value.id, commentText)

        // Add to local list
        comments.value.unshift({
          id: savedComment.id,
          author: savedComment.author_name,
          avatar: savedComment.author_avatar,
          text: savedComment.content,
          time: 'Just now',
          timestamp_seconds: savedComment.timestamp_seconds
        })
      } catch (err) {
        console.error('Failed to save comment:', err)
        newComment.value = commentText // Restore on error
        showToast('Failed to save comment')
      } finally {
        isSavingComment.value = false
      }
    }

    const formatTimeAgo = (dateString) => {
      const date = new Date(dateString)
      const seconds = Math.floor((new Date() - date) / 1000)

      if (seconds < 60) return 'Just now'
      if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`
      if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`
      if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`
      return date.toLocaleDateString()
    }

    const trackView = async () => {
      if (viewTracked.value || !video.value.id) return

      viewTracked.value = true
      watchStartTime.value = Date.now()

      await videoService.recordView(video.value.id, 0, false)
    }

    const updateViewProgress = async () => {
      if (!viewTracked.value || !video.value.id || !watchStartTime.value) return

      const watchDuration = Math.floor((Date.now() - watchStartTime.value) / 1000)
      const completed = currentTime.value >= duration.value * 0.9 // 90% watched = completed

      await videoService.recordView(video.value.id, watchDuration, completed)
    }

    const handleKeydown = (e) => {
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return

      const key = e.key

      // Handle arrow keys (case-sensitive)
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

      // Handle number keys (0-9) to jump to video positions
      if (key >= '0' && key <= '9') {
        e.preventDefault()
        const percent = parseInt(key) * 10
        const video = videoRef.value
        if (video && video.duration) {
          const newTime = (percent / 100) * video.duration
          video.currentTime = newTime
          currentTime.value = newTime
          showToast(`Jumped to ${percent}%`)
        }
        return
      }

      // Handle letter keys (case-insensitive)
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
        case 'p':
          e.preventDefault()
          goToPreviousVideo()
          break
        case 'n':
          e.preventDefault()
          goToNextVideo()
          break
        case 'c':
          e.preventDefault()
          // Toggle captions (placeholder for future feature)
          showToast('Captions not available')
          break
        case 'i':
          e.preventDefault()
          // Toggle miniplayer (placeholder for future feature)
          togglePiP()
          break
        case ',':
          // Previous frame when paused, decrease speed when playing
          e.preventDefault()
          if (!isPlaying.value) {
            // Frame-by-frame backward (approximate)
            skip(-1/30)
          } else {
            const currentIdx = speedOptions.indexOf(playbackSpeed.value)
            if (currentIdx > 0) {
              setPlaybackSpeed(speedOptions[currentIdx - 1])
              showToast(`Speed: ${speedOptions[currentIdx - 1]}x`)
            }
          }
          break
        case '.':
          // Next frame when paused, increase speed when playing
          e.preventDefault()
          if (!isPlaying.value) {
            // Frame-by-frame forward (approximate)
            skip(1/30)
          } else {
            const currIdx = speedOptions.indexOf(playbackSpeed.value)
            if (currIdx < speedOptions.length - 1) {
              setPlaybackSpeed(speedOptions[currIdx + 1])
              showToast(`Speed: ${speedOptions[currIdx + 1]}x`)
            }
          }
          break
        case '<':
          // Decrease speed
          e.preventDefault()
          const currentIdx = speedOptions.indexOf(playbackSpeed.value)
          if (currentIdx > 0) {
            setPlaybackSpeed(speedOptions[currentIdx - 1])
            showToast(`Speed: ${speedOptions[currentIdx - 1]}x`)
          }
          break
        case '>':
          // Increase speed
          e.preventDefault()
          const currIdx = speedOptions.indexOf(playbackSpeed.value)
          if (currIdx < speedOptions.length - 1) {
            setPlaybackSpeed(speedOptions[currIdx + 1])
            showToast(`Speed: ${speedOptions[currIdx + 1]}x`)
          }
          break
        case 'home':
          e.preventDefault()
          if (videoRef.value) {
            videoRef.value.currentTime = 0
            currentTime.value = 0
            showToast('Jumped to start')
          }
          break
        case 'end':
          e.preventDefault()
          if (videoRef.value && videoRef.value.duration) {
            videoRef.value.currentTime = videoRef.value.duration
            currentTime.value = videoRef.value.duration
            showToast('Jumped to end')
          }
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

    // Note: Route watcher not needed since we use full page reloads for navigation
    // Videos are always loaded fresh via window.location.href instead of router.push

    onMounted(async () => {
      await fetchVideo()
      await loadComments()
      trackView() // Track initial view

      document.addEventListener('keydown', handleKeydown)
      document.addEventListener('click', handleClickOutside)
      document.addEventListener('fullscreenchange', handleFullscreenChange)

      // Update view progress every 30 seconds
      const viewProgressInterval = setInterval(updateViewProgress, 30000)

      onUnmounted(() => {
        clearInterval(viewProgressInterval)
        updateViewProgress() // Final update
      })
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
      // User
      currentUser, userInitial,
      // Video
      video, loading, error, videoRef, progressBar, speedMenuRef, playerContainer,
      isPlaying, isBuffering, isMuted, isFullscreen, volume, currentTime, duration,
      bufferedPercent, progressPercent, playbackSpeed, controlsVisible, hoverTime,
      hoverPercent, showSpeedMenu, showBigPlayButton, copied, toast, showSkipBack,
      showSkipForward, skipBackAmount, skipForwardAmount, newComment, comments, reactions,
      isLoadingComments, isSavingComment,
      // Navigation
      allVideos, currentIndex, hasPrevious, hasNext, goToPreviousVideo, goToNextVideo,
      // Speed options
      speedOptions, toggleSpeedMenu,
      // Methods
      togglePlay, updateProgress, onVideoLoaded, onVideoError, onVideoEnded, seek, startSeeking, updateHoverTime,
      skip, toggleMute, updateVolume, setPlaybackSpeed, toggleFullscreen, togglePiP,
      showControls, hideControlsDelayed, formatTime, formatDate, copyShareLink,
      downloadVideo, deleteVideo, confirmDeleteVideo, goBack, addReaction, addComment, loadComments,
      // Delete modal
      showDeleteModal, isDeleting,
      // Title editing
      isEditingTitle, editedTitle, isSavingTitle, titleInput,
      startEditingTitle, saveTitle, cancelEditingTitle,
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
