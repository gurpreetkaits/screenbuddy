// Background service worker for ScreenSense extension - Production

let mediaRecorder = null;
let recordedChunks = [];
let currentTabId = null;
let isRecording = false;
let isPaused = false;

// Handle messages from popup and content scripts
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  switch (message.action) {
    case 'startRecording':
      handleStartRecording(message.options, sender, sendResponse);
      return true; // Will respond asynchronously

    case 'pauseRecording':
      handlePauseRecording(sendResponse);
      return true;

    case 'resumeRecording':
      handleResumeRecording(sendResponse);
      return true;

    case 'stopRecording':
      handleStopRecording(sendResponse);
      return true;

    case 'downloadRecording':
      handleDownloadRecording(sendResponse);
      return true;

    case 'websiteStateChanged':
      handleWebsiteStateChanged(message.state, sender, sendResponse);
      return true;

    case 'getRecordingState':
      sendResponse({ isRecording, isPaused, currentTabId });
      return true;

    default:
      sendResponse({ success: false, error: 'Unknown action' });
  }
});

// Handle state changes from the website
async function handleWebsiteStateChanged(state, sender, sendResponse) {
  console.log('Website state changed:', state, 'from tab:', sender.tab?.id);

  if (state.source === 'website') {
    // Website started/stopped recording, sync to extension state
    if (state.isRecording && !isRecording) {
      // Website started recording - track the tab ID!
      isRecording = true;
      isPaused = state.isPaused;
      currentTabId = sender.tab?.id || null;
      chrome.storage.local.set({
        isRecording: true,
        isPaused: state.isPaused,
        startTime: state.startTime,
        source: 'website'
      });
    } else if (!state.isRecording && isRecording) {
      // Website stopped recording
      isRecording = false;
      isPaused = false;
      currentTabId = null;
      chrome.storage.local.set({
        isRecording: false,
        isPaused: false,
        source: null
      });
    } else if (state.isPaused !== isPaused) {
      // Pause state changed
      isPaused = state.isPaused;
      chrome.storage.local.set({ isPaused: state.isPaused });
    }

    // Notify all ScreenSense tabs about the state change
    syncStateToAllTabs();
  }

  sendResponse({ success: true });
}

// Sync recording state to all tabs running ScreenSense
async function syncStateToAllTabs() {
  try {
    const tabs = await chrome.tabs.query({});
    for (const tab of tabs) {
      if (tab.url && tab.url.includes('record.gurpreetkait.in')) {
        try {
          chrome.tabs.sendMessage(tab.id, {
            action: 'syncState',
            isRecording,
            isPaused
          });
        } catch (e) {
          // Tab might not have content script, ignore
        }
      }
    }
  } catch (e) {
    console.error('Error syncing state to tabs:', e);
  }
}

async function handleStartRecording(options, sender, sendResponse) {
  try {
    // Get the active tab
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });

    if (!tab || !tab.id) {
      sendResponse({ success: false, error: 'No active tab found' });
      return;
    }

    currentTabId = tab.id;
    const tabUrl = tab.url || '';

    console.log('Starting recording on tab:', currentTabId, 'URL:', tabUrl);

    // Check if we can inject into this tab (not chrome:// or extension pages)
    if (tabUrl.startsWith('chrome://') || tabUrl.startsWith('chrome-extension://') || tabUrl.startsWith('about:') || tabUrl === '') {
      sendResponse({ success: false, error: 'Cannot record on this page. Please navigate to a website.' });
      return;
    }

    // Try to inject content script first (in case it's not loaded)
    try {
      await chrome.scripting.executeScript({
        target: { tabId: currentTabId },
        files: ['scripts/content.js']
      });
      console.log('Content script injected successfully');
    } catch (injectError) {
      // Script might already be injected, or page doesn't allow it
      console.log('Content script injection skipped:', injectError.message);
    }

    // Small delay to ensure content script is ready
    await new Promise(resolve => setTimeout(resolve, 100));

    // Send message to content script to start recording
    chrome.tabs.sendMessage(currentTabId, {
      action: 'initRecording',
      options
    }, (response) => {
      if (chrome.runtime.lastError) {
        console.error('Error sending message to content script:', chrome.runtime.lastError);
        sendResponse({ success: false, error: 'Could not connect to page. Try refreshing the page.' });
        return;
      }

      if (response && response.success) {
        isRecording = true;
        isPaused = false;
        chrome.storage.local.set({
          isRecording: true,
          isPaused: false,
          startTime: Date.now(),
          source: 'extension'
        });
        syncStateToAllTabs();
        sendResponse({ success: true });
      } else {
        sendResponse({ success: false, error: response?.error || 'Failed to start recording' });
      }
    });
  } catch (error) {
    console.error('Error starting recording:', error);
    sendResponse({ success: false, error: error.message });
  }
}

function handlePauseRecording(sendResponse) {
  if (!isRecording || !currentTabId) {
    sendResponse({ success: false, error: 'No active recording' });
    return;
  }

  chrome.tabs.sendMessage(currentTabId, { action: 'pauseRecording' }, async (response) => {
    if (response && response.success) {
      isPaused = true;
      chrome.storage.local.set({ isPaused: true });
      syncStateToAllTabs();

      // Update all control bars to show paused state
      await syncControlBarPauseState(true);

      sendResponse({ success: true });
    } else {
      sendResponse({ success: false, error: 'Failed to pause recording' });
    }
  });
}

function handleResumeRecording(sendResponse) {
  if (!isRecording || !currentTabId) {
    sendResponse({ success: false, error: 'No active recording' });
    return;
  }

  chrome.tabs.sendMessage(currentTabId, { action: 'resumeRecording' }, async (response) => {
    if (response && response.success) {
      isPaused = false;
      chrome.storage.local.set({ isPaused: false });
      syncStateToAllTabs();

      // Update all control bars to show resumed state
      await syncControlBarPauseState(false);

      sendResponse({ success: true });
    } else {
      sendResponse({ success: false, error: 'Failed to resume recording' });
    }
  });
}

// Sync pause state to all control bars
async function syncControlBarPauseState(paused) {
  try {
    const tabs = await chrome.tabs.query({});
    for (const tab of tabs) {
      try {
        chrome.tabs.sendMessage(tab.id, {
          action: 'updateControlBarPause',
          isPaused: paused
        });
      } catch (e) {
        // Tab might not have content script, ignore
      }
    }
  } catch (e) {
    console.error('Error syncing control bar pause state:', e);
  }
}

function handleStopRecording(sendResponse) {
  if (!isRecording || !currentTabId) {
    sendResponse({ success: false, error: 'No active recording' });
    return;
  }

  chrome.tabs.sendMessage(currentTabId, { action: 'stopRecording' }, async (response) => {
    if (response && response.success) {
      isRecording = false;
      isPaused = false;
      chrome.storage.local.set({ isRecording: false, isPaused: false, source: null });
      syncStateToAllTabs();

      // Remove control bar from all tabs
      await removeControlBarFromAllTabs();

      sendResponse({ success: true });
    } else {
      sendResponse({ success: false, error: 'Failed to stop recording' });
    }
  });
}

// Remove control bar from all tabs
async function removeControlBarFromAllTabs() {
  try {
    const tabs = await chrome.tabs.query({});
    for (const tab of tabs) {
      try {
        chrome.tabs.sendMessage(tab.id, { action: 'hideControlBar' });
      } catch (e) {
        // Tab might not have content script, ignore
      }
    }
  } catch (e) {
    console.error('Error removing control bars:', e);
  }
}

function handleDownloadRecording(sendResponse) {
  if (!currentTabId) {
    sendResponse({ success: false, error: 'No recording available' });
    return;
  }

  chrome.tabs.sendMessage(currentTabId, { action: 'downloadRecording' }, (response) => {
    if (response && response.success) {
      sendResponse({ success: true });
    } else {
      sendResponse({ success: false, error: 'Failed to download recording' });
    }
  });
}

// Clean up when tabs are closed
chrome.tabs.onRemoved.addListener((tabId) => {
  if (tabId === currentTabId) {
    isRecording = false;
    isPaused = false;
    currentTabId = null;
    chrome.storage.local.set({ isRecording: false, isPaused: false });
  }
});

// Show control bar on active tab when switching tabs during recording
chrome.tabs.onActivated.addListener(async (activeInfo) => {
  if (isRecording) {
    // Get recording start time from storage
    const { startTime } = await chrome.storage.local.get(['startTime']);

    // Show control bar on the newly active tab
    try {
      await chrome.tabs.sendMessage(activeInfo.tabId, {
        action: 'showControlBar',
        isPaused,
        startTime
      });
    } catch (e) {
      // Tab might not have content script, ignore
      console.log('Could not show control bar on tab:', e.message);
    }
  }
});

// Also show control bar when a tab is updated (e.g., page refresh or navigation)
chrome.tabs.onUpdated.addListener(async (tabId, changeInfo, tab) => {
  if (changeInfo.status === 'complete' && isRecording) {
    // Check if this is the active tab
    const [activeTab] = await chrome.tabs.query({ active: true, currentWindow: true });
    if (activeTab && activeTab.id === tabId) {
      const { startTime } = await chrome.storage.local.get(['startTime']);

      // Small delay to ensure content script is loaded
      setTimeout(async () => {
        try {
          await chrome.tabs.sendMessage(tabId, {
            action: 'showControlBar',
            isPaused,
            startTime
          });
        } catch (e) {
          console.log('Could not show control bar on updated tab:', e.message);
        }
      }, 200);
    }
  }
});
