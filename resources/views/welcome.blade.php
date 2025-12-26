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
            margin-bottom: 2rem;
        }

        .hero-badge-dot {
            width: 6px;
            height: 6px;
            background: #ea580c;
            border-radius: 50%;
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

        /* Features Section */
        .features {
            padding: 4rem 2rem 6rem;
            background: linear-gradient(180deg, #0a0a0b 0%, #111113 100%);
        }

        .features-inner {
            max-width: 1000px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
            <div class="hero-badge">
                <span class="hero-badge-dot"></span>
                <span>Open Source</span>
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
</body>
</html>
