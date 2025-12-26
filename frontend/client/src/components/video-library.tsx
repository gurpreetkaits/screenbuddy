import { useState } from 'react';
import { Play, Share2, Download, Settings } from 'lucide-react';
import { RecordedVideo, downloadVideo } from '@/lib/recording-utils';
import { ShareModal } from './share-modal';

// Mock data for demonstration - in a real app, this would come from a backend
const mockRecordings: RecordedVideo[] = [];

export const VideoLibrary = () => {
  const [recordings] = useState<RecordedVideo[]>(mockRecordings);
  const [shareModalOpen, setShareModalOpen] = useState(false);
  const [selectedVideoId, setSelectedVideoId] = useState<string>('');

  const handleShare = (videoId: string) => {
    setSelectedVideoId(videoId);
    setShareModalOpen(true);
  };

  const handleDownload = (recording: RecordedVideo) => {
    const filename = `${recording.title.replace(/\s+/g, '-')}-${recording.id}.webm`;
    downloadVideo(recording.blob, filename);
  };

  if (recordings.length === 0) {
    return (
      <div className="mt-12">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-xl font-semibold">Recent Recordings</h2>
          <button className="btn btn-secondary" data-testid="button-view-all">
            <Settings className="w-4 h-4 mr-2" />
            View All
          </button>
        </div>

        <div className="card bg-base-100 shadow-xl">
          <div className="card-body">
            <div className="text-center py-12">
              <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-base-300 flex items-center justify-center">
                <Play className="w-8 h-8 text-base-content opacity-60" />
              </div>
              <h3 className="text-lg font-medium mb-2">No recordings yet</h3>
              <p className="text-base-content opacity-60">
                Start recording your screen to see your videos here.
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="mt-12">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-xl font-semibold">Recent Recordings</h2>
        <button className="btn btn-secondary" data-testid="button-view-all">
          <Settings className="w-4 h-4 mr-2" />
          View All
        </button>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {recordings.map((recording) => (
          <div
            key={recording.id}
            className="card bg-base-100 shadow-xl hover:shadow-2xl transition-shadow overflow-hidden"
            data-testid={`card-recording-${recording.id}`}
          >
            <div className="aspect-video bg-base-200 relative">
              <video
                src={recording.url}
                className="w-full h-full object-cover"
                muted
                data-testid={`video-thumbnail-${recording.id}`}
              />
              <div
                className="badge absolute bottom-2 right-2 bg-black/70 text-white border-none"
              >
                {recording.duration}
              </div>
              <button
                className="btn btn-sm btn-circle absolute top-2 left-2 bg-white/90 hover:bg-white text-gray-700 border-none"
                data-testid={`button-play-${recording.id}`}
              >
                <Play className="w-4 h-4" />
              </button>
            </div>
            
            <div className="card-body p-4">
              <h3 className="font-medium mb-1" data-testid={`text-title-${recording.id}`}>
                {recording.title}
              </h3>
              <p className="text-sm text-base-content opacity-60 mb-3" data-testid={`text-created-${recording.id}`}>
                Created {recording.createdAt.toLocaleDateString()}
              </p>
              <div className="flex items-center space-x-2">
                <button
                  className="btn btn-sm btn-secondary"
                  onClick={() => handleShare(recording.id)}
                  data-testid={`button-share-${recording.id}`}
                >
                  <Share2 className="w-3 h-3 mr-1" />
                  Share
                </button>
                <button
                  className="btn btn-sm btn-secondary"
                  onClick={() => handleDownload(recording)}
                  data-testid={`button-download-${recording.id}`}
                >
                  <Download className="w-3 h-3 mr-1" />
                  Download
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      <ShareModal
        isOpen={shareModalOpen}
        onClose={() => setShareModalOpen(false)}
        videoId={selectedVideoId}
      />
    </div>
  );
};
