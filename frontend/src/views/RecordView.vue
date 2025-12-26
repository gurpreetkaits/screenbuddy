<template>
  <div class="bg-gradient-to-br from-orange-50 via-white to-red-50 min-h-full">
    <!-- Recording Status Bar -->
    <div v-if="isRecording" class="bg-white border-b border-gray-200 px-4 sm:px-6 lg:px-8 py-3 shadow-sm">
      <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-center">
          <div class="flex items-center space-x-2 bg-red-50 text-red-700 px-5 py-2.5 rounded-full shadow-sm">
            <div class="w-2.5 h-2.5 bg-red-500 rounded-full animate-pulse"></div>
            <span class="text-sm font-semibold">Recording {{ formatTime(recordingTime) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
      <!-- Recording Setup -->
      <div v-if="!isRecording && !hasRecorded" class="text-center">
        <!-- Header -->
        <div class="mb-10 sm:mb-12">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-orange-100 rounded-full mb-6">
            <svg class="w-8 h-8 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
              <circle cx="10" cy="10" r="6"/>
            </svg>
          </div>
          <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
            Ready to Record?
          </h1>
          <p class="text-lg sm:text-xl text-gray-600 max-w-2xl mx-auto">
            Capture your screen with crystal-clear quality. Perfect for tutorials, demos, and presentations.
          </p>
        </div>

        <!-- Recording Options Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-10 max-w-2xl mx-auto">
          <!-- Screen Option -->
          <label class="relative cursor-pointer group">
            <input
              v-model="recordingOptions.screen"
              type="checkbox"
              class="peer sr-only"
            >
            <div class="bg-white border-2 border-gray-200 rounded-xl p-8 transition-all peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:shadow-lg hover:shadow-md">
              <div class="flex flex-col items-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 peer-checked:bg-orange-100 transition-colors">
                  <svg class="w-8 h-8 text-gray-600 peer-checked:text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2 text-lg">Screen</h3>
                <p class="text-sm text-gray-500 text-center">Capture your entire display or specific window</p>
                <div class="absolute top-4 right-4 w-6 h-6 border-2 border-gray-300 rounded-full peer-checked:border-orange-500 peer-checked:bg-orange-500 flex items-center justify-center">
                  <svg class="w-4 h-4 text-white hidden peer-checked:block" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                  </svg>
                </div>
              </div>
            </div>
          </label>

          <!-- Microphone Option -->
          <label class="relative cursor-pointer group">
            <input
              v-model="recordingOptions.microphone"
              type="checkbox"
              class="peer sr-only"
            >
            <div class="bg-white border-2 border-gray-200 rounded-xl p-8 transition-all peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:shadow-lg hover:shadow-md">
              <div class="flex flex-col items-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 peer-checked:bg-orange-100 transition-colors">
                  <svg class="w-8 h-8 text-gray-600 peer-checked:text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                  </svg>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2 text-lg">Microphone</h3>
                <p class="text-sm text-gray-500 text-center">Record your voice with the screen</p>
                <div class="absolute top-4 right-4 w-6 h-6 border-2 border-gray-300 rounded-full peer-checked:border-orange-500 peer-checked:bg-orange-500 flex items-center justify-center">
                  <svg class="w-4 h-4 text-white hidden peer-checked:block" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                  </svg>
                </div>
              </div>
            </div>
          </label>
        </div>

        <!-- Start Recording Button -->
        <button
          @click="startRecording"
          :disabled="!canRecord || isStartingRecording"
          class="group relative inline-flex items-center px-8 sm:px-12 py-4 sm:py-5 border border-transparent text-lg sm:text-xl font-semibold rounded-full text-white bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 focus:outline-none focus:ring-4 focus:ring-orange-300 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl transform hover:scale-105"
        >
          <svg v-if="!isStartingRecording" class="w-7 h-7 mr-3" fill="currentColor" viewBox="0 0 20 20">
            <circle cx="10" cy="10" r="7" fill="currentColor"/>
          </svg>
          <svg v-else class="w-7 h-7 mr-3 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>{{ isStartingRecording ? 'Starting...' : 'Start Recording' }}</span>
        </button>

        <!-- Help Text -->
        <p class="mt-6 text-sm text-gray-500">
          Click to select what to share and start recording
        </p>
      </div>

      <!-- Recording in Progress -->
      <div v-if="isRecording" class="text-center">
        <div class="mb-6 sm:mb-8">
          <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3 sm:mb-4">Recording in Progress</h2>
          <p class="text-base sm:text-lg text-gray-600 px-4">
            Your screen is being recorded. Click stop when you're done.
          </p>
        </div>

        <!-- Recording Preview -->
        <div class="bg-gray-900 rounded-lg aspect-video max-w-3xl mx-auto mb-8 relative">
          <video
            ref="previewVideo"
            autoplay
            muted
            class="w-full h-full object-cover rounded-lg"
          ></video>

          <!-- Recording Indicator -->
          <div class="absolute top-4 left-4 flex items-center space-x-2 bg-red-500 text-white px-3 py-2 rounded-lg">
            <div class="w-3 h-3 bg-white rounded-full animate-pulse"></div>
            <span class="font-medium">REC {{ formatTime(recordingTime) }}</span>
          </div>
        </div>

        <!-- Recording Controls -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-0 sm:space-x-4">
          <button
            @click="pauseRecording"
            v-if="!isPaused"
            class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors duration-200"
          >
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path d="M5.5 3.5A1.5 1.5 0 017 2h6a1.5 1.5 0 011.5 1.5v13a1.5 1.5 0 01-1.5 1.5H7A1.5 1.5 0 015.5 16.5v-13zM9 4H7v12h2V4zm4 0h-2v12h2V4z"/>
            </svg>
            Pause
          </button>

          <button
            @click="resumeRecording"
            v-if="isPaused"
            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors duration-200"
          >
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/>
            </svg>
            Resume
          </button>

          <button
            @click="stopRecording"
            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
          >
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <rect x="4" y="4" width="12" height="12" rx="2" ry="2"/>
            </svg>
            Stop Recording
          </button>
        </div>
      </div>

      <!-- Processing/Uploading State -->
      <div v-if="hasRecorded && isSaving" class="text-center">
        <div class="mb-6">
          <div class="inline-flex items-center justify-center w-20 h-20 bg-orange-100 rounded-full mb-6">
            <svg class="w-10 h-10 text-orange-600 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
          <h2 class="text-3xl font-bold text-gray-900 mb-3">Processing Your Video</h2>
          <p class="text-lg text-gray-600">
            Uploading and preparing your recording...
          </p>
        </div>
      </div>
    </main>

    <!-- Upgrade Modal -->
    <SBUpgradeModal
      :show="showUpgradeModal"
      @close="showUpgradeModal = false"
      @success="showUpgradeModal = false"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { recordingSync } from '@/services/recordingSync'
import toast from '@/services/toastService'
import { useAuth } from '@/stores/auth'
import { buildApiUrl } from '@/config/api'
import SBUpgradeModal from '@/components/Global/SBUpgradeModal.vue'

export default {
  name: 'RecordView',
  setup() {
    const auth = useAuth()

    // Recording state
    const isStartingRecording = ref(false)
    const isRecording = ref(false)
    const isPaused = ref(false)
    const hasRecorded = ref(false)
    const recordingTime = ref(0)
    const videoTitle = ref('')
    const isSaving = ref(false)
    const shareUrl = ref('')
    const uploadedVideoId = ref(null)
    const recordingSource = ref(null) // 'website' or 'extension'
    const showUpgradeModal = ref(false)

    // Recording options (microphone always on, no camera)
    const recordingOptions = ref({
      screen: true,
      microphone: true // Always enabled
    })

    // Media elements
    const previewVideo = ref(null)
    const recordedVideo = ref(null)

    // MediaRecorder and streams
    let mediaRecorder = null
    let recordedChunks = []
    let stream = null
    let recordingInterval = null
    let syncUnsubscribe = null

    const canRecord = computed(() => {
      return recordingOptions.value.screen
    })

    const formatTime = (seconds) => {
      const minutes = Math.floor(seconds / 60)
      const remainingSeconds = seconds % 60
      return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
    }

    const startRecording = async () => {
      try {
        // Check subscription status first
        const subscription = await auth.fetchSubscription()
        if (subscription && !subscription.can_record) {
          showUpgradeModal.value = true
          toast.warning('You have reached your video limit. Please upgrade to continue recording.')
          return
        }

        isStartingRecording.value = true

        // Get screen capture (with system audio if available)
        // Request highest quality video (up to 4K)
        const displayMediaOptions = {
          video: {
            width: { ideal: 3840, max: 3840 },    // 4K width
            height: { ideal: 2160, max: 2160 },   // 4K height
            frameRate: { ideal: 60, max: 60 },    // 60fps for smooth recording
            displaySurface: 'monitor'              // Prefer full screen capture
          },
          audio: true // Request system/tab audio
        }

        const displayStream = await navigator.mediaDevices.getDisplayMedia(displayMediaOptions)

        // Log actual video resolution
        const videoTrack = displayStream.getVideoTracks()[0]
        if (videoTrack) {
          const settings = videoTrack.getSettings()
          console.log(`Recording at: ${settings.width}x${settings.height} @ ${settings.frameRate}fps`)
        }

        // Get microphone audio if user enabled it
        let audioStream = null
        if (recordingOptions.value.microphone) {
          try {
            audioStream = await navigator.mediaDevices.getUserMedia({
              audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true
              },
              video: false
            })
            console.log('Microphone access granted')
          } catch (audioErr) {
            console.warn('Could not get microphone access:', audioErr)
            toast.warning('Microphone access denied. Recording screen only.')
          }
        } else {
          console.log('Microphone not enabled by user')
        }

        // Mix audio tracks using Web Audio API
        const audioContext = new AudioContext()
        const audioDestination = audioContext.createMediaStreamDestination()

        // Add system audio if available
        const systemAudioTracks = displayStream.getAudioTracks()
        if (systemAudioTracks.length > 0) {
          const systemSource = audioContext.createMediaStreamSource(new MediaStream(systemAudioTracks))
          systemSource.connect(audioDestination)
          console.log('Added system audio')
        }

        // Add microphone audio if available
        if (audioStream) {
          const micSource = audioContext.createMediaStreamSource(audioStream)
          micSource.connect(audioDestination)
          console.log('Added microphone audio')
        }

        // Combine video and mixed audio
        const videoTracks = displayStream.getVideoTracks()
        const mixedAudioTracks = audioDestination.stream.getAudioTracks()

        stream = new MediaStream([
          ...videoTracks,
          ...mixedAudioTracks
        ])

        // Store reference to stop all streams later
        stream._displayStream = displayStream
        stream._audioStream = audioStream
        stream._audioContext = audioContext

        // Set up preview
        if (previewVideo.value) {
          previewVideo.value.srcObject = displayStream // Show display stream in preview
        }

        // Set up MediaRecorder with combined stream
        recordedChunks = []

        // Get video resolution to determine appropriate bitrate
        const videoTrackSettings = videoTracks[0]?.getSettings() || {}
        const width = videoTrackSettings.width || 1920
        const height = videoTrackSettings.height || 1080

        // Calculate bitrate based on resolution
        // 4K (3840x2160): 40 Mbps, 1440p: 20 Mbps, 1080p: 12 Mbps, 720p: 8 Mbps
        let videoBitsPerSecond = 12000000 // Default 12 Mbps for 1080p
        if (width >= 3840 || height >= 2160) {
          videoBitsPerSecond = 40000000 // 40 Mbps for 4K
        } else if (width >= 2560 || height >= 1440) {
          videoBitsPerSecond = 20000000 // 20 Mbps for 1440p
        } else if (width >= 1920 || height >= 1080) {
          videoBitsPerSecond = 12000000 // 12 Mbps for 1080p
        } else {
          videoBitsPerSecond = 8000000 // 8 Mbps for 720p and below
        }

        console.log(`Video resolution: ${width}x${height}, using bitrate: ${videoBitsPerSecond / 1000000} Mbps`)

        // Try different codecs for better seeking support
        // VP9 offers better compression at high resolutions
        let options = {
          mimeType: 'video/webm;codecs=vp9',
          videoBitsPerSecond: videoBitsPerSecond
        }

        // Fallback to VP8 if VP9 not supported
        if (!MediaRecorder.isTypeSupported(options.mimeType)) {
          console.log('VP9 not supported, trying VP8...')
          options = {
            mimeType: 'video/webm;codecs=vp8',
            videoBitsPerSecond: videoBitsPerSecond
          }
        }

        // Fallback to default if neither VP9 nor VP8 supported
        if (!MediaRecorder.isTypeSupported(options.mimeType)) {
          console.log('VP8 not supported, using default codec...')
          options = { videoBitsPerSecond: videoBitsPerSecond }
        }

        console.log('Using MediaRecorder with:', options)
        mediaRecorder = new MediaRecorder(stream, options)
        
        mediaRecorder.ondataavailable = (event) => {
          if (event.data.size > 0) {
            recordedChunks.push(event.data)
          }
        }
        
        mediaRecorder.onstop = async () => {
          const blob = new Blob(recordedChunks, {
            type: 'video/webm'
          })

          isRecording.value = false

          // Clean up all streams
          if (stream) {
            stream.getTracks().forEach(track => track.stop())
            // Stop display stream
            if (stream._displayStream) {
              stream._displayStream.getTracks().forEach(track => track.stop())
            }
            // Stop microphone stream
            if (stream._audioStream) {
              stream._audioStream.getTracks().forEach(track => track.stop())
            }
            // Close audio context
            if (stream._audioContext) {
              stream._audioContext.close()
            }
          }

          // Auto-save video after recording stops (will redirect to video page)
          await autoSaveVideo(blob)
        }
        
        // Start recording with timeslice for better seeking
        // Request data every 1 second to create more keyframes
        mediaRecorder.start(1000)
        isRecording.value = true
        recordingTime.value = 0
        recordingSource.value = 'website'

        // Notify sync service
        recordingSync.startRecording('website')

        // Start timer
        recordingInterval = setInterval(() => {
          if (!isPaused.value) {
            recordingTime.value++
          }
        }, 1000)

        isStartingRecording.value = false

      } catch (err) {
        console.error('Error starting recording:', err)
        toast.error('Failed to start recording. Please make sure you grant screen sharing permissions.')
        isStartingRecording.value = false
      }
    }

    const pauseRecording = () => {
      if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.pause()
        isPaused.value = true
        recordingSync.pauseRecording()
      }
    }

    const resumeRecording = () => {
      if (mediaRecorder && mediaRecorder.state === 'paused') {
        mediaRecorder.resume()
        isPaused.value = false
        recordingSync.resumeRecording()
      }
    }

    const stopRecording = () => {
      if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop()
      }

      if (recordingInterval) {
        clearInterval(recordingInterval)
        recordingInterval = null
      }

      isPaused.value = false
      isRecording.value = false
      hasRecorded.value = true
      recordingSync.stopRecording()
    }

    const startOver = () => {
      hasRecorded.value = false
      videoTitle.value = ''
      recordingTime.value = 0
      
      if (recordedVideo.value) {
        URL.revokeObjectURL(recordedVideo.value.src)
        recordedVideo.value.src = ''
      }
    }

    const autoSaveVideo = async (blob) => {
      try {
        isSaving.value = true
        hasRecorded.value = true // Show uploading UI

        // Generate default title with timestamp
        const timestamp = new Date().toLocaleString()
        const defaultTitle = `Screen Recording ${timestamp}`
        videoTitle.value = defaultTitle

        // Create FormData for upload
        const formData = new FormData()
        formData.append('video', blob, `recording-${Date.now()}.webm`)
        formData.append('title', defaultTitle)
        formData.append('duration', recordingTime.value)
        formData.append('is_public', '1')

        const apiUrl = buildApiUrl('/api/videos')
        console.log('Uploading video to:', apiUrl)

        // Upload to backend
        const response = await fetch(apiUrl, {
          method: 'POST',
          body: formData,
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${auth.token.value}`
          }
        })

        console.log('Response status:', response.status)
        console.log('Response content type:', response.headers.get('content-type'))

        // Handle 401 - redirect to login
        if (response.status === 401) {
          auth.clearAuth()
          localStorage.setItem('auth_redirect', '/record')
          window.location.href = '/login'
          return
        }

        // Handle 403 - video limit reached
        if (response.status === 403) {
          const errorData = await response.json().catch(() => ({}))
          if (errorData.error === 'video_limit_reached') {
            showUpgradeModal.value = true
            toast.error(errorData.message || 'You have reached your video limit. Please upgrade to continue.')
            return
          }
        }

        if (!response.ok) {
          // Try to get error as text first if it's not JSON
          const contentType = response.headers.get('content-type')
          let errorMessage = `Upload failed with status ${response.status}`

          if (contentType && contentType.includes('application/json')) {
            const errorData = await response.json().catch(() => ({}))
            console.error('Upload failed (JSON):', errorData)
            errorMessage = errorData.message || errorMessage
          } else {
            const errorText = await response.text()
            console.error('Upload failed (HTML/Text):', errorText.substring(0, 500))
            errorMessage = `Server returned an error. Status: ${response.status}`
          }

          throw new Error(errorMessage)
        }

        // Check if response is JSON
        const contentType = response.headers.get('content-type')
        if (!contentType || !contentType.includes('application/json')) {
          const responseText = await response.text()
          console.error('Expected JSON but got:', responseText.substring(0, 500))
          throw new Error('Server returned non-JSON response. Check server configuration.')
        }

        const data = await response.json()
        console.log('Video auto-saved successfully:', data)

        // Store the share URL and video ID
        shareUrl.value = data.video.share_url
        uploadedVideoId.value = data.video.id

        // Redirect to video watch page immediately (no toast)
        window.location.href = `/video/${data.video.id}`

      } catch (error) {
        console.error('Error auto-saving video:', error)
        toast.error(`Failed to auto-save video: ${error.message}`)
      } finally {
        isSaving.value = false
      }
    }

    const saveVideo = async () => {
      if (!videoTitle.value.trim()) return

      try {
        isSaving.value = true

        // Update the video title if it was changed
        if (uploadedVideoId.value) {
          const response = await fetch(buildApiUrl(`/api/videos/${uploadedVideoId.value}`), {
            method: 'PUT',
            body: JSON.stringify({
              title: videoTitle.value
            }),
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${auth.token.value}`
            }
          })

          // Handle 401 - redirect to login
          if (response.status === 401) {
            auth.clearAuth()
            localStorage.setItem('auth_redirect', '/record')
            window.location.href = '/login'
            return
          }

          if (!response.ok) {
            throw new Error('Failed to update video title')
          }
        }

        toast.success(`Video "${videoTitle.value}" saved successfully!`)
        // Use full page reload instead of router navigation
        window.location.href = '/videos'
      } catch (error) {
        console.error('Error updating video:', error)
        toast.error('Failed to update video. Please try again.')
      } finally {
        isSaving.value = false
      }
    }

    const copyShareLink = async () => {
      try {
        await navigator.clipboard.writeText(shareUrl.value)
        toast.success('Share link copied to clipboard!')
      } catch (error) {
        console.error('Failed to copy:', error)
        // Fallback for older browsers
        const textArea = document.createElement('textarea')
        textArea.value = shareUrl.value
        document.body.appendChild(textArea)
        textArea.select()
        document.execCommand('copy')
        document.body.removeChild(textArea)
        toast.success('Share link copied to clipboard!')
      }
    }

    onMounted(() => {
      // Component mounted
    })

    onUnmounted(() => {

      if (recordingInterval) {
        clearInterval(recordingInterval)
      }

      if (stream) {
        stream.getTracks().forEach(track => track.stop())
        if (stream._displayStream) {
          stream._displayStream.getTracks().forEach(track => track.stop())
        }
        if (stream._audioStream) {
          stream._audioStream.getTracks().forEach(track => track.stop())
        }
      }
    })

    return {
      // Recording state
      isStartingRecording,
      isRecording,
      isPaused,
      hasRecorded,
      recordingTime,
      videoTitle,
      isSaving,
      shareUrl,
      recordingOptions,
      previewVideo,
      recordedVideo,
      canRecord,
      showUpgradeModal,
      // Methods
      formatTime,
      startRecording,
      pauseRecording,
      resumeRecording,
      stopRecording,
      startOver,
      saveVideo,
      copyShareLink
    }
  },
  components: {
    SBUpgradeModal
  }
}
</script>
