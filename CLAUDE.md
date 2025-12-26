# ScreenSense - Development Documentation

## Quick Start

### Running the Application

You need **THREE terminals** running simultaneously:

**Terminal 1: Backend (Laravel)**
```bash
cd /Users/gurpreetkait/code/ScreenSense
php artisan serve
# Runs on http://localhost:8000
```

**Terminal 2: Frontend (Vue.js)**
```bash
cd /Users/gurpreetkait/code/ScreenSense/frontend
npm run dev
# Runs on http://localhost:5173
```

**Terminal 3: Queue Worker (For video conversion)**
```bash
cd /Users/gurpreetkait/code/ScreenSense
php artisan queue:work --queue=default --tries=3 --timeout=1800
# Processes background jobs (video conversion to MP4)
```

### Initial Setup (One-Time)

```bash
# Backend
composer install
php artisan migrate
php artisan storage:link
chmod -R 775 storage/ bootstrap/cache/

# Frontend
cd frontend
npm install
```

---

## Video Recording & Sharing Implementation

### How It Works

1. **Recording Stops** → Video blob created in browser
2. **Auto-Save Triggered** → Uploads to `/api/videos`
3. **Backend Processing**:
   - Validates file (webm/mp4/mov, max 500MB)
   - Creates Video record in database
   - Generates unique 64-char share token
   - Stores file via Spatie Media Library
   - Sets `is_public = true` by default
4. **Frontend Receives**: Video ID + Share URL
5. **User Can**: Copy link, edit title, share immediately

### API Endpoints

**Public:**
- `GET /api/share/video/{token}` - View shared video

**Profile Management (No auth in MVP):**
- `GET /api/profile` - Get current user profile
- `POST /api/profile` - Update profile (name, username, bio, avatar, website, location)
- `DELETE /api/profile/avatar` - Delete profile avatar

**Video Management (No auth in MVP):**
- `GET /api/videos` - List videos
- `POST /api/videos` - Upload video
- `GET /api/videos/{id}` - Get single video
- `GET /api/videos/{id}/stream` - **Stream video with Range request support (enables instant seeking)**
- `PUT /api/videos/{id}` - Update video
- `DELETE /api/videos/{id}` - Delete video
- `POST /api/videos/{id}/toggle-sharing` - Make public/private
- `POST /api/videos/{id}/regenerate-token` - New share link

### File Locations

- **Videos**: `storage/app/public/[media-id]/`
- **Database**: `videos` table (metadata), `media` table (files)
- **Logs**: `storage/logs/laravel.log`

### Database Schema

**Users Table:**
```sql
- id (primary key)
- name (string)
- email (string, unique)
- password (hashed string)
- username (string, unique, nullable)
- bio (text, nullable, max 500 chars)
- avatar (string, nullable - path to uploaded image)
- website (string, nullable - URL)
- location (string, nullable)
- email_verified_at (timestamp, nullable)
- timestamps
```

**Videos Table:**
```sql
- id (primary key)
- user_id (foreign key, default 1 for MVP)
- title (string)
- description (text, nullable)
- duration (integer, seconds)
- conversion_status (string: pending, processing, completed, failed)
- original_extension (string, nullable - webm, mov, etc.)
- conversion_progress (tinyint, 0-100)
- conversion_error (text, nullable)
- converted_at (timestamp, nullable)
- share_token (string 64, unique)
- is_public (boolean, default true)
- share_expires_at (timestamp, nullable)
- timestamps
```

---

## Security Features

### 1. Secure Token System
- **64-character random tokens** (cryptographically secure)
- Unique and non-guessable
- Hidden from API responses
- Can be regenerated if compromised

### 2. File Upload Security
- **Validation**: `mimes:webm,mp4,mov|max:512000` (500MB)
- **MIME type verification** via Spatie Media Library
- **Automatic cleanup** when videos deleted

### 3. Share Link Validation
Public viewing checks:
- Token must exist
- Video must be `is_public = true`
- If expiration set, must be in future
- Returns 403 if any check fails

### 4. MVP Mode (Current Setup)
Authentication is **temporarily disabled**:
- Videos use `user_id = 1` (default user)
- All videos are public by default
- No login required

**When adding authentication later:**
1. Uncomment auth middleware in `routes/api.php` (line 45)
2. Remove `Auth::check()` conditions in VideoController
3. Implement login/registration
4. Add Sanctum tokens to frontend requests

---

## Troubleshooting

### "Failed to auto-save video"

**1. Check Browser Console (F12)**
Look for detailed logs:
- "Uploading video to /api/videos..."
- "Response status: XXX"
- Error details

**2. Common Issues**

**Database not migrated**
```bash
php artisan migrate
```

**Backend not running**
```bash
php artisan serve
```

**Storage not writable**
```bash
chmod -R 775 storage/ bootstrap/cache/
```

**File too large (>500MB)**
Update `php.ini`:
```ini
upload_max_filesize = 600M
post_max_size = 600M
```

**CORS issues**
Update `config/cors.php`:
```php
'allowed_origins' => ['http://localhost:5173'],
```

**3. Test API Directly**
```bash
curl http://localhost:8000/api/test
# Should return: {"message":"ScreenSense API is working!"}
```

**4. Check Logs**
```bash
tail -f storage/logs/laravel.log
```

**5. Quick Reset**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan migrate:fresh
```

---

## Key Files Modified

### Backend
- `app/Models/Video.php` - Video model with Spatie Media
- `app/Http/Controllers/VideoController.php` - Video CRUD + sharing
- `routes/api.php` - API routes (auth disabled for MVP)
- `database/migrations/*_create_videos_table.php` - Videos schema
- `database/migrations/*_add_share_token_to_videos_table.php` - Share tokens

### Frontend
- `frontend/src/views/RecordView.vue` - Auto-save + share link UI
- `frontend/vite.config.js` - API proxy to backend

---

## Development Notes

### Current State
✅ Recording works
✅ Auto-save after stop
✅ Shareable links generated
✅ Copy to clipboard
✅ Security tokens implemented
⚠️ Authentication disabled for MVP

### TODO (Future)
- Add authentication system
- Add user management
- Add video thumbnails
- Add video playback page
- Add rate limiting
- Add view tracking
- Add password-protected shares (optional)

### Environment Variables
Check `.env`:
```env
APP_URL=http://localhost:8000
DB_CONNECTION=mariadb
DB_DATABASE=screensense
FILESYSTEM_DISK=public
```

---

## Testing

### Manual Test Flow
1. Open http://localhost:5173
2. Start recording
3. Stop recording
4. Check console for upload logs
5. Verify green success banner shows
6. Copy share link
7. Open in incognito → should play

### Test with curl
```bash
curl -X POST http://localhost:8000/api/videos \
  -F "video=@test.webm" \
  -F "title=Test Video" \
  -F "duration=10" \
  -F "is_public=true"

# Test streaming endpoint with Range requests
curl -I -H "Range: bytes=0-1023" http://localhost:8000/api/videos/1/stream
# Should return: HTTP/1.1 206 Partial Content
```

---

## Packages Used

**Backend:**
- `spatie/laravel-medialibrary` - File storage & management
- Laravel 12 framework
- Sanctum (for future auth)

**Frontend:**
- Vue 3
- Vite
- MediaRecorder API

---

**Last Updated:** 2025-12-07
**Version:** 1.0 (MVP)
