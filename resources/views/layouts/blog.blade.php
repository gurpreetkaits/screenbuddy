<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Blog | ScreenSense')</title>
    <meta name="description" content="@yield('meta_description', 'Tips, guides, and insights about screen recording and async communication from ScreenSense.')">

    <!-- Open Graph -->
    <meta property="og:title" content="@yield('og_title', 'ScreenSense Blog')">
    <meta property="og:description" content="@yield('og_description', 'Tips, guides, and insights about screen recording.')">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', 'ScreenSense Blog')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Tips, guides, and insights about screen recording.')">

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Favicon -->
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
            line-height: 1.6;
        }

        a {
            color: inherit;
            text-decoration: none;
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

        .navbar-link.active {
            color: #ea580c;
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

        /* Main content */
        main {
            padding-top: 5rem;
            min-height: calc(100vh - 200px);
        }

        /* Footer */
        .footer {
            padding: 2rem;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            margin-top: 4rem;
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

        /* Utility classes */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

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

            main {
                padding-top: 4.5rem;
            }
        }
    </style>
    @stack('styles')
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
                <a href="/blog" class="navbar-link active">Blog</a>
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

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

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

    @stack('scripts')
</body>
</html>
