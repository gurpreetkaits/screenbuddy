// Video API Service
const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000'

// Helper to get auth token
function getAuthToken() {
  return localStorage.getItem('auth_token')
}

// Helper to get auth headers
function getAuthHeaders() {
  const token = getAuthToken()
  const headers = {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }
  return headers
}

// Handle 401 responses by redirecting to login
function handleUnauthorized(response) {
  if (response.status === 401) {
    // Clear auth data
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    // Store current path for redirect after login
    localStorage.setItem('auth_redirect', window.location.pathname)
    // Redirect to login
    window.location.href = '/login'
    return true
  }
  return false
}

class VideoService {
  /**
   * Fetch all videos for the current user
   */
  async getVideos() {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return []

      if (!response.ok) {
        throw new Error(`Failed to fetch videos: ${response.statusText}`)
      }

      const data = await response.json()
      return data.videos || []
    } catch (error) {
      console.error('Error fetching videos:', error)
      throw error
    }
  }

  /**
   * Get a specific video by ID
   */
  async getVideo(id) {
    try {
      const headers = getAuthHeaders()
      headers['Cache-Control'] = 'no-cache, no-store, must-revalidate'
      headers['Pragma'] = 'no-cache'
      headers['Expires'] = '0'

      const response = await fetch(`${API_BASE_URL}/api/videos/${id}`, {
        method: 'GET',
        headers
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch video: ${response.statusText}`)
      }

      const data = await response.json()
      return data.video
    } catch (error) {
      console.error('Error fetching video:', error)
      throw error
    }
  }

  /**
   * Upload a new video
   */
  async uploadVideo(formData) {
    try {
      const token = getAuthToken()
      const headers = { 'Accept': 'application/json' }
      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }

      const response = await fetch(`${API_BASE_URL}/api/videos`, {
        method: 'POST',
        body: formData,
        headers
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to upload video: ${response.statusText}`)
      }

      const data = await response.json()
      return data.video
    } catch (error) {
      console.error('Error uploading video:', error)
      throw error
    }
  }

  /**
   * Update video details (title, description)
   */
  async updateVideo(id, updates) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}`, {
        method: 'PUT',
        headers: getAuthHeaders(),
        body: JSON.stringify(updates)
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to update video: ${response.statusText}`)
      }

      const data = await response.json()
      return data.video
    } catch (error) {
      console.error('Error updating video:', error)
      throw error
    }
  }

  /**
   * Delete a video
   */
  async deleteVideo(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}`, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to delete video: ${response.statusText}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Error deleting video:', error)
      throw error
    }
  }

  /**
   * Toggle video sharing status
   */
  async toggleSharing(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/toggle-sharing`, {
        method: 'POST',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to toggle sharing: ${response.statusText}`)
      }

      const data = await response.json()
      return data.video
    } catch (error) {
      console.error('Error toggling sharing:', error)
      throw error
    }
  }

  /**
   * Regenerate share token for a video
   */
  async regenerateShareToken(id) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${id}/regenerate-token`, {
        method: 'POST',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to regenerate token: ${response.statusText}`)
      }

      const data = await response.json()
      return data.video
    } catch (error) {
      console.error('Error regenerating token:', error)
      throw error
    }
  }

  /**
   * Get comments for a video
   */
  async getComments(videoId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${videoId}/comments`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return []

      if (!response.ok) {
        throw new Error(`Failed to fetch comments: ${response.statusText}`)
      }

      const data = await response.json()
      return data.comments || []
    } catch (error) {
      console.error('Error fetching comments:', error)
      return []
    }
  }

  /**
   * Add a comment to a video
   */
  async addComment(videoId, content, authorName = 'You', timestampSeconds = null) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${videoId}/comments`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({
          content,
          author_name: authorName,
          timestamp_seconds: timestampSeconds
        })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to add comment: ${response.statusText}`)
      }

      const data = await response.json()
      return data.comment
    } catch (error) {
      console.error('Error adding comment:', error)
      throw error
    }
  }

  /**
   * Delete a comment
   */
  async deleteComment(videoId, commentId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${videoId}/comments/${commentId}`, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return false

      if (!response.ok) {
        throw new Error(`Failed to delete comment: ${response.statusText}`)
      }

      return true
    } catch (error) {
      console.error('Error deleting comment:', error)
      throw error
    }
  }

  /**
   * Record a video view
   */
  async recordView(videoId, watchDuration = 0, completed = false) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${videoId}/view`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({
          watch_duration: watchDuration,
          completed
        })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        console.warn('Failed to record view:', response.statusText)
        return null
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.warn('Error recording view:', error)
      return null
    }
  }

  /**
   * Trim a video to specified start and end times
   */
  async trimVideo(videoId, startTime, endTime) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${videoId}/trim`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify({
          start_time: startTime,
          end_time: endTime
        })
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || `Failed to trim video: ${response.statusText}`)
      }

      const data = await response.json()
      return data.video
    } catch (error) {
      console.error('Error trimming video:', error)
      throw error
    }
  }

  /**
   * Get video statistics
   */
  async getVideoStats(videoId) {
    try {
      const response = await fetch(`${API_BASE_URL}/api/videos/${videoId}/stats`, {
        method: 'GET',
        headers: getAuthHeaders()
      })

      if (handleUnauthorized(response)) return null

      if (!response.ok) {
        throw new Error(`Failed to fetch stats: ${response.statusText}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Error fetching video stats:', error)
      return null
    }
  }
}

export default new VideoService()
