# ScreenSense Browser Extension

A Loom-like screen recording extension for Chrome/Edge that lets you record your screen with camera and microphone.

## Features

- **Screen Recording**: Capture your entire screen, a window, or a tab
- **Camera Overlay**: Add a draggable camera bubble during recording (like Loom)
- **Microphone Audio**: Record audio from your microphone
- **Pause/Resume**: Control your recording with pause and resume
- **Easy Download**: Save recordings directly to your computer
- **Clean UI**: Simple, intuitive interface

## Installation

### Chrome/Edge (Developer Mode)

1. Open Chrome/Edge and navigate to `chrome://extensions/` (or `edge://extensions/`)

2. Enable **Developer mode** (toggle in the top-right corner)

3. Click **Load unpacked**

4. Select the `extension` folder from this project

5. The ScreenSense extension icon should appear in your browser toolbar

### Pin the Extension

- Click the puzzle icon in your browser toolbar
- Find "ScreenSense - Screen Recorder"
- Click the pin icon to keep it visible

## Usage

### Starting a Recording

1. Click the ScreenSense icon in your browser toolbar

2. Choose your recording options:
   - **Screen**: Record your screen (required)
   - **Camera**: Show your face in a draggable overlay
   - **Microphone**: Record audio

3. Click **Start Recording**

4. Select what you want to share:
   - Entire screen
   - Specific window
   - Browser tab

5. Click **Share** to begin recording

### During Recording

- The camera overlay (if enabled) appears in the bottom-right corner
- You can drag the camera overlay anywhere on the screen
- Click the extension icon to access controls:
  - **Pause**: Temporarily pause recording
  - **Resume**: Continue recording after pause
  - **Stop Recording**: End the recording

### After Recording

1. Click **Download Video** to save the recording to your computer

2. The video will be saved as a `.webm` file with a timestamp

3. Click **New Recording** to start over

## Permissions Explained

- **activeTab**: To capture the current tab
- **storage**: To save recording preferences
- **tabCapture**: To record browser tabs
- **desktopCapture**: To record your screen

## Keyboard Shortcuts (Coming Soon)

- `Ctrl+Shift+R` - Start/Stop recording
- `Ctrl+Shift+P` - Pause/Resume

## Troubleshooting

### Recording doesn't start

- Make sure you clicked "Share" in the screen sharing dialog
- Check that you've granted camera/microphone permissions
- Try refreshing the page and starting again

### No camera overlay appears

- Verify that "Camera" is checked in the recording options
- Make sure you've granted camera permissions to your browser
- Check browser console for errors (F12)

### Video won't download

- Check your browser's download settings
- Make sure pop-ups are allowed for the extension
- Try stopping and starting the recording again

## Browser Support

- Chrome 88+
- Edge 88+
- Opera 74+
- Brave (Chromium-based)

## Privacy

- All recordings are processed locally in your browser
- No data is sent to external servers
- Recordings are only saved to your computer when you click download

## Development

### Project Structure

```
extension/
├── manifest.json          # Extension configuration
├── popup/
│   ├── popup.html        # Extension popup UI
│   ├── popup.css         # Popup styles
│   └── popup.js          # Popup logic
├── scripts/
│   ├── background.js     # Background service worker
│   ├── content.js        # Content script for recording
│   └── content.css       # Camera overlay styles
└── icons/                # Extension icons
```

### Testing Changes

1. Make your changes to the code
2. Go to `chrome://extensions/`
3. Click the refresh icon on the ScreenSense extension
4. Test the changes

## Known Issues

- WebM format may not play in all video players (use VLC or Chrome)
- Very long recordings may use significant memory
- Camera overlay may appear under some full-screen elements

## Future Enhancements

- [ ] Upload recordings to cloud storage
- [ ] Trim and edit videos
- [ ] Add drawing/annotation tools
- [ ] Keyboard shortcuts
- [ ] Multiple video format options (MP4, etc.)
- [ ] Share links directly
- [ ] Recording countdown timer

## License

MIT License - Feel free to use and modify

## Support

For issues or feature requests, please open an issue on GitHub.
