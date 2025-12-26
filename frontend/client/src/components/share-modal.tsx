import { useState } from 'react';
import { Copy, Check } from 'lucide-react';
import { generateShareUrl } from '@/lib/recording-utils';

interface ShareModalProps {
  isOpen: boolean;
  onClose: () => void;
  videoId: string;
}

export const ShareModal = ({ isOpen, onClose, videoId }: ShareModalProps) => {
  const [copied, setCopied] = useState(false);
  const [allowDownloads, setAllowDownloads] = useState(false);
  const [publicAccess, setPublicAccess] = useState(true);
  // Basic toast notification
  const showToast = (title: string, description: string) => {
    console.log(`${title}: ${description}`);
  };

  const shareUrl = generateShareUrl(videoId);

  const handleCopyLink = async () => {
    try {
      await navigator.clipboard.writeText(shareUrl);
      setCopied(true);
      showToast('Link Copied', 'Share link has been copied to your clipboard.');
      setTimeout(() => setCopied(false), 2000);
    } catch (error) {
      showToast('Copy Failed', 'Failed to copy link to clipboard.');
    }
  };

  const handleShare = () => {
    showToast('Settings Saved', 'Your sharing preferences have been updated.');
    onClose();
  };

  return (
    <dialog id="share_modal" className={`modal ${isOpen ? 'modal-open' : ''}`}>
      <div className="modal-box max-w-md" data-testid="modal-share">
        <h3 className="font-bold text-lg mb-4">Share Recording</h3>
        
        <div className="space-y-4">
          <div className="form-control">
            <label htmlFor="share-link" className="label">
              <span className="label-text">Share Link</span>
            </label>
            <div className="input-group">
              <input
                id="share-link"
                type="text"
                value={shareUrl}
                readOnly
                className="input input-bordered flex-1"
                data-testid="input-share-url"
              />
              <button 
                onClick={handleCopyLink}
                className="btn btn-square"
                data-testid="button-copy-link"
              >
                {copied ? (
                  <Check className="w-4 h-4" />
                ) : (
                  <Copy className="w-4 h-4" />
                )}
              </button>
            </div>
          </div>
          
          <div className="flex items-center space-x-4">
            <div className="form-control">
              <label className="label cursor-pointer">
                <input 
                  type="checkbox" 
                  className="checkbox checkbox-sm mr-2" 
                  checked={allowDownloads}
                  onChange={(e) => setAllowDownloads(e.target.checked)}
                  data-testid="checkbox-allow-downloads"
                />
                <span className="label-text text-sm">Allow downloads</span>
              </label>
            </div>
            <div className="form-control">
              <label className="label cursor-pointer">
                <input 
                  type="checkbox" 
                  className="checkbox checkbox-sm mr-2" 
                  checked={publicAccess}
                  onChange={(e) => setPublicAccess(e.target.checked)}
                  data-testid="checkbox-public-access"
                />
                <span className="label-text text-sm">Public access</span>
              </label>
            </div>
          </div>

          <div className="pt-4 flex space-x-3">
            <button 
              className="btn btn-secondary flex-1" 
              onClick={onClose}
              data-testid="button-cancel-share"
            >
              Cancel
            </button>
            <button 
              className="btn btn-primary flex-1" 
              onClick={handleShare}
              data-testid="button-confirm-share"
            >
              Share
            </button>
          </div>
        </div>
      </div>
      <form method="dialog" className="modal-backdrop">
        <button onClick={onClose}>close</button>
      </form>
    </dialog>
  );
};
