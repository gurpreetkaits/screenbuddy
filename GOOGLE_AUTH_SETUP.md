# Google OAuth Authentication Setup - ScreenSense

## ✅ Backend Setup Complete

### **1. What's Been Implemented**

#### **Database Changes:**
- ✅ `google_id` - Unique Google user identifier
- ✅ `avatar_url` - URL to Google profile picture
- ✅ `password` - Now nullable (OAuth users don't have passwords)

#### **Auth Flow:**
```
User clicks "Sign in with Google"
   ↓
Redirect to Google OAuth page
   ↓
User grants permission
   ↓
Google redirects back with user info
   ↓
Create/update user in database
   ↓
Generate Sanctum auth token
   ↓
Redirect to frontend with token
   ↓
Frontend stores token & user data
```

---

## 🔧 Backend Configuration

### **Files Modified:**

1. **`config/services.php`** - Google OAuth credentials
2. **`app/Models/User.php`** - Added Sanctum, Google fields
3. **`app/Http/Controllers/Auth/GoogleAuthController.php`** - OAuth logic
4. **`routes/api.php`** - Auth routes
5. **`database/migrations/*_add_google_oauth_to_users_table.php`** - Schema

### **API Endpoints:**

```http
GET /api/auth/google
# Redirects to Google OAuth page

GET /api/auth/google/callback
# Handles Google callback, creates user, returns token

GET /api/auth/me (requires token)
# Returns authenticated user info

POST /api/auth/logout (requires token)
# Logs out user, invalidates token
```

---

## 🔑 Google Cloud Console Setup

### **Step 1: Create OAuth Credentials**

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing: **"ScreenSense"**
3. Enable **Google+ API**:
   - Go to **APIs & Services** → **Library**
   - Search for "Google+ API"
   - Click **Enable**

4. Create **OAuth 2.0 Client ID**:
   - Go to **APIs & Services** → **Credentials**
   - Click **Create Credentials** → **OAuth client ID**
   - Application type: **Web application**
   - Name: **ScreenSense Web**

5. Configure **Authorized redirect URIs**:
   ```
   http://localhost:8000/api/auth/google/callback
   http://localhost:5173/auth/callback
   ```

6. Copy credentials:
   - **Client ID**: `123456789-abcdef.apps.googleusercontent.com`
   - **Client Secret**: `YOUR_SECRET_HERE`

### **Step 2: Update `.env` File**

Add to `/Users/gurpreetkait/code/ScreenSense/.env`:

```env
# Google OAuth
GOOGLE_CLIENT_ID=your-client-id-here
GOOGLE_CLIENT_SECRET=your-client-secret-here
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback

# Frontend URL
FRONTEND_URL=http://localhost:5173
```

---

## 🎨 Frontend Implementation (TODO)

### **1. Create Auth Store** (`frontend/src/stores/auth.js`)

```javascript
import { ref, computed } from 'vue'
import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token'))

  const isAuthenticated = computed(() => !!token.value)

  function setAuth(newToken, userData) {
    token.value = newToken
    user.value = userData
    localStorage.setItem('auth_token', newToken)
    localStorage.setItem('auth_user', JSON.stringify(userData))
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
  }

  function loadFromStorage() {
    const savedToken = localStorage.getItem('auth_token')
    const savedUser = localStorage.getItem('auth_user')

    if (savedToken && savedUser) {
      token.value = savedToken
      user.value = JSON.parse(savedUser)
    }
  }

  return { user, token, isAuthenticated, setAuth, logout, loadFromStorage }
})
```

### **2. Google Login Button**

```vue
<template>
  <button @click="loginWithGoogle" class="...">
    <svg><!-- Google Icon --></svg>
    Continue with Google
  </button>
</template>

<script setup>
function loginWithGoogle() {
  window.location.href = 'http://localhost:8000/api/auth/google'
}
</script>
```

### **3. Auth Callback Page** (`frontend/src/views/AuthCallback.vue`)

```vue
<template>
  <div class="loading">
    Completing sign in...
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  const token = params.get('token')
  const userJson = params.get('user')

  if (token && userJson) {
    const user = JSON.parse(decodeURIComponent(userJson))
    authStore.setAuth(token, user)
    router.push('/')
  } else {
    const error = params.get('error')
    router.push(`/login?error=${error}`)
  }
})
</script>
```

### **4. Add Authorization Header**

Update all API calls to include the token:

```javascript
// frontend/src/services/videoService.js
const API_BASE_URL = 'http://localhost:8000'

async function getVideos() {
  const token = localStorage.getItem('auth_token')

  const response = await fetch(`${API_BASE_URL}/api/videos`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    }
  })
  // ...
}
```

---

## 📝 Profile Management

### **Updated Profile Controller** (Already Exists)

Users can update:
- ✅ Name
- ✅ Username
- ✅ Bio
- ✅ Avatar (upload custom)
- ✅ Website
- ✅ Location

Users **CANNOT** update:
- ❌ Email (from Google)
- ❌ Password (OAuth users don't have passwords)
- ❌ Google ID

---

## 🎥 Recording Updates (TODO)

### **1. Set 4K Resolution**

Update `frontend/src/views/RecordView.vue`:

```javascript
// Change from:
const constraints = {
  video: {
    width: 1920,
    height: 1080  // 1080p
  }
}

// To:
const constraints = {
  video: {
    width: 3840,  // 4K width
    height: 2160  // 4K height
  },
  audio: true
}
```

### **2. Add Camera Overlay**

```javascript
// Get camera stream
const cameraStream = await navigator.mediaDevices.getUserMedia({
  video: {
    width: 320,  // Small camera window
    height: 240
  }
})

// Create canvas to combine screen + camera
const canvas = document.createElement('canvas')
canvas.width = 3840
canvas.height = 2160

// Draw screen on canvas
// Draw camera overlay in corner (e.g., bottom-right)
```

### **3. Remove Settings Modal**

Delete:
- Recording preferences UI
- Settings button
- Preferences modal

---

## 🔒 Security Features

### **CORS Configuration**

Update `config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => [
    'http://localhost:5173',
    env('FRONTEND_URL'),
],
'supports_credentials' => true,
```

### **Sanctum Configuration**

Already configured via `config/sanctum.php`:
- Stateful domains for SPA authentication
- Token expiration
- Token abilities

---

## ✅ Testing

### **1. Test Google Login**

```bash
# Visit login page
open http://localhost:5173/login

# Click "Sign in with Google"
# Should redirect to Google
# After auth, should redirect back with token
```

### **2. Test API with Token**

```bash
# Get token from localStorage in browser console
TOKEN="your-token-here"

# Test authenticated endpoint
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/auth/me
```

### **3. Test Profile Update**

```bash
curl -X POST http://localhost:8000/api/profile \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "Updated Name", "bio": "New bio"}'
```

---

## 🎯 Next Steps

1. ✅ Backend Google OAuth - **COMPLETE**
2. ⏳ **TODO:** Create frontend auth store
3. ⏳ **TODO:** Add Google login button
4. ⏳ **TODO:** Create auth callback page
5. ⏳ **TODO:** Update API calls with auth headers
6. ⏳ **TODO:** Update profile page (remove password change)
7. ⏳ **TODO:** Set recording to 4K
8. ⏳ **TODO:** Add camera overlay to recording
9. ⏳ **TODO:** Remove recording preferences UI

---

**Status:** Backend Complete ✅
**Next:** Frontend Implementation
**Last Updated:** 2025-12-07
