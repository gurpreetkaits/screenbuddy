# ScreenSense Extension Features

## Core Features

### 1. Screen Recording
- Capture entire screen, specific window, or browser tab
- High-quality video (2.5 Mbps bitrate)
- WebM format with VP9 codec

### 2. Camera Overlay (Loom-style)
- Floating camera bubble overlay
- Draggable to any position on screen
- Rounded corners with orange border
- "REC" indicator with pulsing dot
- 200x150px default size

### 3. Microphone Audio
- Record system audio + microphone
- Clear audio capture
- Synchronized with video

### 4. Recording Controls
- **Start**: Begin recording with one click
- **Pause**: Temporarily stop recording
- **Resume**: Continue after pause
- **Stop**: End recording

### 5. Easy Download
- One-click download
- Timestamped filenames
- WebM format (compatible with VLC, Chrome, etc.)

## User Interface

### Extension Popup
- Clean, modern design
- Orange accent color (#f97316)
- 360px width
- Three views:
  1. **Setup View**: Choose recording options
  2. **Recording View**: Controls during recording
  3. **Complete View**: Download finished recording

### Recording Options
- ☐ Screen (checkbox)
- ☐ Camera (checkbox)
- ☐ Microphone (checkbox)

### Visual Feedback
- Recording time display
- Pulsing red indicator
- Status messages
- Smooth animations

## Technical Capabilities

### Streams Management
- Combines screen + camera + audio streams
- Efficient MediaRecorder API usage
- Proper cleanup on stop

### State Persistence
- Remembers recording preferences
- Tracks recording state across popup opens/closes
- Handles tab closure gracefully

### Browser Permissions
- `activeTab`: Current tab access
- `storage`: Save preferences
- `tabCapture`: Record browser tabs
- `desktopCapture`: Record screen

## Workflow

```
1. Click Extension Icon
   ↓
2. Select Options (Screen/Camera/Mic)
   ↓
3. Click "Start Recording"
   ↓
4. Choose Screen/Window/Tab
   ↓
5. Click "Share"
   ↓
6. [Recording in Progress]
   - Camera overlay visible (if enabled)
   - Can pause/resume
   - Timer counting
   ↓
7. Click "Stop Recording"
   ↓
8. Click "Download Video"
   ↓
9. Video saved to Downloads folder
```

## Like Loom Features

✅ Screen recording
✅ Camera bubble overlay
✅ Draggable camera position
✅ Microphone audio
✅ Pause/Resume
✅ Clean UI
✅ One-click start
✅ Video download

## Differences from Loom

❌ No cloud storage (local only)
❌ No link sharing
❌ No video editing tools
❌ No team features
❌ No transcriptions
❌ No viewer analytics

## Privacy & Security

- **100% Local Processing**: All recording happens in your browser
- **No External Servers**: Nothing uploaded automatically
- **No Data Collection**: Extension doesn't track you
- **User-Controlled**: You decide when to record and where to save
- **Secure Permissions**: Only requests necessary browser permissions

## Performance

- **Memory Efficient**: Streams data in chunks
- **Low CPU Usage**: Native MediaRecorder API
- **No Lag**: Hardware-accelerated encoding
- **Instant Start**: No loading or buffering

## File Output

- **Format**: WebM (`.webm`)
- **Video Codec**: VP9
- **Audio**: Opus codec
- **Filename**: `screensense-recording-[timestamp].webm`
- **Example**: `screensense-recording-1701234567890.webm`

## Compatibility

### Browsers
- ✅ Chrome 88+
- ✅ Edge 88+
- ✅ Opera 74+
- ✅ Brave (Chromium-based)
- ❌ Firefox (Manifest V3 support limited)
- ❌ Safari (No extension support)

### Operating Systems
- ✅ Windows 10/11
- ✅ macOS 10.14+
- ✅ Linux (most distributions)
- ✅ Chrome OS

## Use Cases

- **Product Demos**: Show off your app
- **Bug Reports**: Record issues for developers
- **Tutorials**: Create how-to videos
- **Presentations**: Record your talks
- **Customer Support**: Visual explanations
- **Team Updates**: Async video messages
- **Code Reviews**: Walk through code changes
- **Design Feedback**: Show UI/UX suggestions

## Future Enhancements

### Planned
- [ ] Keyboard shortcuts (Ctrl+Shift+R)
- [ ] Countdown timer before recording
- [ ] Custom camera bubble size/shape
- [ ] Drawing tools during recording
- [ ] System audio capture
- [ ] Multiple export formats (MP4, GIF)

### Under Consideration
- [ ] Basic trimming
- [ ] Upload to cloud storage
- [ ] Share via link
- [ ] Webcam-only mode
- [ ] Picture-in-picture preview
- [ ] Custom watermarks
