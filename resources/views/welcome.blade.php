<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScreenSense - Screen Recording Made Simple</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #18181b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #ffffff;
        }

        .container {
            max-width: 1000px;
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .logo {
            width: 64px;
            height: 64px;
            background: #ea580c;
            border-radius: 16px;
            padding: 12px;
            box-shadow: 0 4px 20px rgba(234, 88, 12, 0.3);
        }

        .logo svg {
            width: 100%;
            height: 100%;
            fill: white;
        }

        h1 {
            color: white;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .mvp-badge {
            display: inline-block;
            background: rgba(234, 88, 12, 0.15);
            color: #ea580c;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 1px;
            border: 1px solid rgba(234, 88, 12, 0.3);
            margin-bottom: 1rem;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.25rem;
            font-weight: 400;
        }

        .card {
            background: #27272a;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            border: 1px solid #3f3f46;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .feature {
            padding: 1.5rem;
            background: #18181b;
            border-radius: 12px;
            border: 1px solid #3f3f46;
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        }

        .feature:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(234, 88, 12, 0.15);
            border-color: #ea580c;
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: #ea580c;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 24px;
        }

        .feature h3 {
            color: #ffffff;
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .feature p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .cta-section {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid #3f3f46;
        }

        .cta-text {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: #ea580c;
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
            box-shadow: 0 4px 15px rgba(234, 88, 12, 0.3);
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(234, 88, 12, 0.5);
            background: #dc2626;
        }

        .cta-button-secondary {
            background: transparent;
            border: 1px solid #3f3f46;
            color: rgba(255, 255, 255, 0.9);
        }

        .cta-button-secondary:hover {
            background: #3f3f46;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .api-status {
            text-align: center;
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(34, 197, 94, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .api-status .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #22c55e;
            font-weight: 500;
        }

        .api-status .status-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }

            .subtitle {
                font-size: 1rem;
            }

            .card {
                padding: 1.5rem;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .cta-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-section">
                <div class="logo">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </div>
            </div>
            <h1>ScreenSense</h1>
            <div class="mvp-badge">MVP VERSION</div>
            <p class="subtitle">Screen Recording Made Simple</p>
        </div>

        <div class="card">
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">🎥</div>
                    <h3>Screen Capture</h3>
                    <p>Record your entire screen, application window, or browser tab with one click.</p>
                </div>

                <div class="feature">
                    <div class="feature-icon">🎙️</div>
                    <h3>Audio Recording</h3>
                    <p>Optional microphone recording to add your voice to screen captures.</p>
                </div>

                <div class="feature">
                    <div class="feature-icon">🔗</div>
                    <h3>Instant Sharing</h3>
                    <p>Get shareable links immediately after recording with secure token-based access.</p>
                </div>
            </div>

            <div class="cta-section">
                <p class="cta-text">Sign in to start recording and sharing your screen</p>
                <div class="cta-buttons">
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="cta-button">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M13.8 12H3"/>
                        </svg>
                        Sign In
                    </a>
                    <a href="/privacy-policy" class="cta-button cta-button-secondary">
                        Learn More
                    </a>
                </div>
            </div>

            <div class="api-status">
                <div class="status-indicator">
                    <span class="status-dot"></span>
                    <span>API is operational</span>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Powered by Laravel & Vue.js</p>
        </div>
    </div>
</body>
</html>
