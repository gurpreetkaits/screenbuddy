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
      <div v-if="hasRecorded && isFinishing" class="text-center">
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
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuth } from '@/stores/auth'
import { buildApiUrl } from '@/config/api'
import toast from '@/services/toastService'

export default {
  name: 'RecordView',
  setup() {
    const auth = useAuth()

    // Recording state
    const isStartingRecording = ref(false)
    const isRecording = ref(false)
    const isPaused = ref(false)
    const hasRecorded = ref(false)
    const isFinishing = ref(false)
    const recordingTime = ref(0)

    // Upload state
    const sessionId = ref(null)
    const uploadedBytes = ref(0)
    const chunksUploaded = ref(0)
    const isUploading = ref(false)
    const uploadQueue = ref([])
    const uploadProgress = ref(0)

    // Recording options
    const recordingOptions = ref({
      screen: true,
      microphone: true
    })

    // Media elements
    const previewVideo = ref(null)

    // MediaRecorder and streams
    let mediaRecorder = null
    let stream = null
    let recordingInterval = null
    let chunkIndex = 0

    const canRecord = computed(() => {
      return recordingOptions.value.screen
    })

    const formatTime = (seconds) => {
      const minutes = Math.floor(seconds / 60)
      const remainingSeconds = seconds % 60
      return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
    }

    const formatBytes = (bytes) => {
      if (bytes === 0) return '0 B'
      const k = 1024
      const sizes = ['B', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
    }

    // Start upload session
    const startUploadSession = async () => {
      const timestamp = new Date().toLocaleString()
      const title = `Screen Recording ${timestamp}`

      const response = await fetch(buildApiUrl('/api/stream/start'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${auth.token.value}`
        },
        body: JSON.stringify({
          title,
          mime_type: 'video/webm'
        })
      })

      if (response.status === 401) {
        auth.clearAuth()
        localStorage.setItem('auth_redirect', '/record')
        window.location.href = '/login'
        return null
      }

      if (!response.ok) {
        throw new Error('Failed to start upload session')
      }

      const data = await response.json()
      return data.session_id
    }

    // Upload a chunk
    const uploadChunk = async (chunk, index) => {
      if (!sessionId.value) return

      isUploading.value = true

      const formData = new FormData()
      formData.append('chunk', chunk, `chunk_${index}.webm`)
      formData.append('chunk_index', index)

      try {
        const response = await fetch(buildApiUrl(`/api/stream/${sessionId.value}/chunk`), {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${auth.token.value}`
          },
          body: formData
        })

        if (response.ok) {
          const data = await response.json()
          uploadedBytes.value = data.total_size
          chunksUploaded.value = data.chunks_received
          // Estimate progress (will be more accurate as recording continues)
          uploadProgress.value = Math.min(95, chunksUploaded.value * 5)
        }
      } catch (err) {
        console.error('Failed to upload chunk:', err)
        // Add to retry queue
        uploadQueue.value.push({ chunk, index })
      } finally {
        isUploading.value = uploadQueue.value.length > 0
      }
    }

    // Process upload queue
    const processUploadQueue = async () => {
      while (uploadQueue.value.length > 0) {
        const { chunk, index } = uploadQueue.value.shift()
        await uploadChunk(chunk, index)
      }
    }

    // Complete upload
    const completeUpload = async () => {
      if (!sessionId.value) return

      // Wait for any pending uploads
      await processUploadQueue()

      const response = await fetch(buildApiUrl(`/api/stream/${sessionId.value}/complete`), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${auth.token.value}`
        },
        body: JSON.stringify({
          duration: recordingTime.value
        })
      })

      if (!response.ok) {
        throw new Error('Failed to complete upload')
      }

      const data = await response.json()
      return data.video
    }

    const startRecording = async () => {
      try {
        isStartingRecording.value = true

        // Start upload session first
        sessionId.value = await startUploadSession()
        if (!sessionId.value) return

        // Get screen capture (up to 4K)
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

        // Get microphone audio if enabled
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
          } catch (audioErr) {
            console.warn('Could not get microphone access:', audioErr)
          }
        }

        // Mix audio tracks
        const audioContext = new AudioContext()
        const audioDestination = audioContext.createMediaStreamDestination()

        const systemAudioTracks = displayStream.getAudioTracks()
        if (systemAudioTracks.length > 0) {
          const systemSource = audioContext.createMediaStreamSource(new MediaStream(systemAudioTracks))
          systemSource.connect(audioDestination)
        }

        if (audioStream) {
          const micSource = audioContext.createMediaStreamSource(audioStream)
          micSource.connect(audioDestination)
        }

        // Combine video and mixed audio
        const videoTracks = displayStream.getVideoTracks()
        const mixedAudioTracks = audioDestination.stream.getAudioTracks()

        stream = new MediaStream([
          ...videoTracks,
          ...mixedAudioTracks
        ])

        stream._displayStream = displayStream
        stream._audioStream = audioStream
        stream._audioContext = audioContext

        // Set up preview
        if (previewVideo.value) {
          previewVideo.value.srcObject = displayStream
        }

        // Set up MediaRecorder with adaptive bitrate
        chunkIndex = 0

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

        // Try VP9 for better compression at high resolutions
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

        mediaRecorder.ondataavailable = async (event) => {
          if (event.data.size > 0) {
            // Upload chunk immediately
            uploadChunk(event.data, chunkIndex)
            chunkIndex++
          }
        }

        mediaRecorder.onstop = async () => {
          isRecording.value = false
          hasRecorded.value = true
          isFinishing.value = true

          // Clean up streams
          if (stream) {
            stream.getTracks().forEach(track => track.stop())
            if (stream._displayStream) {
              stream._displayStream.getTracks().forEach(track => track.stop())
            }
            if (stream._audioStream) {
              stream._audioStream.getTracks().forEach(track => track.stop())
            }
            if (stream._audioContext) {
              stream._audioContext.close()
            }
          }

          // Complete the upload
          try {
            const video = await completeUpload()
            uploadProgress.value = 100

            // Redirect to video page
            window.location.href = `/video/${video.id}`
          } catch (err) {
            console.error('Failed to complete upload:', err)
            toast.error('Failed to save video. Please try again.')
            isFinishing.value = false
          }
        }

        // Request data every 3 seconds (upload chunks during recording)
        mediaRecorder.start(3000)
        isRecording.value = true
        recordingTime.value = 0

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

        // Cancel upload session if started
        if (sessionId.value) {
          fetch(buildApiUrl(`/api/stream/${sessionId.value}/cancel`), {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${auth.token.value}`
            }
          }).catch(() => {})
        }
      }
    }

    const pauseRecording = () => {
      if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.pause()
        isPaused.value = true
      }
    }

    const resumeRecording = () => {
      if (mediaRecorder && mediaRecorder.state === 'paused') {
        mediaRecorder.resume()
        isPaused.value = false
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

      // Cancel upload session if recording was interrupted
      if (sessionId.value && !hasRecorded.value) {
        fetch(buildApiUrl(`/api/stream/${sessionId.value}/cancel`), {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${auth.token.value}`
          }
        }).catch(() => {})
      }
    })

    return {
      // State
      isStartingRecording,
      isRecording,
      isPaused,
      hasRecorded,
      isFinishing,
      recordingTime,
      recordingOptions,
      previewVideo,
      canRecord,
      // Upload state
      uploadedBytes,
      chunksUploaded,
      isUploading,
      uploadProgress,
      // Methods
      formatTime,
      formatBytes,
      startRecording,
      pauseRecording,
      resumeRecording,
      stopRecording
    }
  }
}
</script>
