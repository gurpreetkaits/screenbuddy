@php
    $thumbnailUrl = $video->getThumbnailUrl();
    // Ensure thumbnail URL is absolute
    if ($thumbnailUrl && !str_starts_with($thumbnailUrl, 'http')) {
        $thumbnailUrl = url($thumbnailUrl);
    }
    // Fallback to a default preview image if no thumbnail
    // Note: For best compatibility, use a PNG file. SVG may not work on all platforms.
    $thumbnailUrl = $thumbnailUrl ?: url('/images/video-preview-default.svg');

    // Ensure video URL is absolute
    $absoluteVideoUrl = $videoUrl;
    if ($videoUrl && !str_starts_with($videoUrl, 'http')) {
        $absoluteVideoUrl = url($videoUrl);
    }

    $shareUrl = $video->getShareUrl();
    $description = $video->description ?: 'Watch this screen recording on ScreenSense';
    $duration = $video->duration ?? 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $video->title }} - ScreenSense</title>
    <meta name="description" content="{{ $description }}">

    <!-- Open Graph / Facebook / LinkedIn / Discord / Slack -->
    <meta property="og:title" content="{{ $video->title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="video.other">
    <meta property="og:url" content="{{ $shareUrl }}">
    <meta property="og:site_name" content="ScreenSense">

    <!-- OG Image (for link preview thumbnail) -->
    <meta property="og:image" content="{{ $thumbnailUrl }}">
    <meta property="og:image:width" content="1280">
    <meta property="og:image:height" content="720">
    <meta property="og:image:alt" content="{{ $video->title }}">

    <!-- OG Video (for platforms that support video embeds) -->
    <meta property="og:video" content="{{ $absoluteVideoUrl }}">
    <meta property="og:video:secure_url" content="{{ $absoluteVideoUrl }}">
    <meta property="og:video:type" content="video/webm">
    <meta property="og:video:width" content="1280">
    <meta property="og:video:height" content="720">
    @if($duration > 0)
    <meta property="video:duration" content="{{ $duration }}">
    @endif

    <!-- Twitter Card (summary_large_image shows thumbnail, player would embed video) -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $video->title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $thumbnailUrl }}">
    <meta name="twitter:image:alt" content="{{ $video->title }}">

    <!-- Additional meta for iMessage / Apple -->
    <meta name="apple-mobile-web-app-title" content="ScreenSense">
    <link rel="image_src" href="{{ $thumbnailUrl }}">

    <!-- Redirect to frontend for full-featured player -->
    <script>
        // Redirect to the Vue frontend for the full video player experience
        // Social media crawlers will get the meta tags above before JS runs
        window.location.replace('{{ $shareUrl }}');
    </script>
    <noscript>
        <meta http-equiv="refresh" content="0;url={{ $shareUrl }}">
    </noscript>

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 min-h-screen">
    <!-- Fallback content shown briefly before redirect, or if JS disabled -->
    <div class="max-w-5xl mx-auto px-4 py-8">
        <div class="text-center text-white">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-orange-500 border-t-transparent mx-auto mb-4"></div>
            <p class="text-gray-400">Redirecting to video player...</p>
            <p class="text-gray-500 text-sm mt-2">
                If you are not redirected, <a href="{{ $video->getShareUrl() }}" class="text-orange-500 hover:text-orange-400">click here</a>
            </p>
        </div>
    </div>
</body>
</html>
