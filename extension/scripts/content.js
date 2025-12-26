// Content script for ScreenSense - handles recording and camera overlay

// Prevent duplicate injection using IIFE
(function() {
  // If already loaded, just show panel if requested and exit
  if (window.__screensenseContentScriptLoaded) {
    console.log('ScreenSense content script already loaded');
    // Re-register message listener to ensure it works
    chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
      if (message.action === 'showFloatingPanel') {
        if (typeof window.__screensenseShowPanel === 'function') {
          window.__screensenseShowPanel();
        }
        sendResponse({ success: true });
      }
      return true;
    });
    return;
  }
  window.__screensenseContentScriptLoaded = true;
  window.__screensenseExtensionInstalled = true;
  console.log('ScreenSense content script loaded');

  // Dispatch event to notify website that extension is installed
  window.dispatchEvent(new CustomEvent('screensense:extension:ready', {
    detail: { version: '1.0.0' }
  }));

let mediaRecorder = null;
let recordedChunks = [];
let screenStream = null;
let cameraStream = null;
let combinedStream = null;
let cameraOverlay = null;
let cameraVideo = null;
let recordingOptions = null;
let recordingStartTime = null;
let websiteRecordingActive = false;
let controlBar = null;
let controlBarTimer = null;
let isPaused = false;
let recordingPanel = null;

// ============================================
// Configuration - Change ENV to switch environments
// ============================================

const ENV = 'local'; // Change to 'production' for live site

const CONFIG = {
  local: {
    APP_URL: 'http://localhost:5173',
    API_URL: 'http://localhost:8000'
  },
  production: {
    APP_URL: 'https://record.screensense.in',
    API_URL: 'https://record.screensense.in'
  }
};

const SCREENSENSE_URL = CONFIG[ENV].APP_URL;
const API_URL = CONFIG[ENV].API_URL;
const IS_LOCAL = ENV === 'local';

// ============================================
// Website <-> Extension Sync
// ============================================

function isScreenSensePage() {
  const currentOrigin = window.location.origin;
  return currentOrigin === CONFIG.local.APP_URL ||
         currentOrigin === CONFIG.production.APP_URL ||
         window.location.hostname === 'localhost' ||
         window.location.hostname.includes('screensense.in');
}

// Check if extension context is still valid
function isExtensionContextValid() {
  try {
    return !!chrome.runtime?.id;
  } catch (e) {
    return false;
  }
}

// Safe wrapper for chrome.runtime.sendMessage
function safeSendMessage(message, callback) {
  if (!isExtensionContextValid()) {
    console.warn('Extension context invalidated. Please refresh the page.');
    return;
  }
  try {
    chrome.runtime.sendMessage(message, callback);
  } catch (e) {
    console.warn('Failed to send message:', e);
  }
}

// Listen for auth updates from the website
window.addEventListener('message', (event) => {
  if (!isExtensionContextValid()) return;

  if (event.data?.type === 'SCREENSENSE_AUTH_UPDATE') {
    const { token, user } = event.data;
    // Store in chrome.storage for cross-origin access
    try {
      chrome.storage.local.set({ auth_token: token, auth_user: user }, () => {
        console.log('Auth synced to extension storage');
      });
    } catch (e) {
      console.warn('Failed to sync auth to extension storage');
    }
  } else if (event.data?.type === 'SCREENSENSE_AUTH_LOGOUT') {
    try {
      chrome.storage.local.remove(['auth_token', 'auth_user'], () => {
        console.log('Auth cleared from extension storage');
      });
    } catch (e) {
      console.warn('Failed to clear auth from extension storage');
    }
  }
});

// Sync auth from localStorage on ScreenSense pages
if (isScreenSensePage() && isExtensionContextValid()) {
  const token = localStorage.getItem('auth_token');
  const userStr = localStorage.getItem('auth_user');
  if (token && userStr) {
    try {
      const user = JSON.parse(userStr);
      chrome.storage.local.set({ auth_token: token, auth_user: user });
    } catch (e) {}
  }
}

// Helper to get auth token (checks localStorage first, then chrome.storage)
async function getAuthToken() {
  // First try localStorage (works on ScreenSense pages)
  const localToken = localStorage.getItem('auth_token');
  if (localToken) return localToken;

  // Check if extension context is valid before using chrome.storage
  if (!isExtensionContextValid()) {
    return null;
  }

  // Fall back to chrome.storage (works on any page)
  return new Promise((resolve) => {
    try {
      chrome.storage.local.get(['auth_token'], (result) => {
        resolve(result.auth_token || null);
      });
    } catch (e) {
      resolve(null);
    }
  });
}

// Notify website of extension recording state
function notifyWebsiteOfState(isRecording, isPaused = false) {
  if (isScreenSensePage()) {
    window.dispatchEvent(new CustomEvent('screensense:extension:state', {
      detail: {
        isRecording,
        isPaused,
        startTime: recordingStartTime,
        source: 'extension'
      }
    }));
  }
}

// Listen for website recording state changes
window.addEventListener('screensense:website:state', (event) => {
  const state = event.detail;
  console.log('Extension received website state:', state);

  // Track website recording state
  if (state.source === 'website') {
    websiteRecordingActive = state.isRecording;
  }

  // Forward to background script
  safeSendMessage({
    action: 'websiteStateChanged',
    state
  });
});

// Listen for website requesting to show recording panel
window.addEventListener('screensense:website:showPanel', (event) => {
  console.log('Extension received showPanel request');
  createRecordingPanel();
});

// Listen for website requesting to control extension recording
window.addEventListener('screensense:website:command', (event) => {
  const { command, options } = event.detail;
  console.log('Extension received website command:', command);

  switch (command) {
    case 'start':
      // Start recording directly from content script
      initRecording(options || { screen: true, camera: false, microphone: true })
        .then(() => {
          recordingStartTime = Date.now();
          // Notify background script that recording started
          safeSendMessage({
            action: 'websiteStateChanged',
            state: {
              isRecording: true,
              isPaused: false,
              startTime: recordingStartTime,
              source: 'website'
            }
          });
          notifyWebsiteOfState(true, false);
          window.dispatchEvent(new CustomEvent('screensense:extension:response', {
            detail: { command: 'start', success: true }
          }));
        })
        .catch((error) => {
          window.dispatchEvent(new CustomEvent('screensense:extension:response', {
            detail: { command: 'start', success: false, error: error.message }
          }));
        });
      break;
    case 'pause':
      pauseRecording();
      safeSendMessage({
        action: 'websiteStateChanged',
        state: { isRecording: true, isPaused: true, source: 'website' }
      });
      notifyWebsiteOfState(true, true);
      break;
    case 'resume':
      resumeRecording();
      safeSendMessage({
        action: 'websiteStateChanged',
        state: { isRecording: true, isPaused: false, source: 'website' }
      });
      notifyWebsiteOfState(true, false);
      break;
    case 'stop':
      stopRecording().then(() => {
        safeSendMessage({
          action: 'websiteStateChanged',
          state: { isRecording: false, isPaused: false, source: 'website' }
        });
        notifyWebsiteOfState(false, false);
      });
      break;
    case 'check':
      // Website is checking if extension is ready
      window.dispatchEvent(new CustomEvent('screensense:extension:response', {
        detail: { command: 'check', success: true, installed: true }
      }));
      break;
  }
});

// Listen for messages from background script
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  switch (message.action) {
    case 'showRecordingPanel':
      // Show the recording panel (triggered from extension popup)
      createRecordingPanel();
      sendResponse({ success: true });
      break;

    case 'showControlBar':
      // Show control bar on this tab (for tab switching during recording)
      if (!controlBar) {
        recordingStartTime = message.startTime || Date.now();
        isPaused = message.isPaused || false;
        createControlBar();
        if (isPaused) {
          updateControlBarPauseState(true);
        }
      }
      sendResponse({ success: true });
      break;

    case 'hideControlBar':
      // Hide control bar when switching away from this tab
      removeControlBar();
      sendResponse({ success: true });
      break;

    case 'updateControlBarPause':
      // Update control bar pause state (synced from background)
      if (controlBar) {
        updateControlBarPauseState(message.isPaused);
      }
      sendResponse({ success: true });
      break;

    case 'initRecording':
      initRecording(message.options)
        .then(() => {
          recordingStartTime = Date.now();
          notifyWebsiteOfState(true, false);
          sendResponse({ success: true });
        })
        .catch(error => sendResponse({ success: false, error: error.message }));
      return true;

    case 'pauseRecording':
      // If recording is from website, send command to website
      if (websiteRecordingActive && !mediaRecorder) {
        window.dispatchEvent(new CustomEvent('screensense:extension:command', {
          detail: { command: 'pause' }
        }));
        sendResponse({ success: true });
      } else if (mediaRecorder && mediaRecorder.state === 'recording') {
        pauseRecording();
        notifyWebsiteOfState(true, true);
        sendResponse({ success: true });
      } else {
        sendResponse({ success: false, error: 'No active recording to pause' });
      }
      break;

    case 'resumeRecording':
      // If recording is from website, send command to website
      if (websiteRecordingActive && !mediaRecorder) {
        window.dispatchEvent(new CustomEvent('screensense:extension:command', {
          detail: { command: 'resume' }
        }));
        sendResponse({ success: true });
      } else if (mediaRecorder && mediaRecorder.state === 'paused') {
        resumeRecording();
        notifyWebsiteOfState(true, false);
        sendResponse({ success: true });
      } else {
        sendResponse({ success: false, error: 'No paused recording to resume' });
      }
      break;

    case 'stopRecording':
      // If recording is from website (Vue app), send command to website
      if (websiteRecordingActive && !mediaRecorder) {
        window.dispatchEvent(new CustomEvent('screensense:extension:command', {
          detail: { command: 'stop' }
        }));
        websiteRecordingActive = false;
        sendResponse({ success: true });
      } else if (mediaRecorder) {
        // Recording exists in content script (from panel or extension)
        stopRecording()
          .then(() => {
            notifyWebsiteOfState(false, false);
            recordingStartTime = null;
            sendResponse({ success: true });
          })
          .catch(error => sendResponse({ success: false, error: error.message }));
        return true;
      } else {
        sendResponse({ success: false, error: 'No active recording found' });
      }
      break;

    case 'downloadRecording':
      downloadRecording();
      sendResponse({ success: true });
      break;

    case 'syncState':
      // Background script is syncing state to content script
      notifyWebsiteOfState(message.isRecording, message.isPaused);
      sendResponse({ success: true });
      break;

    case 'getRecordingState':
      // Website is asking for current state
      sendResponse({
        isRecording: mediaRecorder !== null && mediaRecorder.state !== 'inactive',
        isPaused: mediaRecorder?.state === 'paused',
        startTime: recordingStartTime
      });
      break;

    case 'autoStartRecording':
      // Triggered from extension popup - post message to website to auto-start
      if (isScreenSensePage()) {
        window.postMessage({
          type: 'SCREENSENSE_AUTO_START',
          options: message.options || { camera: false, microphone: true }
        }, '*');
        sendResponse({ success: true });
      } else {
        sendResponse({ success: false, error: 'Not on ScreenSense page' });
      }
      break;

    case 'showFloatingPanel':
      // Show the Loom-style floating panel
      showFloatingRecordPanel();
      sendResponse({ success: true });
      break;
  }
});

async function initRecording(options) {
  try {
    recordingOptions = options;
    recordedChunks = [];

    // Request screen capture (with system audio)
    if (options.screen) {
      screenStream = await navigator.mediaDevices.getDisplayMedia({
        video: {
          mediaSource: 'screen',
          width: { ideal: 1920 },
          height: { ideal: 1080 }
        },
        audio: true // Capture system/tab audio if available
      });
    }

    // Request microphone audio separately (this is the fix!)
    let microphoneStream = null;
    if (options.microphone) {
      try {
        microphoneStream = await navigator.mediaDevices.getUserMedia({
          audio: {
            echoCancellation: true,
            noiseSuppression: true,
            autoGainControl: true
          },
          video: false
        });
        console.log('Microphone access granted');
      } catch (micError) {
        console.warn('Could not get microphone access:', micError);
      }
    }

    // Request camera if enabled
    if (options.camera) {
      try {
        console.log('Requesting camera access...');
        cameraStream = await navigator.mediaDevices.getUserMedia({
          video: {
            width: { ideal: 640 },
            height: { ideal: 480 },
            facingMode: 'user'
          },
          audio: false // We get audio from microphoneStream now
        });
        console.log('Camera access granted, creating overlay...');
        createCameraOverlay();
        console.log('Camera overlay created');
      } catch (cameraError) {
        console.error('Could not get camera access:', cameraError);
        // Continue without camera
      }
    }

    // Combine all streams
    const tracks = [];

    // Add video tracks
    if (screenStream) {
      screenStream.getVideoTracks().forEach(track => tracks.push(track));
      // Add system audio if available
      screenStream.getAudioTracks().forEach(track => tracks.push(track));
    }

    if (cameraStream && !options.screen) {
      // If screen recording is off, use camera as main video
      cameraStream.getVideoTracks().forEach(track => tracks.push(track));
    }

    // Add microphone audio (separate from system audio)
    if (microphoneStream) {
      microphoneStream.getAudioTracks().forEach(track => tracks.push(track));
    }

    combinedStream = new MediaStream(tracks);

    // Store microphone stream for cleanup
    combinedStream._microphoneStream = microphoneStream;

    // Set up MediaRecorder
    const mimeType = MediaRecorder.isTypeSupported('video/webm;codecs=vp9')
      ? 'video/webm;codecs=vp9'
      : 'video/webm';

    mediaRecorder = new MediaRecorder(combinedStream, {
      mimeType,
      videoBitsPerSecond: 2500000
    });

    mediaRecorder.ondataavailable = (event) => {
      if (event.data.size > 0) {
        recordedChunks.push(event.data);
      }
    };

    mediaRecorder.onstop = () => {
      console.log('Recording stopped, chunks:', recordedChunks.length);
    };

    // Handle when user stops screen sharing via browser UI
    if (screenStream) {
      screenStream.getVideoTracks()[0].onended = () => {
        console.log('Screen sharing stopped by user');
        stopRecording();
      };
    }

    // Start recording
    mediaRecorder.start(100); // Capture data every 100ms
    console.log('Recording started');

    // Show the control bar
    console.log('Creating control bar...');
    createControlBar();
    console.log('Control bar created and appended to body');

  } catch (error) {
    console.error('Error initializing recording:', error);
    cleanup();
    throw error;
  }
}

function createCameraOverlay() {
  // Create camera overlay container
  cameraOverlay = document.createElement('div');
  cameraOverlay.id = 'screensense-camera-overlay';
  cameraOverlay.style.cssText = `
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 200px;
    height: 150px;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2), 0 0 0 3px rgba(234, 88, 12, 0.3);
    z-index: 999999;
    border: 3px solid #ea580c;
    cursor: move;
    transition: all 0.3s ease;
  `;

  // Create video element for camera
  cameraVideo = document.createElement('video');
  cameraVideo.autoplay = true;
  cameraVideo.muted = true;
  cameraVideo.playsInline = true;
  cameraVideo.setAttribute('playsinline', '');
  cameraVideo.srcObject = cameraStream;
  cameraVideo.style.cssText = `
    width: 100%;
    height: 100%;
    object-fit: cover;
    transform: scaleX(-1);
  `;

  // Ensure video plays
  cameraVideo.play().catch(err => console.log('Camera video play error:', err));

  // Create recording indicator
  const recordingIndicator = document.createElement('div');
  recordingIndicator.style.cssText = `
    position: absolute;
    top: 10px;
    left: 10px;
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: white;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 5px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.4);
    letter-spacing: 0.5px;
  `;

  const dot = document.createElement('span');
  dot.style.cssText = `
    width: 7px;
    height: 7px;
    background: white;
    border-radius: 50%;
    animation: screensense-pulse 1.5s ease-in-out infinite;
    box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
  `;

  recordingIndicator.appendChild(dot);
  recordingIndicator.appendChild(document.createTextNode('REC'));

  cameraOverlay.appendChild(cameraVideo);
  cameraOverlay.appendChild(recordingIndicator);

  // Make draggable
  makeDraggable(cameraOverlay);

  // Add to page
  document.body.appendChild(cameraOverlay);

  // Add animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes screensense-pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
  `;
  document.head.appendChild(style);
}

function createControlBar() {
  // Remove existing control bar if any
  if (controlBar) {
    controlBar.remove();
  }

  controlBar = document.createElement('div');
  controlBar.id = 'screensense-control-bar';
  controlBar.style.cssText = `
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    border-radius: 50px;
    padding: 8px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 999999;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    cursor: move;
    user-select: none;
    animation: screensense-slide-down 0.3s ease-out;
  `;

  // Add slide-down animation
  const animStyle = document.createElement('style');
  animStyle.id = 'screensense-control-bar-styles';
  animStyle.textContent = `
    @keyframes screensense-slide-down {
      from { transform: translateX(-50%) translateY(-100%); opacity: 0; }
      to { transform: translateX(-50%) translateY(0); opacity: 1; }
    }
    @keyframes screensense-rec-pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.4; }
    }
  `;
  document.head.appendChild(animStyle);

  // Drag handle / Logo
  const dragHandle = document.createElement('div');
  dragHandle.style.cssText = `
    display: flex;
    align-items: center;
    gap: 8px;
    padding-right: 12px;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
  `;
  dragHandle.innerHTML = `
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2">
      <circle cx="12" cy="12" r="10"/>
      <circle cx="12" cy="12" r="4" fill="#ea580c"/>
    </svg>
    <span style="color: #ea580c; font-weight: 600; font-size: 13px;">ScreenSense</span>
  `;

  // Recording indicator with time
  const recIndicator = document.createElement('div');
  recIndicator.id = 'screensense-rec-indicator';
  recIndicator.style.cssText = `
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 12px;
  `;
  recIndicator.innerHTML = `
    <div style="
      width: 10px;
      height: 10px;
      background: #ef4444;
      border-radius: 50%;
      animation: screensense-rec-pulse 1.5s ease-in-out infinite;
    "></div>
    <span id="screensense-timer" style="color: white; font-size: 14px; font-weight: 500; font-variant-numeric: tabular-nums;">00:00</span>
  `;

  // Pause/Resume button
  const pauseBtn = document.createElement('button');
  pauseBtn.id = 'screensense-pause-btn';
  pauseBtn.style.cssText = `
    background: rgba(255, 255, 255, 0.1);
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
  `;
  pauseBtn.innerHTML = `
    <svg id="screensense-pause-icon" width="16" height="16" viewBox="0 0 24 24" fill="white">
      <rect x="6" y="4" width="4" height="16" rx="1"/>
      <rect x="14" y="4" width="4" height="16" rx="1"/>
    </svg>
  `;
  pauseBtn.title = 'Pause Recording';
  pauseBtn.onmouseover = () => pauseBtn.style.background = 'rgba(255, 255, 255, 0.2)';
  pauseBtn.onmouseout = () => pauseBtn.style.background = 'rgba(255, 255, 255, 0.1)';
  pauseBtn.onclick = (e) => {
    e.stopPropagation();
    if (isPaused) {
      // Send resume to background which will forward to the recording tab
      safeSendMessage({ action: 'resumeRecording' }, (response) => {
        if (response && response.success) {
          updateControlBarPauseState(false);
        }
      });
    } else {
      // Send pause to background which will forward to the recording tab
      safeSendMessage({ action: 'pauseRecording' }, (response) => {
        if (response && response.success) {
          updateControlBarPauseState(true);
        }
      });
    }
  };

  // Stop button
  const stopBtn = document.createElement('button');
  stopBtn.id = 'screensense-stop-btn';
  stopBtn.style.cssText = `
    background: #ef4444;
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
  `;
  stopBtn.innerHTML = `
    <svg width="14" height="14" viewBox="0 0 24 24" fill="white">
      <rect x="4" y="4" width="16" height="16" rx="2"/>
    </svg>
  `;
  stopBtn.title = 'Stop Recording';
  stopBtn.onmouseover = () => stopBtn.style.background = '#dc2626';
  stopBtn.onmouseout = () => stopBtn.style.background = '#ef4444';
  stopBtn.onclick = (e) => {
    e.stopPropagation();
    // Send stop to background which will forward to the recording tab
    safeSendMessage({ action: 'stopRecording' }, (response) => {
      if (response && response.success) {
        removeControlBar();
      }
    });
  };

  // Assemble control bar
  controlBar.appendChild(dragHandle);
  controlBar.appendChild(recIndicator);
  controlBar.appendChild(pauseBtn);
  controlBar.appendChild(stopBtn);

  // Make the control bar draggable (but not the buttons)
  makeDraggableControlBar(controlBar);

  document.body.appendChild(controlBar);

  // Start the timer
  startControlBarTimer();
}

function updateControlBarPauseState(paused) {
  isPaused = paused;
  const pauseBtn = document.getElementById('screensense-pause-btn');
  const recDot = controlBar?.querySelector('#screensense-rec-indicator div');

  if (pauseBtn) {
    if (paused) {
      // Show play icon
      pauseBtn.innerHTML = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="white">
          <path d="M8 5v14l11-7z"/>
        </svg>
      `;
      pauseBtn.title = 'Resume Recording';
    } else {
      // Show pause icon
      pauseBtn.innerHTML = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="white">
          <rect x="6" y="4" width="4" height="16" rx="1"/>
          <rect x="14" y="4" width="4" height="16" rx="1"/>
        </svg>
      `;
      pauseBtn.title = 'Pause Recording';
    }
  }

  if (recDot) {
    if (paused) {
      recDot.style.animation = 'none';
      recDot.style.background = '#fbbf24'; // Yellow for paused
    } else {
      recDot.style.animation = 'screensense-rec-pulse 1.5s ease-in-out infinite';
      recDot.style.background = '#ef4444'; // Red for recording
    }
  }
}

function startControlBarTimer() {
  if (controlBarTimer) {
    clearInterval(controlBarTimer);
  }

  const updateTimer = () => {
    if (!recordingStartTime) return;

    const timerEl = document.getElementById('screensense-timer');
    if (timerEl) {
      const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
      const minutes = Math.floor(elapsed / 60);
      const seconds = elapsed % 60;
      timerEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
  };

  // Update immediately
  updateTimer();

  // Update every second (only when not paused)
  controlBarTimer = setInterval(() => {
    if (!isPaused) {
      updateTimer();
    }
  }, 1000);
}

function removeControlBar() {
  if (controlBarTimer) {
    clearInterval(controlBarTimer);
    controlBarTimer = null;
  }
  if (controlBar) {
    controlBar.remove();
    controlBar = null;
  }
  const styles = document.getElementById('screensense-control-bar-styles');
  if (styles) {
    styles.remove();
  }
  isPaused = false;
}

// ============================================
// Recording Panel (Loom-style popup)
// ============================================

async function createRecordingPanel() {
  // Check if user is logged in (uses chrome.storage for cross-origin support)
  const authToken = await getAuthToken();
  if (!authToken) {
    // Show login required message and redirect
    alert('Please sign in to ScreenSense to record videos');
    window.open(SCREENSENSE_URL + '/login', '_blank');
    return;
  }

  // Remove existing panel if any
  if (recordingPanel) {
    recordingPanel.remove();
  }

  // Create overlay backdrop
  const overlay = document.createElement('div');
  overlay.id = 'screensense-panel-overlay';
  overlay.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999998;
    animation: screensense-fade-in 0.2s ease-out;
  `;

  // Create panel
  recordingPanel = document.createElement('div');
  recordingPanel.id = 'screensense-recording-panel';
  recordingPanel.style.cssText = `
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 16px;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.2);
    z-index: 999999;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    width: 380px;
    overflow: hidden;
    animation: screensense-scale-in 0.3s ease-out;
  `;

  // Panel content
  recordingPanel.innerHTML = `
    <style>
      @keyframes screensense-fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
      }
      @keyframes screensense-scale-in {
        from { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
        to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
      }
      #screensense-recording-panel * {
        box-sizing: border-box;
      }
      .sb-option-btn {
        width: 100%;
        padding: 14px 16px;
        background: #f9fafb;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.2s ease;
        text-align: left;
      }
      .sb-option-btn:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
      }
      .sb-option-btn.selected {
        background: #fff7ed;
        border-color: #ea580c;
      }
      .sb-option-btn .sb-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }
      .sb-option-btn .sb-icon.screen { background: #dbeafe; }
      .sb-option-btn .sb-icon.camera { background: #fce7f3; }
      .sb-option-btn .sb-icon.both { background: #f3e8ff; }
      .sb-toggle {
        width: 44px;
        height: 24px;
        background: #e5e7eb;
        border-radius: 12px;
        position: relative;
        cursor: pointer;
        transition: all 0.2s ease;
      }
      .sb-toggle.active {
        background: #ea580c;
      }
      .sb-toggle::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 20px;
        height: 20px;
        background: white;
        border-radius: 50%;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
      }
      .sb-toggle.active::after {
        left: 22px;
      }
    </style>

    <!-- Header -->
    <div style="padding: 20px 20px 0; display: flex; align-items: center; justify-content: space-between;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <circle cx="12" cy="12" r="4" fill="#ea580c"/>
        </svg>
        <span style="font-weight: 700; font-size: 18px; color: #111827;">ScreenSense</span>
      </div>
      <button id="sb-close-panel" style="
        background: none;
        border: none;
        padding: 8px;
        cursor: pointer;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
      ">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2">
          <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Recording Options -->
    <div style="padding: 20px;">
      <p style="font-size: 13px; color: #6b7280; margin: 0 0 16px;">What would you like to record?</p>

      <div style="display: flex; flex-direction: column; gap: 10px;">
        <!-- Screen Only -->
        <button class="sb-option-btn selected" data-mode="screen">
          <div class="sb-icon screen">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
              <rect x="2" y="3" width="20" height="14" rx="2"/>
              <path d="M8 21h8M12 17v4"/>
            </svg>
          </div>
          <div style="flex: 1;">
            <div style="font-weight: 600; font-size: 14px; color: #111827;">Screen Only</div>
            <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">Record your screen or a window</div>
          </div>
        </button>

        <!-- Screen + Camera -->
        <button class="sb-option-btn" data-mode="screen-camera">
          <div class="sb-icon both">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9333ea" stroke-width="2">
              <rect x="2" y="3" width="20" height="14" rx="2"/>
              <circle cx="18" cy="18" r="4" fill="#fce7f3" stroke="#ec4899"/>
            </svg>
          </div>
          <div style="flex: 1;">
            <div style="font-weight: 600; font-size: 14px; color: #111827;">Screen + Camera</div>
            <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">Show your face in the corner</div>
          </div>
        </button>

        <!-- Camera Only -->
        <button class="sb-option-btn" data-mode="camera">
          <div class="sb-icon camera">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ec4899" stroke-width="2">
              <path d="M23 7l-7 5 7 5V7z"/>
              <rect x="1" y="5" width="15" height="14" rx="2"/>
            </svg>
          </div>
          <div style="flex: 1;">
            <div style="font-weight: 600; font-size: 14px; color: #111827;">Camera Only</div>
            <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">Record yourself talking</div>
          </div>
        </button>
      </div>
    </div>

    <!-- Audio Option -->
    <div style="padding: 0 20px 20px;">
      <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f9fafb; border-radius: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2">
            <path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"/>
            <path d="M19 10v2a7 7 0 01-14 0v-2M12 19v4M8 23h8"/>
          </svg>
          <span style="font-size: 14px; color: #374151;">Microphone</span>
        </div>
        <div class="sb-toggle active" id="sb-mic-toggle"></div>
      </div>
    </div>

    <!-- Start Button -->
    <div style="padding: 0 20px 20px;">
      <button id="sb-start-recording" style="
        width: 100%;
        padding: 14px 24px;
        background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(234, 88, 12, 0.3);
      ">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
          <circle cx="12" cy="12" r="8"/>
        </svg>
        Start Recording
      </button>
    </div>
  `;

  // Add elements to page
  document.body.appendChild(overlay);
  document.body.appendChild(recordingPanel);

  // State
  let selectedMode = 'screen';
  let micEnabled = true;

  // Close panel handlers
  const closePanel = () => {
    overlay.remove();
    recordingPanel.remove();
    recordingPanel = null;
  };

  overlay.addEventListener('click', closePanel);
  recordingPanel.querySelector('#sb-close-panel').addEventListener('click', closePanel);

  // Mode selection
  const optionBtns = recordingPanel.querySelectorAll('.sb-option-btn');
  optionBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      optionBtns.forEach(b => b.classList.remove('selected'));
      btn.classList.add('selected');
      selectedMode = btn.dataset.mode;
    });
  });

  // Mic toggle
  const micToggle = recordingPanel.querySelector('#sb-mic-toggle');
  micToggle.addEventListener('click', () => {
    micEnabled = !micEnabled;
    micToggle.classList.toggle('active', micEnabled);
  });

  // Start recording
  recordingPanel.querySelector('#sb-start-recording').addEventListener('click', async () => {
    const options = {
      screen: selectedMode === 'screen' || selectedMode === 'screen-camera',
      camera: selectedMode === 'camera' || selectedMode === 'screen-camera',
      microphone: micEnabled
    };

    // Close panel first
    closePanel();

    // Start recording
    try {
      await initRecording(options);
      recordingStartTime = Date.now();

      // Notify background script (non-critical, wrapped for safety)
      safeSendMessage({
        action: 'websiteStateChanged',
        state: {
          isRecording: true,
          isPaused: false,
          startTime: recordingStartTime,
          source: 'website'
        }
      });

      notifyWebsiteOfState(true, false);

      // Notify website of successful start
      window.dispatchEvent(new CustomEvent('screensense:extension:response', {
        detail: { command: 'start', success: true }
      }));
    } catch (error) {
      console.error('Failed to start recording:', error);
      window.dispatchEvent(new CustomEvent('screensense:extension:response', {
        detail: { command: 'start', success: false, error: error.message }
      }));
    }
  });

  // Handle Escape key
  const handleEscape = (e) => {
    if (e.key === 'Escape') {
      closePanel();
      document.removeEventListener('keydown', handleEscape);
    }
  };
  document.addEventListener('keydown', handleEscape);
}

function makeDraggableControlBar(element) {
  let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
  let isDragging = false;

  element.onmousedown = dragMouseDown;

  function dragMouseDown(e) {
    // Don't drag if clicking on buttons
    if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
      return;
    }
    e.preventDefault();
    isDragging = true;
    pos3 = e.clientX;
    pos4 = e.clientY;
    document.onmouseup = closeDragElement;
    document.onmousemove = elementDrag;
  }

  function elementDrag(e) {
    if (!isDragging) return;
    e.preventDefault();
    pos1 = pos3 - e.clientX;
    pos2 = pos4 - e.clientY;
    pos3 = e.clientX;
    pos4 = e.clientY;

    // Remove the transform and use direct positioning
    element.style.transform = 'none';

    // Calculate new position with bounds checking
    let newTop = element.offsetTop - pos2;
    let newLeft = element.offsetLeft - pos1;

    // Get element dimensions
    const rect = element.getBoundingClientRect();
    const minVisible = 50; // Minimum pixels that must remain visible

    // Bound to viewport
    newTop = Math.max(10, Math.min(newTop, window.innerHeight - minVisible));
    newLeft = Math.max(-rect.width + minVisible, Math.min(newLeft, window.innerWidth - minVisible));

    element.style.top = newTop + 'px';
    element.style.left = newLeft + 'px';
  }

  function closeDragElement() {
    isDragging = false;
    document.onmouseup = null;
    document.onmousemove = null;
  }
}

function makeDraggable(element) {
  let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;

  element.onmousedown = dragMouseDown;

  function dragMouseDown(e) {
    e.preventDefault();
    pos3 = e.clientX;
    pos4 = e.clientY;
    document.onmouseup = closeDragElement;
    document.onmousemove = elementDrag;
  }

  function elementDrag(e) {
    e.preventDefault();
    pos1 = pos3 - e.clientX;
    pos2 = pos4 - e.clientY;
    pos3 = e.clientX;
    pos4 = e.clientY;

    // Calculate new position with bounds checking
    let newTop = element.offsetTop - pos2;
    let newLeft = element.offsetLeft - pos1;

    // Get element dimensions
    const rect = element.getBoundingClientRect();
    const minVisible = 50; // Minimum pixels that must remain visible

    // Bound to viewport
    newTop = Math.max(10, Math.min(newTop, window.innerHeight - minVisible));
    newLeft = Math.max(-rect.width + minVisible, Math.min(newLeft, window.innerWidth - minVisible));

    element.style.top = newTop + 'px';
    element.style.left = newLeft + 'px';
    element.style.bottom = 'auto';
    element.style.right = 'auto';
  }

  function closeDragElement() {
    document.onmouseup = null;
    document.onmousemove = null;
  }
}

function pauseRecording() {
  if (mediaRecorder && mediaRecorder.state === 'recording') {
    mediaRecorder.pause();
    updateControlBarPauseState(true);
    console.log('Recording paused');
  }
}

function resumeRecording() {
  if (mediaRecorder && mediaRecorder.state === 'paused') {
    mediaRecorder.resume();
    updateControlBarPauseState(false);
    console.log('Recording resumed');
  }
}

async function stopRecording() {
  return new Promise((resolve) => {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
      mediaRecorder.onstop = async () => {
        console.log('Recording stopped');

        // Auto-upload to backend
        if (recordedChunks.length > 0) {
          const blob = new Blob(recordedChunks, { type: 'video/webm' });
          try {
            await uploadToBackend(blob);
            recordedChunks = []; // Clear after successful upload
          } catch (error) {
            console.error('Auto-upload failed:', error);
            // Keep chunks for manual download
          }
        }

        cleanup();
        resolve();
      };
      mediaRecorder.stop();
    } else {
      cleanup();
      resolve();
    }
  });
}

async function downloadRecording() {
  if (recordedChunks.length === 0) {
    console.error('No recording data available');
    return { success: false, error: 'No recording data' };
  }

  const blob = new Blob(recordedChunks, { type: 'video/webm' });

  // Try to upload to backend first
  try {
    const result = await uploadToBackend(blob);
    if (result.success) {
      console.log('Video uploaded successfully:', result);
      // Clear recorded chunks after successful upload
      recordedChunks = [];
      return result;
    }
  } catch (error) {
    console.error('Failed to upload to backend, falling back to download:', error);
  }

  // Fallback: download locally if upload fails
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.style.display = 'none';
  a.href = url;
  a.download = `screensense-recording-${Date.now()}.webm`;

  document.body.appendChild(a);
  a.click();

  setTimeout(() => {
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }, 100);

  // Clear recorded chunks
  recordedChunks = [];
  return { success: true, downloaded: true };
}

async function uploadToBackend(blob) {
  const API_URL = 'http://localhost:8000/api/videos';

  // Get auth token from localStorage or chrome.storage
  const authToken = await getAuthToken();
  if (!authToken) {
    console.error('No auth token found - user not logged in');
    // Redirect to ScreenSense login
    window.location.href = SCREENSENSE_URL + '/login';
    throw new Error('Not authenticated');
  }

  // Calculate duration from recording time
  const duration = recordingStartTime ? Math.floor((Date.now() - recordingStartTime) / 1000) : 0;

  // Generate default title
  const timestamp = new Date().toLocaleString();
  const title = `Screen Recording ${timestamp}`;

  // Create FormData for upload
  const formData = new FormData();
  formData.append('video', blob, `recording-${Date.now()}.webm`);
  formData.append('title', title);
  formData.append('duration', duration.toString());
  formData.append('is_public', '1');

  console.log('Uploading video to backend...', { title, duration });

  const response = await fetch(API_URL, {
    method: 'POST',
    body: formData,
    headers: {
      'Accept': 'application/json',
      'Authorization': `Bearer ${authToken}`,
    }
  });

  if (!response.ok) {
    // Handle 401 - redirect to login
    if (response.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');
      window.location.href = '/login';
      throw new Error('Session expired. Please log in again.');
    }
    const errorData = await response.json().catch(() => ({}));
    throw new Error(errorData.message || `Upload failed with status ${response.status}`);
  }

  const data = await response.json();
  console.log('Video uploaded:', data);

  // Notify the page about the successful upload
  if (isScreenSensePage()) {
    window.dispatchEvent(new CustomEvent('screensense:extension:uploadComplete', {
      detail: {
        videoId: data.video.id,
        shareUrl: data.video.share_url,
        title: data.video.title
      }
    }));
  }

  // Show notification with share URL
  showUploadNotification(data.video.share_url);

  return {
    success: true,
    videoId: data.video.id,
    shareUrl: data.video.share_url
  };
}

function showUploadNotification(shareUrl) {
  // Create a notification overlay
  const notification = document.createElement('div');
  notification.id = 'screensense-upload-notification';
  notification.style.cssText = `
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 16px 20px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    z-index: 999999;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 320px;
    animation: screensense-slide-in 0.3s ease-out;
  `;

  notification.innerHTML = `
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <span style="font-weight: 600; font-size: 14px;">Video Saved!</span>
    </div>
    <div style="background: rgba(255,255,255,0.2); padding: 8px 12px; border-radius: 6px; margin-bottom: 12px;">
      <input type="text" value="${shareUrl}" readonly style="
        background: transparent;
        border: none;
        color: white;
        width: 100%;
        font-size: 12px;
        outline: none;
      " id="screensense-share-url"/>
    </div>
    <div style="display: flex; gap: 8px;">
      <button id="screensense-copy-btn" style="
        flex: 1;
        background: white;
        color: #059669;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
      ">Copy Link</button>
      <button id="screensense-close-btn" style="
        background: rgba(255,255,255,0.2);
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 13px;
        cursor: pointer;
      ">✕</button>
    </div>
  `;

  // Add animation style
  const style = document.createElement('style');
  style.textContent = `
    @keyframes screensense-slide-in {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
  `;
  document.head.appendChild(style);

  document.body.appendChild(notification);

  // Copy button handler
  document.getElementById('screensense-copy-btn').addEventListener('click', () => {
    navigator.clipboard.writeText(shareUrl).then(() => {
      document.getElementById('screensense-copy-btn').textContent = 'Copied!';
      setTimeout(() => {
        document.getElementById('screensense-copy-btn').textContent = 'Copy Link';
      }, 2000);
    });
  });

  // Close button handler
  document.getElementById('screensense-close-btn').addEventListener('click', () => {
    notification.remove();
  });

  // Auto-close after 30 seconds
  setTimeout(() => {
    if (notification.parentNode) {
      notification.remove();
    }
  }, 30000);
}

function cleanup() {
  // Remove the control bar
  removeControlBar();

  // Stop all tracks
  if (screenStream) {
    screenStream.getTracks().forEach(track => track.stop());
    screenStream = null;
  }

  if (cameraStream) {
    cameraStream.getTracks().forEach(track => track.stop());
    cameraStream = null;
  }

  if (combinedStream) {
    // Stop microphone stream if stored
    if (combinedStream._microphoneStream) {
      combinedStream._microphoneStream.getTracks().forEach(track => track.stop());
    }
    combinedStream.getTracks().forEach(track => track.stop());
    combinedStream = null;
  }

  // Remove camera overlay
  if (cameraOverlay) {
    cameraOverlay.remove();
    cameraOverlay = null;
    cameraVideo = null;
  }

  mediaRecorder = null;
}

// ============================================
// Floating Record Panel (Loom-style)
// ============================================

let floatingPanel = null;

function showFloatingRecordPanel() {
  // Remove existing panel if any
  if (floatingPanel) {
    floatingPanel.remove();
    floatingPanel = null;
    return; // Toggle behavior
  }

  // Load saved options
  chrome.storage.local.get(['recordingOptions'], (result) => {
    const options = result.recordingOptions || { camera: false, microphone: true };
    createFloatingPanel(options);
  });
}

// Expose globally for duplicate script handling
window.__screensenseShowPanel = showFloatingRecordPanel;

function createFloatingPanel(savedOptions) {
  // Create overlay to detect outside clicks
  const overlay = document.createElement('div');
  overlay.id = 'screensense-floating-overlay';
  overlay.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 2147483645;
    background: transparent;
  `;

  // Create the floating panel
  floatingPanel = document.createElement('div');
  floatingPanel.id = 'screensense-floating-panel';
  floatingPanel.style.cssText = `
    position: fixed;
    top: 80px;
    right: 20px;
    width: 320px;
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255,255,255,0.1);
    z-index: 2147483646;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: white;
    animation: screensense-panel-slide-in 0.3s ease-out;
  `;

  floatingPanel.innerHTML = `
    <style>
      @keyframes screensense-panel-slide-in {
        from { opacity: 0; transform: translateY(-10px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
      }
      #screensense-floating-panel * {
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      }
      .ss-toggle {
        position: relative;
        width: 44px;
        height: 24px;
        cursor: pointer;
      }
      .ss-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
      }
      .ss-toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.2);
        transition: 0.3s;
        border-radius: 24px;
      }
      .ss-toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
      }
      .ss-toggle input:checked + .ss-toggle-slider {
        background-color: #ea580c;
      }
      .ss-toggle input:checked + .ss-toggle-slider:before {
        transform: translateX(20px);
      }
    </style>

    <!-- Header -->
    <div style="padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: space-between;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <circle cx="12" cy="12" r="4" fill="#ea580c"/>
        </svg>
        <span style="font-size: 16px; font-weight: 600; color: #ea580c;">ScreenSense</span>
      </div>
      <button id="ss-close-btn" style="background: none; border: none; cursor: pointer; padding: 4px; color: #9ca3af; transition: color 0.2s;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

    <!-- Options -->
    <div style="padding: 20px;">
      <div style="font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">Recording Options</div>

      <!-- Camera Option -->
      <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
        <div style="display: flex; align-items: center; gap: 12px;">
          <div id="ss-camera-icon" style="width: 36px; height: 36px; background: rgba(255,255,255,0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2">
              <path d="M23 7l-7 5 7 5V7z"/>
              <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
            </svg>
          </div>
          <div>
            <div style="font-size: 14px; font-weight: 500;">Camera</div>
            <div style="font-size: 11px; color: #6b7280;">Show your face</div>
          </div>
        </div>
        <label class="ss-toggle">
          <input type="checkbox" id="ss-camera-toggle" ${savedOptions.camera ? 'checked' : ''}>
          <span class="ss-toggle-slider"></span>
        </label>
      </div>

      <!-- Microphone Option -->
      <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0;">
        <div style="display: flex; align-items: center; gap: 12px;">
          <div id="ss-mic-icon" style="width: 36px; height: 36px; background: ${savedOptions.microphone ? 'rgba(234, 88, 12, 0.2)' : 'rgba(255,255,255,0.1)'}; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="${savedOptions.microphone ? '#ea580c' : '#9ca3af'}" stroke-width="2">
              <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
              <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
              <line x1="12" y1="19" x2="12" y2="23"/>
              <line x1="8" y1="23" x2="16" y2="23"/>
            </svg>
          </div>
          <div>
            <div style="font-size: 14px; font-weight: 500;">Microphone</div>
            <div style="font-size: 11px; color: #6b7280;">Record audio</div>
          </div>
        </div>
        <label class="ss-toggle">
          <input type="checkbox" id="ss-mic-toggle" ${savedOptions.microphone ? 'checked' : ''}>
          <span class="ss-toggle-slider"></span>
        </label>
      </div>
    </div>

    <!-- Record Button -->
    <div style="padding: 0 20px 20px;">
      <button id="ss-record-btn" style="
        width: 100%;
        padding: 14px 20px;
        background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 4px 12px rgba(234, 88, 12, 0.3);
        transition: all 0.2s ease;
      ">
        <div style="width: 16px; height: 16px; background: white; border-radius: 50%;"></div>
        Start Recording
      </button>
    </div>
  `;

  document.body.appendChild(overlay);
  document.body.appendChild(floatingPanel);

  // Close button handler
  document.getElementById('ss-close-btn').addEventListener('click', closeFloatingPanel);

  // Click outside to close
  overlay.addEventListener('click', closeFloatingPanel);

  // Toggle handlers
  const cameraToggle = document.getElementById('ss-camera-toggle');
  const micToggle = document.getElementById('ss-mic-toggle');
  const cameraIcon = document.getElementById('ss-camera-icon');
  const micIcon = document.getElementById('ss-mic-icon');

  cameraToggle.addEventListener('change', () => {
    if (cameraToggle.checked) {
      cameraIcon.style.background = 'rgba(234, 88, 12, 0.2)';
      cameraIcon.querySelector('svg').setAttribute('stroke', '#ea580c');
    } else {
      cameraIcon.style.background = 'rgba(255,255,255,0.1)';
      cameraIcon.querySelector('svg').setAttribute('stroke', '#9ca3af');
    }
    saveFloatingPanelOptions();
  });

  micToggle.addEventListener('change', () => {
    if (micToggle.checked) {
      micIcon.style.background = 'rgba(234, 88, 12, 0.2)';
      micIcon.querySelector('svg').setAttribute('stroke', '#ea580c');
    } else {
      micIcon.style.background = 'rgba(255,255,255,0.1)';
      micIcon.querySelector('svg').setAttribute('stroke', '#9ca3af');
    }
    saveFloatingPanelOptions();
  });

  // Initialize camera icon based on saved state
  if (savedOptions.camera) {
    cameraIcon.style.background = 'rgba(234, 88, 12, 0.2)';
    cameraIcon.querySelector('svg').setAttribute('stroke', '#ea580c');
  }

  // Record button handler
  document.getElementById('ss-record-btn').addEventListener('click', () => {
    const options = {
      camera: cameraToggle.checked,
      microphone: micToggle.checked
    };

    // Save options and set auto-start flag
    chrome.storage.local.set({
      autoStartRecording: true,
      recordingOptions: options
    }, () => {
      // Navigate to record page
      window.location.href = SCREENSENSE_URL + '/record?autostart=true';
    });
  });
}

function saveFloatingPanelOptions() {
  const cameraToggle = document.getElementById('ss-camera-toggle');
  const micToggle = document.getElementById('ss-mic-toggle');

  if (cameraToggle && micToggle) {
    chrome.storage.local.set({
      recordingOptions: {
        camera: cameraToggle.checked,
        microphone: micToggle.checked
      }
    });
  }
}

function closeFloatingPanel() {
  const overlay = document.getElementById('screensense-floating-overlay');
  if (overlay) overlay.remove();
  if (floatingPanel) {
    floatingPanel.remove();
    floatingPanel = null;
  }
}

})(); // End of IIFE
