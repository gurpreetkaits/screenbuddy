export type RecordingType = 'screen' | 'camera' | 'screen+camera';

export interface RecordingOptions {
  recordingType: RecordingType;
  includeAudio: boolean;
  showCursor: boolean;
  countdownTimer: boolean;
}

export interface RecordedVideo {
  id: string;
  title: string;
  duration: string;
  createdAt: Date;
  blob: Blob;
  url: string;
  thumbnail?: string;
}

export const formatTime = (seconds: number): string => {
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
};

export const generateVideoId = (): string => {
  return Math.random().toString(36).substring(2, 15);
};

export const downloadVideo = (blob: Blob, filename: string): void => {
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
};

export const generateShareUrl = (videoId: string): string => {
  const baseUrl = window.location.origin;
  return `${baseUrl}/v/${videoId}`;
};

export const checkBrowserSupport = (): { 
  supported: boolean; 
  message?: string; 
} => {
  if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
    return {
      supported: false,
      message: 'Screen recording is not supported in your browser. Please use Chrome, Firefox, or Safari.'
    };
  }

  if (!window.MediaRecorder) {
    return {
      supported: false,
      message: 'Media recording is not supported in your browser.'
    };
  }

  return { supported: true };
};

export const getDisplayMediaConstraints = (options: RecordingOptions): MediaStreamConstraints => {
  return {
    video: {
      // @ts-ignore - displaySurface is a valid property but not in TS types
      displaySurface: 'monitor',
      // @ts-ignore - cursor is a valid property but not in TS types
      cursor: options.showCursor ? 'always' : 'never',
    },
    audio: options.includeAudio,
  };
};

export const getCameraConstraints = (options: RecordingOptions): MediaStreamConstraints => {
  return {
    video: {
      width: { ideal: 1280 },
      height: { ideal: 720 },
      facingMode: 'user'
    },
    audio: options.includeAudio,
  };
};
