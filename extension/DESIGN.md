# ScreenSense Extension - Minimal Design

## Design Inspiration
Inspired by Percam's clean, minimal interface with focus on simplicity and ease of use.

## Visual Design

### Color Palette
- **Primary Blue**: `#5b7aff` - Main action button, toggles
- **Success Green**: `#52c41a` - Completion state
- **Danger Red**: `#ff4d4f` - Recording indicator, stop button
- **Background**: `#f7f7f7` - Section rows
- **Border**: `#e8e8e8` - Subtle borders
- **Text**: `#1a1a1a` - Primary text
- **Secondary Text**: `#666` - Icons and labels

### Typography
- **Font**: System font stack (SF Pro on Mac, Segoe UI on Windows)
- **Sizes**:
  - Section labels: 14px (500 weight)
  - Buttons: 15px (600 weight)
  - Timer: 32px (700 weight)

## Layout Structure

### Setup View (320px wide)
```
┌─────────────────────────────────┐
│  [Icon] Screen ▼                │ ← Source select
├─────────────────────────────────┤
│  [Icon] Camera        [Toggle]  │ ← Camera toggle
├─────────────────────────────────┤
│  [Icon] Default Microphone ▼    │ ← Mic select
├─────────────────────────────────┤
│  [ ○  00:00 ]                   │ ← Record button
└─────────────────────────────────┘
```

### Recording View
```
┌─────────────────────────────────┐
│         [● Recording]            │ ← Red indicator
│           00:45                  │ ← Large timer
│                                  │
│     [⏸] [■]                     │ ← Pause & Stop
└─────────────────────────────────┘
```

### Complete View
```
┌─────────────────────────────────┐
│           [✓]                    │ ← Green checkmark
│      Recording saved             │
│                                  │
│      [Download]                  │ ← Primary button
│      [New Recording]             │ ← Secondary button
└─────────────────────────────────┘
```

## Component Styles

### Section Rows
- Background: `#f7f7f7`
- Border: `1px solid #e8e8e8`
- Padding: `10px 12px`
- Border radius: `8px`
- Gap between icon and content: `12px`

### Toggle Switch
- Width: `40px`
- Height: `22px`
- Knob: `18px` circle
- Off state: `#e0e0e0`
- On state: `#5b7aff`
- Smooth transition: `0.2s`

### Record Button
- Full width
- Background: `#5b7aff`
- Padding: `14px`
- Border radius: `8px`
- Contains: White circle (16px) + "00:00" timer
- Hover: Lifts up with shadow

### Control Buttons (During Recording)
- Circular: `44px` diameter
- Pause button: Gray background
- Stop button: Red (`#ff4d4f`)
- Icons: `16px`

## Interactions

### Hover States
- **Record Button**:
  - Transform up 1px
  - Add blue glow shadow
  - Darken background

- **Control Buttons**:
  - Stop button scales to 1.05
  - Pause button darkens background

### Active States
- All buttons scale to 0.95-0.98 on click
- Smooth transitions (0.2s)

### Toggle Animation
- Knob slides smoothly across track
- Background color fades from gray to blue
- Duration: 0.2s

## Accessibility

- High contrast ratios for all text
- Clear focus states
- Keyboard navigable
- Semantic HTML
- ARIA labels where needed

## Responsive Behavior

Fixed width: 320px (standard extension popup size)
All elements scale proportionally
Touch-friendly button sizes (min 44px)

## Icons

- Screen: Monitor/display icon
- Camera: Video camera icon
- Microphone: Mic icon
- All icons: 16px, stroke weight 2
- Neutral gray color (#666)

## Animations

### Fade In
- Duration: 150ms
- Easing: ease-out
- Vertical slide: 4px

### Pulse (Recording Dot)
- Duration: 1.5s
- Easing: ease-in-out
- Infinite loop
- Opacity: 1 → 0.6 → 1
- Scale: 1 → 0.95 → 1

## Design Principles

1. **Minimal**: Only essential controls visible
2. **Clear**: Large timer, obvious recording state
3. **Fast**: Quick access to all features
4. **Clean**: Lots of white space, simple shapes
5. **Modern**: Rounded corners, smooth transitions
6. **Focused**: One primary action per screen

## Comparison to Percam

### Similar
- Minimal interface
- Toggle switches for settings
- Dropdown selects
- Clean section rows
- Blue primary color
- Circular record button

### Differences
- Simpler (fewer options on main screen)
- Larger touch targets
- Bolder colors
- More pronounced hover states
