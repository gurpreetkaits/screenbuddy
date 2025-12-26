// ScreenSense Extension Popup
// SCREENSENSE_URL is loaded from config.js

// Helper to check if a URL is a ScreenSense URL
function isScreenSenseUrl(url) {
  if (!url) return false;
  return url.includes('localhost:5173') ||
         url.includes('localhost:8000') ||
         url.includes('screensense.in');
}

function isRecordPage(url) {
  return isScreenSenseUrl(url) && url.includes('/record');
}

// UI Elements
const setupView = document.getElementById('setupView');
const recordingView = document.getElementById('recordingView');
const startRecordingBtn = document.getElementById('startRecordingBtn');
const openAppBtn = document.getElementById('openAppBtn');
const pauseBtn = document.getElementById('pauseBtn');
const resumeBtn = document.getElementById('resumeBtn');
const stopRecordingBtn = document.getElementById('stopRecordingBtn');
const recordingTimeDisplay = document.getElementById('recordingTimeDisplay');

// Option Elements
const cameraToggle = document.getElementById('cameraToggle');
const micToggle = document.getElementById('micToggle');
const cameraIcon = document.getElementById('cameraIcon');
const micIcon = document.getElementById('micIcon');

// State
let recordingStartTime = null;
let timerInterval = null;

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
  // Load saved options
  const { recordingOptions, isRecording, isPaused, startTime } = await chrome.storage.local.get([
    'recordingOptions',
    'isRecording',
    'isPaused',
    'startTime'
  ]);

  // Set toggle states from saved options
  if (recordingOptions) {
    cameraToggle.checked = recordingOptions.camera || false;
    micToggle.checked = recordingOptions.microphone !== false; // Default to true
  }

  // Update icon states
  updateIconStates();

  // Check if there's an ongoing recording
  if (isRecording) {
    recordingStartTime = startTime;
    showRecordingView();
    startTimer();

    if (isPaused) {
      showPauseButton(false);
    }
  }
});

// Update icon active states based on toggles
function updateIconStates() {
  if (cameraToggle.checked) {
    cameraIcon.classList.add('active');
  } else {
    cameraIcon.classList.remove('active');
  }

  if (micToggle.checked) {
    micIcon.classList.add('active');
  } else {
    micIcon.classList.remove('active');
  }
}

// Toggle event listeners
cameraToggle.addEventListener('change', () => {
  updateIconStates();
  saveOptions();
});

micToggle.addEventListener('change', () => {
  updateIconStates();
  saveOptions();
});

// Save options to storage
async function saveOptions() {
  await chrome.storage.local.set({
    recordingOptions: {
      camera: cameraToggle.checked,
      microphone: micToggle.checked
    }
  });
}

// Start Recording - Opens record page and triggers auto-start
startRecordingBtn.addEventListener('click', async () => {
  // Debug: Log which URL we're using
  console.log('SCREENSENSE_URL:', SCREENSENSE_URL);
  console.log('ENV:', typeof ENV !== 'undefined' ? ENV : 'undefined');

  // Save current options
  await saveOptions();

  // Set flag to auto-start recording when page loads
  await chrome.storage.local.set({
    autoStartRecording: true,
    recordingOptions: {
      camera: cameraToggle.checked,
      microphone: micToggle.checked
    }
  });

  // Find if ScreenSense record tab is already open
  const tabs = await chrome.tabs.query({});
  let recordTab = tabs.find(tab => isRecordPage(tab.url));

  if (recordTab) {
    // Focus the existing tab and trigger auto-start
    await chrome.tabs.update(recordTab.id, { active: true });
    await chrome.windows.update(recordTab.windowId, { focused: true });

    // Send message to trigger recording
    setTimeout(() => {
      chrome.tabs.sendMessage(recordTab.id, {
        action: 'autoStartRecording',
        options: {
          camera: cameraToggle.checked,
          microphone: micToggle.checked
        }
      });
    }, 100);
  } else {
    // Check if any ScreenSense tab is open
    let screensenseTab = tabs.find(tab => isScreenSenseUrl(tab.url));

    if (screensenseTab) {
      // Navigate existing tab to record page
      await chrome.tabs.update(screensenseTab.id, {
        active: true,
        url: SCREENSENSE_URL + '/record?autostart=true'
      });
      await chrome.windows.update(screensenseTab.windowId, { focused: true });
    } else {
      // Open new tab with record page
      await chrome.tabs.create({ url: SCREENSENSE_URL + '/record?autostart=true' });
    }
  }

  // Close popup
  window.close();
});

// Open App Button
openAppBtn.addEventListener('click', async () => {
  const tabs = await chrome.tabs.query({});
  let screensenseTab = tabs.find(tab => isScreenSenseUrl(tab.url));

  if (screensenseTab) {
    await chrome.tabs.update(screensenseTab.id, { active: true });
    await chrome.windows.update(screensenseTab.windowId, { focused: true });
  } else {
    await chrome.tabs.create({ url: SCREENSENSE_URL });
  }

  window.close();
});

// Pause Recording
pauseBtn.addEventListener('click', () => {
  chrome.runtime.sendMessage({ action: 'pauseRecording' }, (response) => {
    if (response && response.success) {
      showPauseButton(false);
    }
  });
});

// Resume Recording
resumeBtn.addEventListener('click', () => {
  chrome.runtime.sendMessage({ action: 'resumeRecording' }, (response) => {
    if (response && response.success) {
      showPauseButton(true);
    }
  });
});

// Stop Recording
stopRecordingBtn.addEventListener('click', () => {
  chrome.runtime.sendMessage({ action: 'stopRecording' }, async (response) => {
    if (response && response.success) {
      stopTimer();

      // Redirect to ScreenSense to see the video
      const tabs = await chrome.tabs.query({});
      let screensenseTab = tabs.find(tab => isScreenSenseUrl(tab.url));

      if (screensenseTab) {
        await chrome.tabs.update(screensenseTab.id, { active: true, url: SCREENSENSE_URL + '/videos' });
        await chrome.windows.update(screensenseTab.windowId, { focused: true });
      } else {
        await chrome.tabs.create({ url: SCREENSENSE_URL + '/videos' });
      }

      window.close();
    }
  });
});

// Helper Functions
function showRecordingView() {
  setupView.style.display = 'none';
  recordingView.classList.add('active');
}

function showSetupView() {
  setupView.style.display = 'block';
  recordingView.classList.remove('active');
}

function showPauseButton(showPause) {
  if (showPause) {
    pauseBtn.classList.remove('hidden');
    resumeBtn.classList.add('hidden');
  } else {
    pauseBtn.classList.add('hidden');
    resumeBtn.classList.remove('hidden');
  }
}

function startTimer() {
  updateTimer();
  timerInterval = setInterval(updateTimer, 1000);
}

function stopTimer() {
  if (timerInterval) {
    clearInterval(timerInterval);
    timerInterval = null;
  }
}

function updateTimer() {
  if (!recordingStartTime) return;

  const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
  const minutes = Math.floor(elapsed / 60);
  const seconds = elapsed % 60;
  recordingTimeDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

// Listen for storage changes to update UI
chrome.storage.onChanged.addListener((changes, namespace) => {
  if (namespace === 'local') {
    if (changes.isRecording) {
      if (changes.isRecording.newValue) {
        recordingStartTime = changes.startTime?.newValue || Date.now();
        showRecordingView();
        startTimer();
      } else {
        stopTimer();
        showSetupView();
      }
    }
    if (changes.isPaused) {
      showPauseButton(!changes.isPaused.newValue);
    }
  }
});
