<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScreenSense - Screen Recording Made Simple</title>
    <meta name="description" content="Record your screen, capture audio, and share instantly with ScreenSense. Simple, fast, and secure screen recording.">
    <link rel="icon" type="image/png" href="/favicon.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #0a0a0b;
            min-height: 100vh;
            color: #ffffff;
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 1rem 2rem;
            background: rgba(10, 10, 11, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .navbar-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: white;
        }

        .navbar-logo {
            width: 40px;
            height: 40px;
            border-radius: 10px;
        }

        .navbar-title {
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: -0.02em;
        }

        .navbar-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .navbar-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .navbar-link:hover {
            color: white;
        }

        .navbar-link svg {
            width: 20px;
            height: 20px;
        }

        .btn-signin {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #ea580c;
            color: white;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, transform 0.2s;
        }

        .btn-signin:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8rem 2rem 4rem;
            text-align: center;
        }

        .hero-content {
            max-width: 800px;
        }

        .hero-badges {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(234, 88, 12, 0.1);
            color: #ea580c;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border: 1px solid rgba(234, 88, 12, 0.2);
        }

        .hero-badge-dot {
            width: 6px;
            height: 6px;
            background: #ea580c;
            border-radius: 50%;
        }

        .github-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.2s;
        }

        .github-badge:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .github-badge svg {
            width: 14px;
            height: 14px;
        }

        .github-badge-star {
            color: #fbbf24;
        }

        .hero-title {
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -0.03em;
            margin-bottom: 1.5rem;
        }

        .hero-title-gradient {
            background: linear-gradient(135deg, #ea580c 0%, #f97316 50%, #fbbf24 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-description {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.6;
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-cta {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #ea580c;
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 4px 20px rgba(234, 88, 12, 0.3);
        }

        .btn-primary:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 6px 30px rgba(234, 88, 12, 0.4);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* Demo Section */
        .demo {
            padding: 4rem 2rem;
            background: #0a0a0b;
        }

        .demo-inner {
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }

        .demo-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .demo-subtitle {
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 2rem;
        }

        .demo-video-card {
            background: linear-gradient(145deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .demo-video-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px 12px;
        }

        .demo-video-dots {
            display: flex;
            gap: 6px;
        }

        .demo-video-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .demo-video-dot.red { background: #ff5f56; }
        .demo-video-dot.yellow { background: #ffbd2e; }
        .demo-video-dot.green { background: #27ca40; }

        .demo-video-title {
            flex: 1;
            text-align: center;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
            font-weight: 500;
        }

        .demo-video-container {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            background: #000;
            aspect-ratio: 16 / 9;
        }

        .demo-video {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        /* Custom Video Controls */
        .video-controls {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 2rem 1rem 1rem;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .demo-video-container:hover .video-controls {
            opacity: 1;
        }

        .video-progress {
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
            margin-bottom: 12px;
            cursor: pointer;
            overflow: hidden;
        }

        .video-progress-bar {
            height: 100%;
            background: #ea580c;
            border-radius: 2px;
            width: 0%;
            transition: width 0.1s;
        }

        .video-controls-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .video-controls-left,
        .video-controls-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .video-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s, transform 0.2s;
        }

        .video-btn:hover {
            color: #ea580c;
            transform: scale(1.1);
        }

        .video-btn svg {
            width: 20px;
            height: 20px;
        }

        .video-btn.play-btn svg {
            width: 24px;
            height: 24px;
        }

        .video-time {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.7);
            font-family: monospace;
        }

        /* Play overlay */
        .video-play-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.3);
            cursor: pointer;
            transition: background 0.3s;
        }

        .video-play-overlay:hover {
            background: rgba(0,0,0,0.4);
        }

        .video-play-overlay.hidden {
            display: none;
        }

        .play-overlay-btn {
            width: 80px;
            height: 80px;
            background: rgba(234, 88, 12, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s, background 0.2s;
        }

        .video-play-overlay:hover .play-overlay-btn {
            transform: scale(1.1);
            background: #ea580c;
        }

        .play-overlay-btn svg {
            width: 32px;
            height: 32px;
            color: white;
            margin-left: 4px;
        }

        /* Features Section */
        .features {
            padding: 4rem 2rem 6rem;
            background: linear-gradient(180deg, #0a0a0b 0%, #111113 100%);
        }

        .features-inner {
            max-width: 1200px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s;
        }

        .feature-card:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(234, 88, 12, 0.3);
            transform: translateY(-4px);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: rgba(234, 88, 12, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            font-size: 1.5rem;
        }

        .feature-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: white;
        }

        .feature-description {
            font-size: 0.9375rem;
            color: rgba(255, 255, 255, 0.5);
            line-height: 1.6;
        }

        /* Pricing Section */
        .pricing {
            padding: 5rem 2rem;
            background: #0a0a0b;
        }

        .pricing-inner {
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }

        .pricing-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .pricing-subtitle {
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 3rem;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            max-width: 900px;
            margin: 0 auto;
        }

        .pricing-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            padding: 2rem;
            text-align: left;
            transition: all 0.3s;
        }

        .pricing-card:hover {
            border-color: rgba(255, 255, 255, 0.1);
        }

        .pricing-card.featured {
            border-color: rgba(234, 88, 12, 0.4);
            background: rgba(234, 88, 12, 0.05);
        }

        .pricing-card.featured:hover {
            border-color: rgba(234, 88, 12, 0.6);
        }

        .pricing-card-badge {
            display: inline-block;
            background: #ea580c;
            color: white;
            font-size: 0.625rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
        }

        .pricing-card-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .pricing-card-price {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .pricing-card-price span {
            font-size: 1rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.5);
        }

        .pricing-card-description {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        .pricing-features {
            list-style: none;
            margin-bottom: 1.5rem;
        }

        .pricing-features li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0.75rem;
        }

        .pricing-features li svg {
            width: 16px;
            height: 16px;
            color: #22c55e;
            flex-shrink: 0;
        }

        .pricing-btn {
            display: block;
            width: 100%;
            text-align: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .pricing-btn-outline {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .pricing-btn-outline:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .pricing-btn-primary {
            background: #ea580c;
            border: 1px solid #ea580c;
            color: white;
        }

        .pricing-btn-primary:hover {
            background: #dc2626;
            border-color: #dc2626;
        }

        /* Footer */
        .footer {
            padding: 2rem;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .footer-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.875rem;
        }

        .footer-link {
            color: rgba(255, 255, 255, 0.4);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-link:hover {
            color: rgba(255, 255, 255, 0.7);
        }

        .footer-divider {
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }

            .navbar-title {
                display: none;
            }

            .navbar-links {
                gap: 1rem;
            }

            .hero {
                padding: 7rem 1.5rem 3rem;
            }

            .hero-description {
                font-size: 1.1rem;
            }

            .hero-cta {
                flex-direction: column;
            }

            .btn-primary, .btn-secondary {
                width: 100%;
                justify-content: center;
            }

            .features {
                padding: 3rem 1.5rem 4rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-inner">
            <a href="/" class="navbar-brand">
                <img src="/logo.png" alt="ScreenSense" class="navbar-logo">
                <span class="navbar-title">ScreenSense</span>
            </a>
            <div class="navbar-links">
                <a href="/blog" class="navbar-link">Blog</a>
                <a href="https://github.com/gurpreetkaits/screensense" target="_blank" rel="noopener noreferrer" class="navbar-link">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    <span>GitHub</span>
                </a>
                <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="btn-signin">
                    Sign In
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-badges">
                <div class="hero-badge">
                    <span class="hero-badge-dot"></span>
                    <span>Open Source</span>
                </div>
                <a href="https://github.com/gurpreetkaits/screensense" target="_blank" rel="noopener noreferrer" class="github-badge">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    <svg class="github-badge-star" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <span id="github-stars">--</span>
                </a>
            </div>
            <h1 class="hero-title">
                Screen Recording<br>
                <span class="hero-title-gradient">Made Simple</span>
            </h1>
            <p class="hero-description">
                Capture your screen, record audio, and share instantly with secure links.
                No complex software, just record and share.
            </p>
            <div class="hero-cta">
                <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M13.8 12H3"/>
                    </svg>
                    Get Started
                </a>
                <a href="https://github.com/gurpreetkaits/screensense" target="_blank" rel="noopener noreferrer" class="btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    View on GitHub
                </a>
            </div>
        </div>
    </section>

    <!-- Demo Section -->
    <section class="demo">
        <div class="demo-inner">
            <h2 class="demo-title">See It in Action</h2>
            <p class="demo-subtitle">Watch how easy it is to record and share your screen</p>

            <div class="demo-video-card">
                <div class="demo-video-header">
                    <div class="demo-video-dots">
                        <span class="demo-video-dot red"></span>
                        <span class="demo-video-dot yellow"></span>
                        <span class="demo-video-dot green"></span>
                    </div>
                    <span class="demo-video-title">ScreenSense Demo</span>
                    <div style="width: 54px;"></div>
                </div>

                <div class="demo-video-container">
                    <video id="demoVideo" class="demo-video" playsinline preload="metadata">
                        <source src="/demo.webm" type="video/webm">
                        <source src="/demo.mp4" type="video/mp4">
                    </video>

                    <!-- Play Overlay -->
                    <div class="video-play-overlay" id="playOverlay">
                        <div class="play-overlay-btn">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                            </svg>
                        </div>
                    </div>

                    <!-- Custom Controls -->
                    <div class="video-controls" id="videoControls">
                        <div class="video-progress" id="progressBar">
                            <div class="video-progress-bar" id="progressFill"></div>
                        </div>
                        <div class="video-controls-row">
                            <div class="video-controls-left">
                                <button class="video-btn play-btn" id="playPauseBtn">
                                    <svg id="playIcon" viewBox="0 0 24 24" fill="currentColor">
                                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                    </svg>
                                    <svg id="pauseIcon" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
                                        <rect x="6" y="4" width="4" height="16"></rect>
                                        <rect x="14" y="4" width="4" height="16"></rect>
                                    </svg>
                                </button>
                                <button class="video-btn" id="muteBtn">
                                    <svg id="volumeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                                        <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                                    </svg>
                                    <svg id="muteIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                                        <line x1="23" y1="9" x2="17" y2="15"></line>
                                        <line x1="17" y1="9" x2="23" y2="15"></line>
                                    </svg>
                                </button>
                                <span class="video-time"><span id="currentTime">0:00</span> / <span id="duration">0:00</span></span>
                            </div>
                            <div class="video-controls-right">
                                <button class="video-btn" id="fullscreenBtn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="features-inner">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🎥</div>
                    <h3 class="feature-title">Screen Capture</h3>
                    <p class="feature-description">Record your entire screen, application window, or browser tab with a single click.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎙️</div>
                    <h3 class="feature-title">Audio Recording</h3>
                    <p class="feature-description">Add your voice with optional microphone recording to explain what's on screen.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔗</div>
                    <h3 class="feature-title">Instant Sharing</h3>
                    <p class="feature-description">Get shareable links immediately after recording with secure token-based access.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🧩</div>
                    <h3 class="feature-title">
                        Browser Extension
                        <span style="display: inline-block; background: rgba(234, 88, 12, 0.2); color: #ea580c; font-size: 0.625rem; font-weight: 600; padding: 0.25rem 0.5rem; border-radius: 50px; margin-left: 0.5rem; vertical-align: middle; text-transform: uppercase; letter-spacing: 0.05em;">Coming Soon</span>
                    </h3>
                    <p class="feature-description">Record directly from your browser with our Chrome extension. One-click capture without leaving your workflow.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing">
        <div class="pricing-inner">
            <h2 class="pricing-title">Simple, Transparent Pricing</h2>
            <p class="pricing-subtitle">Start free, upgrade when you need more</p>

            <div class="pricing-grid">
                <!-- Free Plan -->
                <div class="pricing-card">
                    <h3 class="pricing-card-name">Free</h3>
                    <div class="pricing-card-price">$0</div>
                    <p class="pricing-card-description">Perfect for trying out ScreenSense</p>
                    <ul class="pricing-features">
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            1 video recording
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Screen + audio capture
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Shareable links
                        </li>
                    </ul>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="pricing-btn pricing-btn-outline">
                        Get Started
                    </a>
                </div>

                <!-- Pro Monthly -->
                <div class="pricing-card featured">
                    <span class="pricing-card-badge">Most Popular</span>
                    <h3 class="pricing-card-name">Pro Monthly</h3>
                    <div class="pricing-card-price">$7<span>/month</span></div>
                    <p class="pricing-card-description">For creators who need more</p>
                    <ul class="pricing-features">
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Unlimited recordings
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Screen + audio capture
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Shareable links
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Priority support
                        </li>
                    </ul>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="pricing-btn pricing-btn-primary">
                        Get Started
                    </a>
                </div>

                <!-- Pro Yearly -->
                <div class="pricing-card">
                    <span class="pricing-card-badge" style="background: #22c55e;">Save $4</span>
                    <h3 class="pricing-card-name">Pro Yearly</h3>
                    <div class="pricing-card-price">$80<span>/year</span></div>
                    <p class="pricing-card-description">Best value for long-term use</p>
                    <ul class="pricing-features">
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Unlimited recordings
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Screen + audio capture
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Shareable links
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Priority support
                        </li>
                    </ul>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="pricing-btn pricing-btn-outline">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <span>&copy; {{ date('Y') }} ScreenSense</span>
            <span class="footer-divider"></span>
            <a href="/privacy-policy" class="footer-link">Privacy Policy</a>
            <span class="footer-divider"></span>
            <a href="https://github.com/gurpreetkaits/screensense" target="_blank" rel="noopener noreferrer" class="footer-link">GitHub</a>
        </div>
    </footer>

    <script>
        // Fetch GitHub stars
        fetch('https://api.github.com/repos/gurpreetkaits/screensense')
            .then(response => response.json())
            .then(data => {
                const stars = data.stargazers_count || 0;
                document.getElementById('github-stars').textContent = stars;
            })
            .catch(() => {
                document.getElementById('github-stars').textContent = '0';
            });

        // Custom Video Controls
        const video = document.getElementById('demoVideo');
        const playOverlay = document.getElementById('playOverlay');
        const playPauseBtn = document.getElementById('playPauseBtn');
        const playIcon = document.getElementById('playIcon');
        const pauseIcon = document.getElementById('pauseIcon');
        const muteBtn = document.getElementById('muteBtn');
        const volumeIcon = document.getElementById('volumeIcon');
        const muteIcon = document.getElementById('muteIcon');
        const progressBar = document.getElementById('progressBar');
        const progressFill = document.getElementById('progressFill');
        const currentTimeEl = document.getElementById('currentTime');
        const durationEl = document.getElementById('duration');
        const fullscreenBtn = document.getElementById('fullscreenBtn');

        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }

        function togglePlay() {
            if (video.paused) {
                video.play();
                playOverlay.classList.add('hidden');
                playIcon.style.display = 'none';
                pauseIcon.style.display = 'block';
            } else {
                video.pause();
                playIcon.style.display = 'block';
                pauseIcon.style.display = 'none';
            }
        }

        function toggleMute() {
            video.muted = !video.muted;
            volumeIcon.style.display = video.muted ? 'none' : 'block';
            muteIcon.style.display = video.muted ? 'block' : 'none';
        }

        function updateProgress() {
            const percent = (video.currentTime / video.duration) * 100;
            progressFill.style.width = percent + '%';
            currentTimeEl.textContent = formatTime(video.currentTime);
        }

        function setProgress(e) {
            const rect = progressBar.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            video.currentTime = percent * video.duration;
        }

        function toggleFullscreen() {
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                video.parentElement.requestFullscreen();
            }
        }

        // Event Listeners
        playOverlay.addEventListener('click', togglePlay);
        playPauseBtn.addEventListener('click', togglePlay);
        muteBtn.addEventListener('click', toggleMute);
        video.addEventListener('timeupdate', updateProgress);
        video.addEventListener('loadedmetadata', () => {
            durationEl.textContent = formatTime(video.duration);
        });
        video.addEventListener('ended', () => {
            playOverlay.classList.remove('hidden');
            playIcon.style.display = 'block';
            pauseIcon.style.display = 'none';
        });
        progressBar.addEventListener('click', setProgress);
        fullscreenBtn.addEventListener('click', toggleFullscreen);
        video.addEventListener('click', togglePlay);
    </script>
</body>
</html>
