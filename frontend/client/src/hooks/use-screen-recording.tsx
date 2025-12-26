import { useState, useCallback, useRef } from 'react';
import { useToast } from '@/hooks/use-toast';
import { 
  RecordingOptions, 
  RecordedVideo, 
  formatTime, 
  generateVideoId,
  checkBrowserSupport,
  getDisplayMediaConstraints,
  getCameraConstraints
} from '@/lib/recording-utils';

export type RecordingState = 'idle' | 'recording' | 'paused' | 'completed';

export interface UseScreenRecordingReturn {
  state: RecordingState;
  recordingTime: number;
  formattedTime: string;
  currentVideo: RecordedVideo | null;
  stream: MediaStream | null;
  startRecording: (options: RecordingOptions) => Promise<void>;
  stopRecording: () => Promise<void>;
  pauseRecording: () => void;
  resumeRecording: () => void;
  clearRecording: () => void;
  isSupported: boolean;
}

export const useScreenRecording = (): UseScreenRecordingReturn => {
  const [state, setState] = useState<RecordingState>('idle');
  const [recordingTime, setRecordingTime] = useState(0);
  const [currentVideo, setCurrentVideo] = useState<RecordedVideo | null>(null);
  const [stream, setStream] = useState<MediaStream | null>(null);
  
  const mediaRecorderRef = useRef<MediaRecorder | null>(null);
  const chunksRef = useRef<Blob[]>([]);
  const timerRef = useRef<NodeJS.Timeout | null>(null);
  const { toast } = useToast();

  const { supported: isSupported, message: supportMessage } = checkBrowserSupport();

  const startTimer = useCallback(() => {
    timerRef.current = setInterval(() => {
      setRecordingTime(prev => prev + 1);
    }, 1000);
  }, []);

  const stopTimer = useCallback(() => {
    if (timerRef.current) {
      clearInterval(timerRef.current);
      timerRef.current = null;
    }
  }, []);

  const startRecording = useCallback(async (options: RecordingOptions) => {
    if (!isSupported) {
      toast({
        title: 'Recording Not Supported',
        description: supportMessage,
        variant: 'destructive',
      });
      return;
    }

    try {
      let combinedStream: MediaStream;
      
      if (options.recordingType === 'screen') {
        // Screen recording only
        const constraints = getDisplayMediaConstraints(options);
        combinedStream = await navigator.mediaDevices.getDisplayMedia(constraints);
      } else if (options.recordingType === 'camera') {
        // Camera recording only
        const constraints = getCameraConstraints(options);
        combinedStream = await navigator.mediaDevices.getUserMedia(constraints);
      } else {
        // Combined screen + camera recording
        const screenConstraints = getDisplayMediaConstraints(options);
        const cameraConstraints = getCameraConstraints(options);
        
        const [screenStream, cameraStream] = await Promise.all([
          navigator.mediaDevices.getDisplayMedia(screenConstraints),
          navigator.mediaDevices.getUserMedia(cameraConstraints)
        ]);
        
        // Combine both streams
        combinedStream = new MediaStream([
          ...screenStream.getVideoTracks(),
          ...cameraStream.getVideoTracks(),
          ...(options.includeAudio ? screenStream.getAudioTracks() : [])
        ]);
      }
      
      setStream(combinedStream);
      chunksRef.current = [];
      setRecordingTime(0);

      // Create MediaRecorder
      const mediaRecorder = new MediaRecorder(combinedStream, {
        mimeType: MediaRecorder.isTypeSupported('video/webm; codecs=vp9') 
          ? 'video/webm; codecs=vp9'
          : 'video/webm'
      });

      mediaRecorderRef.current = mediaRecorder;

      // Handle data chunks
      mediaRecorder.ondataavailable = (event) => {
        if (event.data.size > 0) {
          chunksRef.current.push(event.data);
        }
      };

      // Handle stop event
      mediaRecorder.onstop = () => {
        const blob = new Blob(chunksRef.current, { 
          type: mediaRecorder.mimeType || 'video/webm' 
        });
        
        const videoId = generateVideoId();
        const url = URL.createObjectURL(blob);
        
        const video: RecordedVideo = {
          id: videoId,
          title: `Recording ${new Date().toLocaleString()}`,
          duration: formatTime(recordingTime),
          createdAt: new Date(),
          blob,
          url,
        };

        setCurrentVideo(video);
        setState('completed');
        stopTimer();

        toast({
          title: 'Recording Complete',
          description: 'Your screen recording is ready for download.',
        });
      };

      // Handle stream ending
      combinedStream.getVideoTracks()[0].onended = () => {
        stopRecording();
      };

      // Start recording
      mediaRecorder.start(1000); // Collect data every second
      setState('recording');
      startTimer();

      toast({
        title: 'Recording Started',
        description: 'Your screen is now being recorded.',
      });

    } catch (error: any) {
      console.error('Error starting recording:', error);
      
      let errorMessage = 'Failed to start recording.';
      if (error.name === 'NotAllowedError') {
        errorMessage = 'Permission denied. Please allow screen sharing.';
      } else if (error.name === 'NotSupportedError') {
        errorMessage = 'Screen recording is not supported in your browser.';
      }

      toast({
        title: 'Recording Error',
        description: errorMessage,
        variant: 'destructive',
      });

      setState('idle');
    }
  }, [isSupported, supportMessage, toast, startTimer, stopTimer, recordingTime]);

  const stopRecording = useCallback(async () => {
    if (mediaRecorderRef.current && state === 'recording') {
      mediaRecorderRef.current.stop();
    }

    if (stream) {
      stream.getTracks().forEach(track => track.stop());
      setStream(null);
    }

    stopTimer();
  }, [state, stream, stopTimer]);

  const pauseRecording = useCallback(() => {
    if (mediaRecorderRef.current && state === 'recording') {
      mediaRecorderRef.current.pause();
      setState('paused');
      stopTimer();
      
      toast({
        title: 'Recording Paused',
        description: 'Click resume to continue recording.',
      });
    }
  }, [state, stopTimer, toast]);

  const resumeRecording = useCallback(() => {
    if (mediaRecorderRef.current && state === 'paused') {
      mediaRecorderRef.current.resume();
      setState('recording');
      startTimer();
      
      toast({
        title: 'Recording Resumed',
        description: 'Recording is now continuing.',
      });
    }
  }, [state, startTimer, toast]);

  const clearRecording = useCallback(() => {
    if (currentVideo?.url) {
      URL.revokeObjectURL(currentVideo.url);
    }
    
    setCurrentVideo(null);
    setState('idle');
    setRecordingTime(0);
    stopTimer();
  }, [currentVideo, stopTimer]);

  return {
    state,
    recordingTime,
    formattedTime: formatTime(recordingTime),
    currentVideo,
    stream,
    startRecording,
    stopRecording,
    pauseRecording,
    resumeRecording,
    clearRecording,
    isSupported,
  };
};
