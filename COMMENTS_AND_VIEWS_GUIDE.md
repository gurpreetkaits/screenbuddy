# Comments & Views Tracking - ScreenSense

## ✅ Implementation Complete

### **What Was Built:**

1. **Video Views Tracking System** - Track authenticated users AND anonymous viewers
2. **Comments System** - Save comments to database with author attribution
3. **View Statistics** - Detailed analytics per video
4. **Automatic View Tracking** - Records when users watch videos

---

## 📊 Database Schema

### **video_views Table**
Tracks every view with detailed information:

```sql
- id (primary key)
- video_id (foreign key to videos)
- user_id (nullable - null for anonymous users)
- ip_address (nullable - only stored for anonymous users)
- user_agent (browser info)
- watch_duration (seconds watched)
- completed (boolean - watched >90%?)
- viewed_at (timestamp)
- created_at, updated_at
```

**Smart Deduplication:**
- Prevents spam: Same user/IP can't create multiple views within 1 hour
- Updates existing view with longer watch duration
- Tracks completion status

### **comments Table** (Already Existed)
```sql
- id (primary key)
- video_id (foreign key)
- user_id (nullable - for authenticated users)
- author_name (nullable - for anonymous comments)
- content (the comment text)
- timestamp_seconds (optional - for timestamped comments)
- created_at, updated_at
```

---

## 🔧 Backend API

### **View Tracking Endpoints**

#### **Record a View**
```http
POST /api/videos/{id}/view
Content-Type: application/json

{
  "watch_duration": 120,  // seconds watched
  "completed": false      // watched to end?
}

Response:
{
  "message": "View recorded",
  "view": {
    "id": 1,
    "video_id": 12,
    "user_id": null,
    "ip_address": "127.0.0.1",
    "watch_duration": 120,
    "completed": false,
    "viewed_at": "2025-12-07T05:30:00Z"
  }
}
```

**How It Works:**
- ✅ Authenticated users: Uses `user_id`, no IP stored
- ✅ Anonymous users: Uses `ip_address` for tracking
- ✅ Anti-spam: Updates existing view if within 1 hour
- ✅ Completion tracking: Auto-detects if video watched >90%

#### **Get Video Statistics**
```http
GET /api/videos/{id}/stats

Response:
{
  "total_views": 150,
  "unique_viewers": 87,
  "authenticated_views": 45,
  "anonymous_views": 105,
  "average_watch_duration": 183.5,
  "completion_rate": 67.33,
  "recent_viewers": [
    {
      "user_name": "John Doe",
      "viewed_at": "2025-12-07T05:30:00Z",
      "watch_duration": 240,
      "completed": true
    }
  ]
}
```

### **Comment Endpoints**

#### **Get Comments**
```http
GET /api/videos/{id}/comments

Response:
{
  "comments": [
    {
      "id": 1,
      "content": "Great video!",
      "author_name": "John Doe",
      "timestamp_seconds": null,
      "created_at": "2025-12-07T05:30:00Z"
    }
  ]
}
```

#### **Add Comment**
```http
POST /api/videos/{id}/comments
Content-Type: application/json

{
  "content": "Great video!",
  "author_name": "Anonymous",  // Optional if authenticated
  "timestamp_seconds": 45      // Optional - for timestamped comments
}

Response:
{
  "message": "Comment added successfully",
  "comment": { ... }
}
```

#### **Delete Comment**
```http
DELETE /api/videos/{id}/comments/{commentId}
```

---

## 🎯 Frontend Integration

### **Automatic View Tracking**

Views are tracked automatically when a user opens a video:

```javascript
// VideoPlayerView.vue

onMounted(async () => {
  await fetchVideo()
  await loadComments()
  trackView() // ← Initial view tracked here
})

// Updates view progress every 30 seconds
setInterval(updateViewProgress, 30000)

// Final update on page unload
onUnmounted(() => {
  updateViewProgress()
})
```

**What Gets Tracked:**
- ✅ Initial view when video loads
- ✅ Watch duration (updated every 30 seconds)
- ✅ Completion status (auto-detected at 90%)
- ✅ Authenticated user OR anonymous IP

### **Comment System**

Comments are loaded and saved automatically:

```javascript
// Load comments when video loads
await loadComments()

// Save comment to database
await addComment() // Calls API and updates local state
```

**Features:**
- ✅ Real-time saving to database
- ✅ Loading states ("Saving...")
- ✅ Error handling with user feedback
- ✅ Optimistic UI updates
- ✅ Time-ago formatting ("2h ago")

---

## 📈 View Count Display

### **Video List (VideosView.vue)**
```vue
<div class="flex items-center gap-1.5">
  <svg><!-- Eye icon --></svg>
  <span>{{ video.views_count || 0 }}</span>
</div>
```

Shows actual view count from database.

### **Video Player**
View counts are available via `video.views_count` property.

---

## 🔍 How Authentication Works

### **Authenticated Users**
When a user is logged in:
```javascript
// Backend automatically detects
$userId = Auth::check() ? Auth::id() : null;

// Saved in database
user_id: 123
ip_address: null  // Not stored for privacy
```

**Benefits:**
- Track which users watched which videos
- Show personalized analytics
- Respect user privacy (no IP storage)

### **Anonymous Users**
When no user is logged in:
```javascript
// Backend uses IP address
$ipAddress = $request->ip();

// Saved in database
user_id: null
ip_address: "192.168.1.1"
```

**Benefits:**
- Still track total views
- Prevent anonymous spam
- Count unique anonymous viewers

---

## 🎨 UI/UX Features

### **Comments Section**
- ✅ Auto-load comments from database
- ✅ Save button shows "Saving..." state
- ✅ Comments persist across page reloads
- ✅ Time-ago display ("2h ago", "Just now")
- ✅ Error handling with toast notifications

### **View Tracking**
- ✅ Silent background tracking
- ✅ Updates every 30 seconds
- ✅ No UI impact
- ✅ Final update on page close

---

## 📊 Analytics Capabilities

You can now answer:

1. **How many people watched this video?**
   - `total_views` - All views
   - `unique_viewers` - Distinct users/IPs

2. **Who watched it?**
   - `recent_viewers` - List of authenticated users
   - `authenticated_views` vs `anonymous_views`

3. **How engaged are viewers?**
   - `average_watch_duration` - Average seconds watched
   - `completion_rate` - % who watched >90%

4. **When did they watch?**
   - `viewed_at` timestamp for each view

---

## 🚀 Usage Examples

### **Check Video Stats**
```bash
curl http://localhost:8000/api/videos/12/stats
```

### **Record a View (Manual)**
```bash
curl -X POST http://localhost:8000/api/videos/12/view \
  -H "Content-Type: application/json" \
  -d '{"watch_duration": 60, "completed": false}'
```

### **Add a Comment**
```bash
curl -X POST http://localhost:8000/api/videos/12/comments \
  -H "Content-Type: application/json" \
  -d '{"content": "Great video!", "author_name": "John"}'
```

---

## 🔒 Privacy & Security

### **View Tracking**
- ✅ IP addresses only stored for anonymous users
- ✅ Authenticated users: No IP stored (privacy)
- ✅ 1-hour deduplication prevents spam
- ✅ No personally identifiable browser fingerprinting

### **Comments**
- ✅ Authenticated users: Linked to user account
- ✅ Anonymous users: Can set display name
- ✅ Content validation (max 2000 chars)
- ✅ XSS protection via backend validation

---

## 📁 Files Modified

### **Backend**
- `database/migrations/*_create_video_views_table.php` - Views schema
- `database/migrations/*_create_comments_table.php` - Comments schema (existed)
- `app/Models/VideoView.php` - View tracking model
- `app/Models/Video.php` - Added views relationship
- `app/Http/Controllers/VideoViewController.php` - View tracking API
- `app/Http/Controllers/CommentController.php` - Comments API (existed)
- `routes/api.php` - Added view/comment routes

### **Frontend**
- `frontend/src/services/videoService.js` - Added comment/view methods
- `frontend/src/views/VideoPlayerView.vue` - Auto-tracking + comments
- `frontend/src/views/VideosView.vue` - Display view counts

---

## 🎯 Future Enhancements

### **Immediate (Easy)**
- [ ] Show view count in video player UI
- [ ] Add "X people are watching" live indicator
- [ ] Comment reactions (like/dislike)
- [ ] Reply to comments (threaded)

### **Medium**
- [ ] Viewer analytics dashboard
- [ ] Export view data to CSV
- [ ] Email notifications for new comments
- [ ] Comment moderation (report/flag)

### **Advanced**
- [ ] Heatmap of video engagement (which parts watched)
- [ ] Viewer retention graph
- [ ] A/B testing different titles/thumbnails
- [ ] Predictive analytics (will they finish?)

---

## ✅ Testing

### **1. Test View Tracking**
1. Open a video
2. Wait 30 seconds
3. Check database: `SELECT * FROM video_views ORDER BY id DESC LIMIT 1;`
4. Should see your view with `watch_duration >= 30`

### **2. Test Comments**
1. Add a comment in the UI
2. Refresh page
3. Comment should still be there (from database)
4. Check database: `SELECT * FROM comments ORDER BY id DESC LIMIT 1;`

### **3. Test View Stats**
```bash
curl http://localhost:8000/api/videos/12/stats | jq
```

### **4. Test Anonymous vs Authenticated**
- Open video in incognito → Should track with IP
- Log in and open video → Should track with user_id

---

**Last Updated:** 2025-12-07
**Status:** ✅ Fully Implemented and Tested
**Authentication:** Works with and without login (MVP ready)
