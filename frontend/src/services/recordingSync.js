/**
 * Recording Sync Service
 * Synchronizes recording state between the website and browser extension
 */

const STORAGE_KEY = 'screensense_recording_state';
const BROADCAST_CHANNEL = 'screensense_recording';

class RecordingSyncService {
  constructor() {
    this.listeners = new Set();
    this.state = {
      isRecording: false,
      isPaused: false,
      startTime: null,
      source: 'website' // 'website' or 'extension'
    };

    // Initialize BroadcastChannel for cross-tab sync
    if (typeof BroadcastChannel !== 'undefined') {
      this.channel = new BroadcastChannel(BROADCAST_CHANNEL);
      this.channel.onmessage = (event) => this.handleBroadcast(event.data);
    }

    // Listen for extension messages via custom events
    window.addEventListener('screensense:extension:state', (event) => {
      this.handleExtensionState(event.detail);
    });

    // Listen for storage changes (cross-tab sync fallback)
    window.addEventListener('storage', (event) => {
      if (event.key === STORAGE_KEY && event.newValue) {
        const state = JSON.parse(event.newValue);
        this.updateState(state, false);
      }
    });

    // Load initial state from storage
    this.loadState();
  }

  loadState() {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored) {
        const state = JSON.parse(stored);
        // Only restore if recording is active (within last 2 hours)
        if (state.isRecording && state.startTime) {
          const elapsed = Date.now() - state.startTime;
          if (elapsed < 2 * 60 * 60 * 1000) { // 2 hours max
            this.state = state;
          }
        }
      }
    } catch (e) {
      console.error('Error loading recording state:', e);
    }
  }

  saveState() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(this.state));
    } catch (e) {
      console.error('Error saving recording state:', e);
    }
  }

  handleBroadcast(data) {
    if (data.type === 'stateUpdate') {
      this.updateState(data.state, false);
    }
  }

  handleExtensionState(detail) {
    console.log('Received extension state:', detail);
    this.updateState({
      isRecording: detail.isRecording,
      isPaused: detail.isPaused,
      startTime: detail.startTime || (detail.isRecording ? Date.now() : null),
      source: 'extension'
    }, false);
  }

  updateState(newState, broadcast = true) {
    const changed =
      this.state.isRecording !== newState.isRecording ||
      this.state.isPaused !== newState.isPaused;

    this.state = { ...this.state, ...newState };
    this.saveState();

    if (changed) {
      this.notifyListeners();

      if (broadcast) {
        this.broadcastState();
        this.notifyExtension();
      }
    }
  }

  broadcastState() {
    if (this.channel) {
      this.channel.postMessage({
        type: 'stateUpdate',
        state: this.state
      });
    }
  }

  notifyExtension() {
    // Dispatch event for extension content script to pick up
    window.dispatchEvent(new CustomEvent('screensense:website:state', {
      detail: this.state
    }));
  }

  notifyListeners() {
    this.listeners.forEach(callback => {
      try {
        callback(this.state);
      } catch (e) {
        console.error('Error in recording state listener:', e);
      }
    });
  }

  // Public API
  getState() {
    return { ...this.state };
  }

  subscribe(callback) {
    this.listeners.add(callback);
    // Immediately call with current state
    callback(this.state);
    return () => this.listeners.delete(callback);
  }

  startRecording(source = 'website') {
    this.updateState({
      isRecording: true,
      isPaused: false,
      startTime: Date.now(),
      source
    });
  }

  pauseRecording() {
    if (this.state.isRecording) {
      this.updateState({ isPaused: true });
    }
  }

  resumeRecording() {
    if (this.state.isRecording) {
      this.updateState({ isPaused: false });
    }
  }

  stopRecording() {
    this.updateState({
      isRecording: false,
      isPaused: false,
      startTime: null,
      source: null
    });
  }

  // Check if recording was started from extension
  isExtensionRecording() {
    return this.state.isRecording && this.state.source === 'extension';
  }

  // Check if recording was started from website
  isWebsiteRecording() {
    return this.state.isRecording && this.state.source === 'website';
  }
}

// Singleton instance
export const recordingSync = new RecordingSyncService();
export default recordingSync;
