import { useState, useEffect } from 'react';
import { Link, useLocation } from 'wouter';
import { 
  Video, 
  Plus, 
  Search, 
  Filter, 
  Grid3x3, 
  List, 
  MoreVertical, 
  Edit3, 
  Share2, 
  Download, 
  Trash2,
  Play,
  Monitor,
  Camera,
  Users
} from 'lucide-react';
import { RecordedVideo, downloadVideo } from '@/lib/recording-utils';
import { ShareModal } from '@/components/share-modal';

// Mock data for demonstration - in a real app, this would come from a backend
const mockRecordings: RecordedVideo[] = [
  {
    id: '1',
    title: 'Project Demo Recording',
    duration: '5:23',
    createdAt: new Date('2024-01-15'),
    blob: new Blob(),
    url: '#',
    thumbnail: '/api/placeholder/320/180',
  },
  {
    id: '2', 
    title: 'Team Meeting - Q4 Planning',
    duration: '12:45',
    createdAt: new Date('2024-01-14'),
    blob: new Blob(),
    url: '#',
    thumbnail: '/api/placeholder/320/180',
  },
];

type ViewMode = 'grid' | 'list';

export default function Dashboard() {
  const [location] = useLocation();
  const [recordings, setRecordings] = useState<RecordedVideo[]>(mockRecordings);
  const [viewMode, setViewMode] = useState<ViewMode>('grid');
  const [searchQuery, setSearchQuery] = useState('');
  const [shareModalOpen, setShareModalOpen] = useState(false);
  const [selectedVideoId, setSelectedVideoId] = useState('');
  const [editModalOpen, setEditModalOpen] = useState(false);
  const [editingVideo, setEditingVideo] = useState<RecordedVideo | null>(null);
  const [editTitle, setEditTitle] = useState('');
  const [editDescription, setEditDescription] = useState('');

  const filteredRecordings = recordings.filter(recording =>
    recording.title.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const handleShare = (videoId: string) => {
    setSelectedVideoId(videoId);
    setShareModalOpen(true);
  };

  const handleEdit = (recording: RecordedVideo) => {
    setEditingVideo(recording);
    setEditTitle(recording.title);
    setEditDescription(''); // Would come from backend
    setEditModalOpen(true);
  };

  const handleSaveEdit = () => {
    if (editingVideo) {
      setRecordings(prev => prev.map(recording => 
        recording.id === editingVideo.id 
          ? { ...recording, title: editTitle }
          : recording
      ));
      setEditModalOpen(false);
      setEditingVideo(null);
    }
  };

  const handleDelete = (videoId: string) => {
    setRecordings(prev => prev.filter(recording => recording.id !== videoId));
  };

  const handleDownload = (recording: RecordedVideo) => {
    const filename = `${recording.title.replace(/\s+/g, '-')}-${recording.id}.webm`;
    downloadVideo(recording.blob, filename);
  };

  const getRecordingTypeIcon = (type?: string) => {
    switch (type) {
      case 'camera':
        return <Camera className="w-4 h-4" />;
      case 'screen+camera':
        return <Users className="w-4 h-4" />;
      default:
        return <Monitor className="w-4 h-4" />;
    }
  };

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
            <Link href="/" className="tab">
              Record
            </Link>
            <Link href="/dashboard" className="tab tab-active">
              Dashboard
            </Link>
          </div>
        </div>
        
        <div className="flex-none flex items-center space-x-4">
          <Link href="/">
            <button className="btn btn-primary" data-testid="button-new-recording">
              <Plus className="w-4 h-4 mr-2" />
              New Recording
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
      <main className="container mx-auto px-4 py-8">
        {/* Page Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold">My Recordings</h1>
            <p className="text-base-content opacity-60 mt-2">
              Manage and share your screen recordings
            </p>
          </div>
          
          <div className="flex items-center space-x-4">
            {/* Search */}
            <div className="form-control">
              <div className="input-group">
                <input
                  type="text"
                  placeholder="Search recordings..."
                  className="input input-bordered w-64"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  data-testid="input-search"
                />
                <div className="btn btn-square btn-ghost">
                  <Search className="w-4 h-4" />
                </div>
              </div>
            </div>

            {/* View Mode Toggle */}
            <div className="btn-group">
              <button
                className={`btn ${viewMode === 'grid' ? 'btn-active' : 'btn-outline'}`}
                onClick={() => setViewMode('grid')}
                data-testid="button-grid-view"
              >
                <Grid3x3 className="w-4 h-4" />
              </button>
              <button
                className={`btn ${viewMode === 'list' ? 'btn-active' : 'btn-outline'}`}
                onClick={() => setViewMode('list')}
                data-testid="button-list-view"
              >
                <List className="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        {/* Recordings */}
        {filteredRecordings.length === 0 ? (
          <div className="card bg-base-100 shadow-xl">
            <div className="card-body">
              <div className="text-center py-12">
                <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-base-300 flex items-center justify-center">
                  <Video className="w-8 h-8 text-base-content opacity-60" />
                </div>
                <h3 className="text-lg font-medium mb-2">
                  {searchQuery ? 'No recordings found' : 'No recordings yet'}
                </h3>
                <p className="text-base-content opacity-60 mb-4">
                  {searchQuery 
                    ? 'Try adjusting your search terms'
                    : 'Start recording your screen to see your videos here.'
                  }
                </p>
                <Link href="/">
                  <button className="btn btn-primary">
                    <Plus className="w-4 h-4 mr-2" />
                    Create First Recording
                  </button>
                </Link>
              </div>
            </div>
          </div>
        ) : (
          <div className={
            viewMode === 'grid'
              ? 'grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'
              : 'space-y-4'
          }>
            {filteredRecordings.map((recording) => (
              <div
                key={recording.id}
                className={`card bg-base-100 shadow-xl hover:shadow-2xl transition-shadow ${
                  viewMode === 'list' ? 'card-side' : ''
                }`}
                data-testid={`card-recording-${recording.id}`}
              >
                {viewMode === 'grid' ? (
                  <>
                    <div className="aspect-video bg-base-200 relative">
                      {recording.thumbnail ? (
                        <img
                          src={recording.thumbnail}
                          alt={recording.title}
                          className="w-full h-full object-cover"
                        />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center">
                          {getRecordingTypeIcon()}
                        </div>
                      )}
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
                      <div className="flex items-start justify-between mb-2">
                        <h3 className="font-medium text-sm leading-tight" data-testid={`text-title-${recording.id}`}>
                          {recording.title}
                        </h3>
                        <div className="dropdown dropdown-end">
                          <div tabIndex={0} role="button" className="btn btn-ghost btn-sm btn-square">
                            <MoreVertical className="w-4 h-4" />
                          </div>
                          <ul tabIndex={0} className="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow">
                            <li><button onClick={() => handleEdit(recording)} className="flex items-center">
                              <Edit3 className="w-4 h-4 mr-2" />
                              Edit
                            </button></li>
                            <li><button onClick={() => handleShare(recording.id)} className="flex items-center">
                              <Share2 className="w-4 h-4 mr-2" />
                              Share
                            </button></li>
                            <li><button onClick={() => handleDownload(recording)} className="flex items-center">
                              <Download className="w-4 h-4 mr-2" />
                              Download
                            </button></li>
                            <li><button onClick={() => handleDelete(recording.id)} className="flex items-center text-error">
                              <Trash2 className="w-4 h-4 mr-2" />
                              Delete
                            </button></li>
                          </ul>
                        </div>
                      </div>
                      <p className="text-xs text-base-content opacity-60" data-testid={`text-created-${recording.id}`}>
                        {recording.createdAt.toLocaleDateString()}
                      </p>
                    </div>
                  </>
                ) : (
                  <>
                    <figure className="w-32 h-20 bg-base-200 relative flex-shrink-0">
                      {recording.thumbnail ? (
                        <img
                          src={recording.thumbnail}
                          alt={recording.title}
                          className="w-full h-full object-cover"
                        />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center">
                          {getRecordingTypeIcon()}
                        </div>
                      )}
                      <div
                        className="badge absolute bottom-1 right-1 text-xs bg-black/70 text-white border-none"
                      >
                        {recording.duration}
                      </div>
                    </figure>
                    
                    <div className="card-body p-4 flex-1 flex items-center justify-between">
                      <div>
                        <h3 className="font-medium mb-1" data-testid={`text-title-${recording.id}`}>
                          {recording.title}
                        </h3>
                        <p className="text-sm text-base-content opacity-60" data-testid={`text-created-${recording.id}`}>
                          Created {recording.createdAt.toLocaleDateString()}
                        </p>
                      </div>
                      
                      <div className="flex items-center space-x-2">
                        <button
                          className="btn btn-sm btn-ghost btn-square"
                          onClick={() => handleEdit(recording)}
                        >
                          <Edit3 className="w-4 h-4" />
                        </button>
                        <button
                          className="btn btn-sm btn-ghost btn-square"
                          onClick={() => handleShare(recording.id)}
                        >
                          <Share2 className="w-4 h-4" />
                        </button>
                        <button
                          className="btn btn-sm btn-ghost btn-square"
                          onClick={() => handleDownload(recording)}
                        >
                          <Download className="w-4 h-4" />
                        </button>
                        <button
                          className="btn btn-sm btn-ghost btn-square text-error"
                          onClick={() => handleDelete(recording.id)}
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  </>
                )}
              </div>
            ))}
          </div>
        )}
      </main>

      {/* Edit Video Modal */}
      <dialog id="edit_modal" className={`modal ${editModalOpen ? 'modal-open' : ''}`}>
        <div className="modal-box max-w-md" data-testid="modal-edit-video">
          <h3 className="font-bold text-lg mb-4">Edit Recording</h3>
          
          <div className="space-y-4">
            <div className="form-control">
              <label htmlFor="edit-title" className="label">
                <span className="label-text">Title</span>
              </label>
              <input
                id="edit-title"
                type="text"
                value={editTitle}
                onChange={(e) => setEditTitle(e.target.value)}
                placeholder="Enter video title..."
                className="input input-bordered"
                data-testid="input-edit-title"
              />
            </div>
            
            <div className="form-control">
              <label htmlFor="edit-description" className="label">
                <span className="label-text">Description</span>
              </label>
              <textarea
                id="edit-description"
                value={editDescription}
                onChange={(e) => setEditDescription(e.target.value)}
                placeholder="Enter video description..."
                rows={3}
                className="textarea textarea-bordered"
                data-testid="textarea-edit-description"
              />
            </div>

            <div className="pt-4 flex space-x-3">
              <button 
                className="btn btn-secondary flex-1" 
                onClick={() => setEditModalOpen(false)}
                data-testid="button-cancel-edit"
              >
                Cancel
              </button>
              <button 
                className="btn btn-primary flex-1" 
                onClick={handleSaveEdit}
                data-testid="button-save-edit"
              >
                Save Changes
              </button>
            </div>
          </div>
        </div>
        <form method="dialog" className="modal-backdrop">
          <button onClick={() => setEditModalOpen(false)}>close</button>
        </form>
      </dialog>

      {/* Share Modal */}
      <ShareModal
        isOpen={shareModalOpen}
        onClose={() => setShareModalOpen(false)}
        videoId={selectedVideoId}
      />
    </div>
  );
}