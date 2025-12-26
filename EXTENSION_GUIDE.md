# ScreenSense Browser Extension - Quick Start

## What You Get

A Loom-like screen recording browser extension with:
- Screen + Camera + Microphone recording
- Draggable camera overlay (like Loom's bubble)
- Pause/Resume controls
- Download recordings directly
- Clean, modern UI

## Installation (5 minutes)

### Step 1: Load the Extension

1. Open **Chrome** or **Edge**
2. Go to `chrome://extensions/` (or `edge://extensions/`)
3. Turn on **Developer mode** (top-right toggle)
4. Click **Load unpacked**
5. Navigate to and select: `/Users/gurpreetkait/code/ScreenSense/extension`
6. The extension icon should appear in your toolbar

### Step 2: Pin the Extension (Optional)

- Click the puzzle piece icon in your toolbar
- Find "ScreenSense - Screen Recorder"
- Click the pin icon to keep it visible

## How to Use

### Start Recording

1. **Click the ScreenSense icon** in your toolbar
2. **Select options**:
   - ✅ Screen (captures your screen)
   - ☐ Camera (adds face bubble overlay)
   - ✅ Microphone (records audio)
3. **Click "Start Recording"**
4. **Choose what to share**:
   - Entire screen
   - A window
   - A Chrome tab
5. **Click "Share"**

### During Recording

- **Camera bubble**: Appears in bottom-right (if camera is enabled)
  - Drag it anywhere you want
  - Shows "REC" indicator with pulsing dot
- **Controls**: Click extension icon to:
  - Pause recording
  - Resume recording
  - Stop recording

### Save Your Video

1. Click **Stop Recording**
2. Click **Download Video**
3. Video saves as `.webm` file to your Downloads

## Features Comparison with Loom

| Feature | ScreenSense | Loom |
|---------|------------|------|
| Screen Recording | ✅ | ✅ |
| Camera Overlay | ✅ | ✅ |
| Microphone | ✅ | ✅ |
| Pause/Resume | ✅ | ✅ |
| Draggable Camera | ✅ | ✅ |
| Download Video | ✅ | ✅ |
| Cloud Storage | ❌ | ✅ |
| Link Sharing | ❌ | ✅ |
| Video Editing | ❌ | ✅ |

## Technical Details

- **Format**: WebM (VP9 codec)
- **Quality**: 2.5 Mbps video bitrate
- **Audio**: Included if microphone enabled
- **Storage**: Local only (no cloud uploads)
- **Privacy**: 100% local processing

## Troubleshooting

**Extension won't load?**
- Make sure you selected the `extension` folder, not a subfolder
- Check that all files are present (manifest.json, popup/, scripts/, icons/)

**Recording won't start?**
- Grant screen/camera/mic permissions when prompted
- Make sure you clicked "Share" in the browser dialog
- Try refreshing the page

**Camera overlay not showing?**
- Verify "Camera" is checked in options
- Grant camera permission in browser settings
- Check browser console (F12) for errors

**Can't play the video?**
- WebM format may need VLC player
- Can be converted to MP4 using online tools or FFmpeg

## File Structure

```
extension/
├── manifest.json          # Extension config
├── README.md             # Detailed docs
├── popup/
│   ├── popup.html        # UI for extension popup
│   ├── popup.css         # Styling
│   └── popup.js          # Popup logic & state
├── scripts/
│   ├── background.js     # Background service worker
│   ├── content.js        # Recording & camera overlay
│   └── content.css       # Overlay styles
└── icons/                # Extension icons
    ├── icon16.png
    ├── icon32.png
    ├── icon48.png
    └── icon128.png
```

## Development

**Make changes:**
1. Edit files in `/extension/` folder
2. Go to `chrome://extensions/`
3. Click refresh icon on ScreenSense
4. Test your changes

**Key files:**
- `popup/popup.js` - UI logic and controls
- `scripts/content.js` - Recording functionality
- `scripts/background.js` - Message handling
- `manifest.json` - Permissions and config

## Next Steps

See `extension/README.md` for:
- Detailed feature documentation
- Known issues
- Future enhancements
- Contributing guidelines

## Support

Having issues? Check:
1. Browser console (F12) for errors
2. Extension page for permission warnings
3. README.md for detailed troubleshooting

---

**Enjoy recording!** 🎥
