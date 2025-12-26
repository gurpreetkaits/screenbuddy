import { Video } from 'lucide-react';
import { Link } from 'wouter';
import { RecordingInterface } from '@/components/recording-interface';
import { VideoLibrary } from '@/components/video-library';

export default function Home() {
  return (
    <div className="min-h-screen">
      {/* Navigation */}
      <header className="navbar bg-base-100 border-b border-base-300">
        <div className="flex-1">
          <Link href="/" className="btn btn-ghost text-xl">
            <div className="w-8 h-8 rounded-lg bg-primary flex items-center justify-center mr-2">
              <Video className="w-5 h-5 text-primary-content" />
            </div>
            LoomClone
          </Link>
          
          <div className="tabs tabs-boxed ml-6">
            <Link href="/" className="tab tab-active">
              Record
            </Link>
            <Link href="/dashboard" className="tab">
              Dashboard
            </Link>
          </div>
        </div>
        
        <div className="flex-none flex items-center space-x-4">
          <Link href="/dashboard">
            <button className="btn btn-secondary" data-testid="button-view-dashboard">
              View All Recordings
            </button>
          </Link>
          <div className="avatar placeholder">
            <div 
              className="bg-neutral text-neutral-content rounded-full w-8"
              data-testid="avatar-user"
            >
              <span className="text-sm">U</span>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-8 max-w-4xl">
        <RecordingInterface />
        <VideoLibrary />
      </main>
    </div>
  );
}
