@extends('layouts.blog')

@section('title', $blog->meta_title ?? $blog->title . ' | ScreenSense Blog')
@section('meta_description', $blog->meta_description ?? $blog->excerpt)
@section('og_title', $blog->title)
@section('og_description', $blog->excerpt)
@section('og_type', 'article')
@section('twitter_title', $blog->title)
@section('twitter_description', $blog->excerpt)

@push('styles')
<style>
    .article-header {
        padding: 3rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .article-header-content {
        max-width: 800px;
        margin: 0 auto;
        padding: 0 1.5rem;
    }

    .breadcrumb {
        margin-bottom: 1.5rem;
    }

    .breadcrumb a {
        color: #ea580c;
        font-weight: 500;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: color 0.2s;
    }

    .breadcrumb a:hover {
        color: #f97316;
    }

    .breadcrumb svg {
        width: 16px;
        height: 16px;
    }

    .article-meta {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.5);
    }

    .article-category {
        background: rgba(234, 88, 12, 0.15);
        color: #ea580c;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-weight: 500;
        font-size: 0.75rem;
    }

    .article-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #ffffff;
        line-height: 1.2;
        margin-bottom: 1.5rem;
    }

    .article-author {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .author-avatar {
        width: 40px;
        height: 40px;
        background: rgba(234, 88, 12, 0.15);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ea580c;
        font-weight: 600;
    }

    .author-name {
        font-weight: 500;
        color: rgba(255, 255, 255, 0.9);
    }

    /* Article Content */
    .article-body {
        max-width: 800px;
        margin: 0 auto;
        padding: 3rem 1.5rem;
    }

    .prose {
        font-size: 1.125rem;
        line-height: 1.8;
        color: rgba(255, 255, 255, 0.8);
    }

    .prose h2 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #ffffff;
        margin-top: 2.5rem;
        margin-bottom: 1rem;
    }

    .prose h3 {
        font-size: 1.375rem;
        font-weight: 600;
        color: #ffffff;
        margin-top: 2rem;
        margin-bottom: 0.75rem;
    }

    .prose p {
        margin-bottom: 1.25rem;
    }

    .prose p.lead {
        font-size: 1.25rem;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.7;
    }

    .prose ul, .prose ol {
        margin: 1.25rem 0;
        padding-left: 1.5rem;
    }

    .prose ul {
        list-style-type: disc;
    }

    .prose ol {
        list-style-type: decimal;
    }

    .prose li {
        margin-bottom: 0.5rem;
    }

    .prose strong {
        font-weight: 600;
        color: #ffffff;
    }

    .prose blockquote {
        border-left: 4px solid #ea580c;
        padding: 1rem 1.5rem;
        margin: 1.5rem 0;
        background: rgba(234, 88, 12, 0.1);
        border-radius: 0 8px 8px 0;
    }

    .prose blockquote p {
        font-style: italic;
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 0;
    }

    .prose blockquote cite {
        display: block;
        margin-top: 0.75rem;
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.5);
        font-style: normal;
    }

    .prose table {
        width: 100%;
        margin: 1.5rem 0;
        border-collapse: collapse;
    }

    .prose th, .prose td {
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 0.75rem 1rem;
        text-align: left;
    }

    .prose th {
        background: rgba(255, 255, 255, 0.05);
        font-weight: 600;
        color: #ffffff;
    }

    .prose a {
        color: #ea580c;
        text-decoration: underline;
        transition: color 0.2s;
    }

    .prose a:hover {
        color: #f97316;
    }

    .prose code {
        background: rgba(255, 255, 255, 0.1);
        padding: 0.2rem 0.4rem;
        border-radius: 4px;
        font-size: 0.9em;
    }

    .prose pre {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 1rem;
        overflow-x: auto;
        margin: 1.5rem 0;
    }

    .prose pre code {
        background: none;
        padding: 0;
    }

    /* Tags */
    .article-tags {
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
    }

    .tags-title {
        font-size: 0.875rem;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 0.75rem;
    }

    .tags-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .tag {
        background: rgba(255, 255, 255, 0.05);
        color: rgba(255, 255, 255, 0.7);
        padding: 0.375rem 0.875rem;
        border-radius: 999px;
        font-size: 0.875rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Share */
    .article-share {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
    }

    .share-title {
        font-size: 0.875rem;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 0.75rem;
    }

    .share-buttons {
        display: flex;
        gap: 0.75rem;
    }

    .share-button {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        cursor: pointer;
    }

    .share-button:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(234, 88, 12, 0.3);
    }

    .share-button svg {
        width: 20px;
        height: 20px;
        color: rgba(255, 255, 255, 0.7);
    }

    /* CTA */
    .cta-section {
        background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
        padding: 3rem 2rem;
        text-align: center;
        border-radius: 16px;
        margin-top: 3rem;
    }

    .cta-section h2 {
        color: white;
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
    }

    .cta-section p {
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 1.5rem;
    }

    .cta-button {
        display: inline-block;
        background: white;
        color: #ea580c;
        padding: 0.875rem 2rem;
        border-radius: 12px;
        font-weight: 600;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .cta-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    /* Toast */
    .toast {
        position: fixed;
        bottom: 2rem;
        left: 50%;
        transform: translateX(-50%) translateY(100px);
        background: #ffffff;
        color: #0a0a0b;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 500;
        opacity: 0;
        transition: transform 0.3s, opacity 0.3s;
        z-index: 1000;
    }

    .toast.show {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }

    @media (max-width: 768px) {
        .article-title {
            font-size: 1.75rem;
        }

        .article-meta {
            flex-wrap: wrap;
        }

        .prose {
            font-size: 1rem;
        }

        .prose h2 {
            font-size: 1.5rem;
        }

        .cta-section {
            padding: 2rem 1.5rem;
        }

        .cta-section h2 {
            font-size: 1.5rem;
        }
    }
</style>
@endpush

@section('content')
    <!-- Article Header -->
    <header class="article-header">
        <div class="article-header-content">
            <nav class="breadcrumb">
                <a href="/blog">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to blog
                </a>
            </nav>

            <div class="article-meta">
                <span class="article-category">{{ $blog->category }}</span>
                <span>{{ $blog->published_at->format('M d, Y') }}</span>
                <span>{{ $blog->read_time }} min read</span>
            </div>

            <h1 class="article-title">{{ $blog->title }}</h1>

            <div class="article-author">
                <div class="author-avatar">{{ substr($blog->author, 0, 1) }}</div>
                <span class="author-name">{{ $blog->author }}</span>
            </div>
        </div>
    </header>

    <!-- Article Body -->
    <article class="article-body">
        <div class="prose">
            {!! $blog->content !!}
        </div>

        @if($blog->tags && count($blog->tags) > 0)
            <div class="article-tags">
                <p class="tags-title">Tags</p>
                <div class="tags-list">
                    @foreach($blog->tags as $tag)
                        <span class="tag">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="article-share">
            <p class="share-title">Share this article</p>
            <div class="share-buttons">
                <button class="share-button" onclick="shareTwitter()" title="Share on Twitter">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                    </svg>
                </button>
                <button class="share-button" onclick="shareLinkedIn()" title="Share on LinkedIn">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                    </svg>
                </button>
                <button class="share-button" onclick="copyLink()" title="Copy link">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- CTA -->
        <div class="cta-section">
            <h2>Ready to try ScreenSense?</h2>
            <p>Start recording your screen for free. No credit card required.</p>
            <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="cta-button">Get Started Free</a>
        </div>
    </article>

    <!-- Toast notification -->
    <div id="toast" class="toast"></div>
@endsection

@push('scripts')
<script>
    function shareTwitter() {
        const url = encodeURIComponent(window.location.href);
        const text = encodeURIComponent('{{ $blog->title }}');
        window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank');
    }

    function shareLinkedIn() {
        const url = encodeURIComponent(window.location.href);
        window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank');
    }

    function copyLink() {
        navigator.clipboard.writeText(window.location.href).then(() => {
            showToast('Link copied to clipboard!');
        }).catch(() => {
            showToast('Failed to copy link');
        });
    }

    function showToast(message) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
</script>
@endpush
