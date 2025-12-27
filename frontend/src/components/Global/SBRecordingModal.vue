<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="handleBackdropClick"></div>

      <!-- Modal -->
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-4xl transform transition-all">
          <!-- Close Button (only when not recording or finishing) -->
          <button
            v-if="!isRecording && !isFinishing && !isSaving"
            @click="closeModal"
            class="absolute top-4 right-4 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors z-10"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>

          <!-- Loading State - Checking Subscription -->
          <div v-if="isCheckingSubscription" class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-gray-100 rounded-full mb-4">
              <svg class="w-7 h-7 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
            <p class="text-gray-600">Checking your account...</p>
          </div>

          <!-- Video Limit Reached -->
          <div v-else-if="!canRecordVideo && !isRecording && !hasRecorded" class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-4">
              <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Video Limit Reached</h2>
            <p class="text-gray-600 mb-6">
              You've used all your free videos. Upgrade to Pro for unlimited recordings.
            </p>
            <div class="flex items-center justify-center gap-3">
              <button
                @click="closeModal"
                class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                @click="handleUpgrade"
                class="px-5 py-2.5 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white rounded-lg font-medium transition-colors"
              >
                Upgrade to Pro
              </button>
            </div>
          </div>

          <!-- Recording Setup -->
          <div v-else-if="!isRecording && !hasRecorded" class="p-8 text-center">
            <!-- Header -->
            <div class="mb-8">
              <div class="inline-flex items-center justify-center w-14 h-14 bg-orange-100 rounded-full mb-4">
                <svg class="w-7 h-7 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                  <circle cx="10" cy="10" r="6"/>
                </svg>
              </div>
              <h2 class="text-2xl font-bold text-gray-900 mb-2">Start Recording</h2>
              <p class="text-gray-600">Select your recording options below</p>
            </div>

            <!-- Recording Options Cards -->
            <div class="grid grid-cols-2 gap-4 mb-8 max-w-md mx-auto">
              <!-- Screen Option (always enabled) -->
              <div class="bg-orange-50 border-2 border-orange-500 rounded-xl p-6">
                <div class="flex flex-col items-center">
                  <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                  </div>
                  <h3 class="font-semibold text-gray-900 text-sm">Screen</h3>
                  <p class="text-xs text-gray-500 mt-1">Always included</p>
                </div>
              </div>

              <!-- Microphone Option -->
              <label class="relative cursor-pointer group">
                <input
                  v-model="recordingOptions.microphone"
                  type="checkbox"
                  class="peer sr-only"
                >
                <div class="bg-white border-2 border-gray-200 rounded-xl p-6 transition-all peer-checked:border-orange-500 peer-checked:bg-orange-50 hover:shadow-md">
                  <div class="flex flex-col items-center">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3 peer-checked:bg-orange-100 transition-colors">
                      <svg class="w-6 h-6 text-gray-600 peer-checked:text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                      </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-sm">Microphone</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ recordingOptions.microphone ? 'Enabled' : 'Disabled' }}</p>
                  </div>
                </div>
              </label>
            </div>

            <!-- Start Recording Button -->
            <button
              @click="startRecording"
              :disabled="isStartingRecording"
              class="inline-flex items-center px-8 py-4 border border-transparent text-lg font-semibold rounded-full text-white bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 focus:outline-none focus:ring-4 focus:ring-orange-300 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl"
            >
              <svg v-if="!isStartingRecording" class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <circle cx="10" cy="10" r="7"/>
              </svg>
              <svg v-else class="w-6 h-6 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ isStartingRecording ? 'Starting...' : 'Start Recording' }}
            </button>

            <p class="mt-4 text-sm text-gray-500">
              Click to select what to share and start recording
            </p>
          </div>

          <!-- Recording in Progress -->
          <div v-if="isRecording" class="p-6">
            <!-- Recording Header -->
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-2 bg-red-50 text-red-700 px-4 py-2 rounded-full">
                <div class="w-2.5 h-2.5 bg-red-500 rounded-full animate-pulse"></div>
                <span class="text-sm font-semibold">REC {{ formatTime(recordingTime) }}</span>
              </div>
              <span class="text-sm text-gray-500">{{ formatBytes(uploadedBytes) }} uploaded</span>
            </div>

            <!-- Recording Preview -->
            <div class="bg-gray-900 rounded-lg aspect-video mb-6 relative overflow-hidden">
              <video
                ref="previewVideo"
                autoplay
                muted
                class="w-full h-full object-contain"
              ></video>
            </div>

            <!-- Recording Controls -->
            <div class="flex items-center justify-center space-x-4">
              <button
                @click="pauseRecording"
                v-if="!isPaused"
                class="inline-flex items-center px-5 py-2.5 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"
              >
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M5 4h3v12H5V4zm7 0h3v12h-3V4z"/>
                </svg>
                Pause
              </button>

              <button
                @click="resumeRecording"
                v-if="isPaused"
                class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-orange-600 hover:bg-orange-700 transition-colors"
              >
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/>
                </svg>
                Resume
              </button>

              <button
                @click="stopRecording"
                class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors"
              >
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <rect x="4" y="4" width="12" height="12" rx="2"/>
                </svg>
                Stop Recording
              </button>
            </div>
          </div>

          <!-- Review Recording (after stop, before save) -->
          <div v-if="hasRecorded && !isFinishing && !isSaving" class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
              <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Recording Complete!</h2>
            <p class="text-gray-600 mb-2">
              Duration: {{ formatTime(recordingTime) }} &bull; Size: {{ formatBytes(uploadedBytes) }}
            </p>
            <p class="text-gray-500 text-sm mb-6">
              Would you like to save this recording or discard it?
            </p>

            <div class="flex items-center justify-center gap-4">
              <button
                @click="discardRecording"
                :disabled="isDiscarding"
                class="inline-flex items-center px-6 py-3 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors disabled:opacity-50"
              >
                <svg v-if="!isDiscarding" class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <svg v-else class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                {{ isDiscarding ? 'Discarding...' : 'Discard' }}
              </button>

              <button
                @click="saveRecording"
                :disabled="isDiscarding"
                class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 transition-colors disabled:opacity-50"
              >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save Recording
              </button>
            </div>
          </div>

          <!-- Saving State -->
          <div v-if="isSaving" class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-orange-100 rounded-full mb-4">
              <svg class="w-8 h-8 text-orange-600 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Saving Your Video</h2>
            <p class="text-gray-600">Finalizing and preparing your recording...</p>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script>
import { ref, watch, onUnmounted } from 'vue'
import { useAuth } from '@/stores/auth'
import { buildApiUrl } from '@/config/api'
import toast from '@/services/toastService'

export default {
  name: 'SBRecordingModal',
  props: {
    show: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'recording-complete', 'upgrade'],
  setup(props, { emit }) {
    const auth = useAuth()

    // Subscription state
    const isCheckingSubscription = ref(false)
    const canRecordVideo = ref(true)

    // Recording state
    const isStartingRecording = ref(false)
    const isRecording = ref(false)
    const isPaused = ref(false)
    const hasRecorded = ref(false)
    const isFinishing = ref(false)
    const isSaving = ref(false)
    const isDiscarding = ref(false)
    const recordingTime = ref(0)

    // Upload state
    const sessionId = ref(null)
    const uploadedBytes = ref(0)
    const chunksUploaded = ref(0)
    const uploadQueue = ref([])

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

    const checkSubscription = async () => {
      console.log('[RecordingModal] Checking subscription limit from backend...')
      isCheckingSubscription.value = true
      try {
        const subscription = await auth.fetchSubscription()
        canRecordVideo.value = subscription ? subscription.can_record : true
        console.log('[RecordingModal] Backend response:', {
          can_record: canRecordVideo.value,
          videos_count: subscription?.videos_count,
          remaining_quota: subscription?.remaining_quota,
          is_active: subscription?.is_active
        })
      } catch (err) {
        console.error('Error checking subscription:', err)
        // Allow recording if check fails (will be checked again on server)
        canRecordVideo.value = true
      } finally {
        isCheckingSubscription.value = false
      }
    }

    const closeModal = () => {
      if (!isRecording.value && !isFinishing.value && !isSaving.value) {
        resetState()
        emit('close')
      }
    }

    const handleBackdropClick = () => {
      if (!isRecording.value && !isFinishing.value && !isSaving.value && !hasRecorded.value) {
        closeModal()
      }
    }

    const handleUpgrade = () => {
      emit('upgrade')
      closeModal()
    }

    const resetState = () => {
      isStartingRecording.value = false
      isRecording.value = false
      isPaused.value = false
      hasRecorded.value = false
      isFinishing.value = false
      isSaving.value = false
      isDiscarding.value = false
      recordingTime.value = 0
      sessionId.value = null
      uploadedBytes.value = 0
      chunksUploaded.value = 0
      uploadQueue.value = []
      chunkIndex = 0
      canRecordVideo.value = true
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
        localStorage.setItem('auth_redirect', '/videos')
        window.location.href = '/login'
        return null
      }

      if (response.status === 403) {
        const errorData = await response.json().catch(() => ({}))
        if (errorData.error === 'video_limit_reached') {
          canRecordVideo.value = false
          return null
        }
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
        }
      } catch (err) {
        console.error('Failed to upload chunk:', err)
        uploadQueue.value.push({ chunk, index })
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

    // Cancel upload session (discard)
    const cancelUpload = async () => {
      if (!sessionId.value) return

      try {
        await fetch(buildApiUrl(`/api/stream/${sessionId.value}/cancel`), {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${auth.token.value}`
          }
        })
      } catch (err) {
        console.error('Failed to cancel upload:', err)
      }
    }

    const startRecording = async () => {
      try {
        isStartingRecording.value = true

        // Start upload session first
        sessionId.value = await startUploadSession()
        if (!sessionId.value) {
          isStartingRecording.value = false
          return
        }

        // Get screen capture
        const displayMediaOptions = {
          video: {
            width: { ideal: 3840, max: 3840 },
            height: { ideal: 2160, max: 2160 },
            frameRate: { ideal: 60, max: 60 },
            displaySurface: 'monitor'
          },
          audio: true
        }

        const displayStream = await navigator.mediaDevices.getDisplayMedia(displayMediaOptions)

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

        // Set up MediaRecorder
        chunkIndex = 0
        const videoTrackSettings = videoTracks[0]?.getSettings() || {}
        const width = videoTrackSettings.width || 1920
        const height = videoTrackSettings.height || 1080

        let videoBitsPerSecond = 12000000
        if (width >= 3840 || height >= 2160) {
          videoBitsPerSecond = 40000000
        } else if (width >= 2560 || height >= 1440) {
          videoBitsPerSecond = 20000000
        } else if (width >= 1920 || height >= 1080) {
          videoBitsPerSecond = 12000000
        } else {
          videoBitsPerSecond = 8000000
        }

        let options = {
          mimeType: 'video/webm;codecs=vp9',
          videoBitsPerSecond
        }

        if (!MediaRecorder.isTypeSupported(options.mimeType)) {
          options = {
            mimeType: 'video/webm;codecs=vp8',
            videoBitsPerSecond
          }
        }

        if (!MediaRecorder.isTypeSupported(options.mimeType)) {
          options = { videoBitsPerSecond }
        }

        mediaRecorder = new MediaRecorder(stream, options)

        mediaRecorder.ondataavailable = async (event) => {
          if (event.data.size > 0) {
            uploadChunk(event.data, chunkIndex)
            chunkIndex++
          }
        }

        mediaRecorder.onstop = async () => {
          isRecording.value = false
          hasRecorded.value = true

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

          // Don't auto-save - wait for user to click Save or Discard
        }

        // Handle when user stops sharing via browser UI
        displayStream.getVideoTracks()[0].onended = () => {
          if (isRecording.value) {
            stopRecording()
          }
        }

        mediaRecorder.start(3000)
        isRecording.value = true
        recordingTime.value = 0

        recordingInterval = setInterval(() => {
          if (!isPaused.value) {
            recordingTime.value++
          }
        }, 1000)

        isStartingRecording.value = false

      } catch (err) {
        console.error('Error starting recording:', err)

        if (err.name === 'NotAllowedError') {
          toast.error('Screen sharing was cancelled or denied.')
        } else {
          toast.error('Failed to start recording. Please make sure you grant screen sharing permissions.')
        }

        isStartingRecording.value = false

        if (sessionId.value) {
          cancelUpload()
          sessionId.value = null
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

    const saveRecording = async () => {
      isSaving.value = true
      try {
        const video = await completeUpload()
        emit('recording-complete', video)
        window.location.href = `/video/${video.id}`
      } catch (err) {
        console.error('Failed to save recording:', err)
        toast.error('Failed to save video. Please try again.')
        isSaving.value = false
      }
    }

    const discardRecording = async () => {
      isDiscarding.value = true
      try {
        await cancelUpload()
        toast.success('Recording discarded')
        resetState()
        emit('close')
      } catch (err) {
        console.error('Failed to discard recording:', err)
        toast.error('Failed to discard recording')
        isDiscarding.value = false
      }
    }

    // Watch for modal open to check subscription
    watch(() => props.show, (newVal) => {
      if (newVal) {
        console.log('[RecordingModal] Modal opened, fetching fresh subscription status from backend...')
        checkSubscription()
      } else if (!isRecording.value && !isFinishing.value && !isSaving.value) {
        resetState()
      }
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

      if (sessionId.value && !hasRecorded.value) {
        cancelUpload()
      }
    })

    return {
      // Subscription state
      isCheckingSubscription,
      canRecordVideo,
      // Recording state
      isStartingRecording,
      isRecording,
      isPaused,
      hasRecorded,
      isFinishing,
      isSaving,
      isDiscarding,
      recordingTime,
      recordingOptions,
      previewVideo,
      uploadedBytes,
      // Methods
      formatTime,
      formatBytes,
      closeModal,
      handleBackdropClick,
      handleUpgrade,
      startRecording,
      pauseRecording,
      resumeRecording,
      stopRecording,
      saveRecording,
      discardRecording
    }
  }
}
</script>
