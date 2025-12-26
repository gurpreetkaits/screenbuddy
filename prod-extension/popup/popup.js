// ScreenSense Extension Popup - Production
const SCREENSENSE_URL = 'https://record.gurpreetkait.in';

// UI Elements
const setupView = document.getElementById('setupView');
const recordingView = document.getElementById('recordingView');
const startRecordingBtn = document.getElementById('startRecordingBtn');
const openAppBtn = document.getElementById('openAppBtn');
const pauseBtn = document.getElementById('pauseBtn');
const resumeBtn = document.getElementById('resumeBtn');
const stopRecordingBtn = document.getElementById('stopRecordingBtn');
const recordingTimeDisplay = document.getElementById('recordingTimeDisplay');

// State
let recordingStartTime = null;
let timerInterval = null;

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
  // Check if there's an ongoing recording
  const { isRecording, isPaused, startTime } = await chrome.storage.local.get(['isRecording', 'isPaused', 'startTime']);

  if (isRecording) {
    recordingStartTime = startTime;
    showRecordingView();
    startTimer();

    if (isPaused) {
      showPauseButton(false);
    }
  }
});

// Start Recording - Opens website and shows panel
startRecordingBtn.addEventListener('click', async () => {
  // Find if ScreenSense tab is already open
  const tabs = await chrome.tabs.query({});
  let screensenseTab = tabs.find(tab =>
    tab.url && tab.url.includes('record.gurpreetkait.in')
  );

  if (screensenseTab) {
    // Focus the existing tab and show panel
    await chrome.tabs.update(screensenseTab.id, { active: true });
    await chrome.windows.update(screensenseTab.windowId, { focused: true });

    // Small delay to ensure tab is focused, then show panel
    setTimeout(() => {
      chrome.tabs.sendMessage(screensenseTab.id, { action: 'showRecordingPanel' });
    }, 100);
  } else {
    // Open new tab with ScreenSense
    const newTab = await chrome.tabs.create({ url: SCREENSENSE_URL });

    // Wait for page to load, then show panel
    chrome.tabs.onUpdated.addListener(function listener(tabId, info) {
      if (tabId === newTab.id && info.status === 'complete') {
        chrome.tabs.onUpdated.removeListener(listener);
        setTimeout(() => {
          chrome.tabs.sendMessage(newTab.id, { action: 'showRecordingPanel' });
        }, 500);
      }
    });
  }

  // Close popup
  window.close();
});

// Open App Button
openAppBtn.addEventListener('click', async () => {
  const tabs = await chrome.tabs.query({});
  let screensenseTab = tabs.find(tab =>
    tab.url && tab.url.includes('record.gurpreetkait.in')
  );

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
      let screensenseTab = tabs.find(tab =>
        tab.url && tab.url.includes('record.gurpreetkait.in')
      );

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
