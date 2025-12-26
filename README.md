# ScreenSense

> Screen recording and sharing made simple. Record, share, and collaborate with instant shareable links.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![React](https://img.shields.io/badge/React-18-blue.svg)](https://reactjs.org)

## What is ScreenSense?

ScreenSense is an open-source screen recording and sharing platform that makes it easy to capture your screen, automatically generate shareable links, and collaborate with your team. Built with modern web technologies, it provides a seamless recording experience with instant sharing capabilities.

### Key Features

- **Screen Recording** - Record your screen, browser tab, or application window with one click
- **Instant Sharing** - Automatic shareable link generation after recording stops
- **Video Management** - Organize, edit titles, and manage your recordings
- **Secure Sharing** - 64-character cryptographic tokens for secure video sharing
- **Profile Management** - Customize your profile with avatar, bio, and website
- **Comments & Engagement** - Add comments and reactions to videos
- **Chrome Extension** - Record directly from your browser with our extension
- **Video Streaming** - Efficient streaming with range request support for instant seeking
- **Privacy Controls** - Toggle videos between public and private
- **Subscription Support** - Integrated with Polar.sh for premium features

## Screenshots

_Coming soon_

## Tech Stack

**Backend:**
- Laravel 12 (PHP 8.2+)
- MariaDB/MySQL
- Spatie Media Library (file management)
- FFmpeg (video processing)
- Queue system for background jobs

**Frontend:**
- React 18
- TypeScript
- Vite
- Tailwind CSS 4
- Radix UI components
- TanStack Query
- Wouter (routing)

**Browser Extension:**
- Chrome Extension Manifest V3
- MediaRecorder API

## Quick Start

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- MariaDB or MySQL
- FFmpeg (for video processing)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/screenbuddy.git
   cd screenbuddy
   ```

2. **Install backend dependencies**
   ```bash
   composer install
   ```

3. **Install frontend dependencies**
   ```bash
   cd frontend
   npm install
   cd ..
   ```

4. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Update `.env` with your database credentials**
   ```env
   DB_CONNECTION=mariadb
   DB_DATABASE=screenbuddy
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Create storage link**
   ```bash
   php artisan storage:link
   chmod -R 775 storage/ bootstrap/cache/
   ```

8. **Start the development servers**

   You need **three terminals** running simultaneously:

   **Terminal 1: Backend**
   ```bash
   php artisan serve
   # Runs on http://localhost:8000
   ```

   **Terminal 2: Frontend**
   ```bash
   cd frontend
   npm run dev
   # Runs on http://localhost:3000
   ```

   **Terminal 3: Queue Worker** (for video processing)
   ```bash
   php artisan queue:work --queue=default --tries=3 --timeout=1800
   ```

9. **Open your browser**
   Navigate to `http://localhost:3000`

### FFmpeg Setup

Install FFmpeg for video processing:

**macOS:**
```bash
brew install ffmpeg
```

**Ubuntu/Debian:**
```bash
sudo apt install ffmpeg
```

**Windows:**
Download from [ffmpeg.org](https://ffmpeg.org/download.html)

Update `.env` with FFmpeg paths:
```env
FFMPEG_BINARIES=/path/to/ffmpeg
FFPROBE_BINARIES=/path/to/ffprobe
```

## Documentation

- **[CLAUDE.md](CLAUDE.md)** - Development guide and API documentation
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Production deployment guide
- **[EXTENSION_GUIDE.md](EXTENSION_GUIDE.md)** - Chrome extension documentation
- **[COMMENTS_AND_VIEWS_GUIDE.md](COMMENTS_AND_VIEWS_GUIDE.md)** - Comments system guide
- **[VIDEO_OPTIMIZATION.md](VIDEO_OPTIMIZATION.md)** - Video processing optimization
- **[GOOGLE_AUTH_SETUP.md](GOOGLE_AUTH_SETUP.md)** - Google OAuth setup
- **[WEBHOOK_STATUS.md](WEBHOOK_STATUS.md)** - Webhook integration status

## API Endpoints

### Videos
- `GET /api/videos` - List videos
- `POST /api/videos` - Upload video
- `GET /api/videos/{id}` - Get video details
- `GET /api/videos/{id}/stream` - Stream video with range support
- `PUT /api/videos/{id}` - Update video
- `DELETE /api/videos/{id}` - Delete video
- `POST /api/videos/{id}/toggle-sharing` - Toggle public/private
- `POST /api/videos/{id}/regenerate-token` - Generate new share link

### Sharing
- `GET /api/share/video/{token}` - View shared video (public)

### Profile
- `GET /api/profile` - Get user profile
- `POST /api/profile` - Update profile
- `DELETE /api/profile/avatar` - Delete avatar

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to get started.

### Development Workflow

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests (`composer test`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to your branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## Security

If you discover a security vulnerability, please review our [Security Policy](SECURITY.md) for responsible disclosure instructions.

## Business Model

ScreenSense is **open-source software** with an optional commercial subscription service. The entire codebase is available under the MIT License, allowing you to:

- Use it for free
- Modify it for your needs
- Self-host your own instance
- Contribute improvements back

We offer a **hosted subscription service** at [screenbuddy.com](https://screenbuddy.com) with premium features:
- Unlimited storage
- Advanced analytics
- Priority support
- Custom branding
- Team collaboration features

This model is similar to other successful open-source projects like GitLab, Ghost, and Cal.com.

## License

ScreenSense is open-source software licensed under the [MIT License](LICENSE).

## Support

- **Documentation**: Check our [docs](./docs) folder
- **Issues**: [GitHub Issues](https://github.com/yourusername/screenbuddy/issues)
- **Discussions**: [GitHub Discussions](https://github.com/yourusername/screenbuddy/discussions)
- **Email**: support@screenbuddy.com

## Acknowledgments

Built with amazing open-source technologies:
- [Laravel](https://laravel.com)
- [React](https://reactjs.org)
- [Spatie Media Library](https://spatie.be/docs/laravel-medialibrary)
- [FFmpeg](https://ffmpeg.org)
- [Tailwind CSS](https://tailwindcss.com)

## Roadmap

- [ ] Mobile app (iOS/Android)
- [ ] Real-time collaboration
- [ ] Video editing features
- [ ] Custom domains for sharing
- [ ] Analytics dashboard
- [ ] Team workspaces
- [ ] API access for integrations

---

**Made with ❤️ by the ScreenSense community**
