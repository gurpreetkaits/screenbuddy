<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Blog | ScreenBuddy')</title>
    <meta name="description" content="@yield('meta_description', 'Tips, guides, and insights about screen recording and async communication from ScreenBuddy.')">

    <!-- Open Graph -->
    <meta property="og:title" content="@yield('og_title', 'ScreenBuddy Blog')">
    <meta property="og:description" content="@yield('og_description', 'Tips, guides, and insights about screen recording.')">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', 'ScreenBuddy Blog')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Tips, guides, and insights about screen recording.')">

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #fff7ed 0%, #ffffff 50%, #fef2f2 100%);
            min-height: 100vh;
            color: #1f2937;
            line-height: 1.6;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        /* Navigation */
        .nav {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            font-size: 1.25rem;
            color: #1f2937;
        }

        .nav-logo-icon {
            width: 36px;
            height: 36px;
            background: #ea580c;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-logo-icon svg {
            width: 20px;
            height: 20px;
            fill: white;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-link {
            color: #6b7280;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-link:hover {
            color: #ea580c;
        }

        .nav-cta {
            background: #ea580c;
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.2s, transform 0.2s;
        }

        .nav-cta:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        /* Footer */
        .footer {
            background: #18181b;
            color: white;
            padding: 3rem 0;
            margin-top: 4rem;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            text-align: center;
        }

        .footer-logo {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .footer-logo-icon {
            width: 32px;
            height: 32px;
            background: #ea580c;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer-logo-icon svg {
            width: 18px;
            height: 18px;
            fill: white;
        }

        .footer-text {
            color: #9ca3af;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .footer-link {
            color: #9ca3af;
            font-size: 0.875rem;
            transition: color 0.2s;
        }

        .footer-link:hover {
            color: #ea580c;
        }

        /* Utility classes */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        @media (max-width: 768px) {
            .nav-links {
                gap: 1rem;
            }

            .nav-link {
                display: none;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Navigation -->
    <nav class="nav">
        <div class="nav-container">
            <a href="/" class="nav-logo">
                <div class="nav-logo-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </div>
                ScreenBuddy
            </a>
            <div class="nav-links">
                <a href="/blog" class="nav-link">Blog</a>
                <a href="/privacy-policy" class="nav-link">Privacy</a>
                <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="nav-cta">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-logo">
                <div class="footer-logo-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </div>
                ScreenBuddy
            </div>
            <p class="footer-text">Screen recording made simple. No watermarks, no hassle.</p>
            <div class="footer-links">
                <a href="/blog" class="footer-link">Blog</a>
                <a href="/privacy-policy" class="footer-link">Privacy Policy</a>
                <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="footer-link">Sign In</a>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
