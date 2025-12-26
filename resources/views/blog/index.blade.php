@extends('layouts.blog')

@section('title', 'Blog | ScreenBuddy - Screen Recording Tips & Guides')
@section('meta_description', 'Tips, guides, and insights about screen recording and async communication. Learn how to create better screen recordings for free.')

@push('styles')
<style>
    .hero {
        background: white;
        border-bottom: 1px solid #e5e7eb;
        padding: 4rem 0;
        text-align: center;
    }

    .hero h1 {
        font-size: 3rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 1rem;
    }

    .hero p {
        font-size: 1.25rem;
        color: #6b7280;
        max-width: 600px;
        margin: 0 auto;
    }

    .blog-list {
        max-width: 800px;
        margin: 0 auto;
        padding: 3rem 1.5rem;
    }

    .blog-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .blog-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .blog-meta {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
        font-size: 0.875rem;
        color: #6b7280;
    }

    .blog-category {
        background: #fff7ed;
        color: #ea580c;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-weight: 500;
        font-size: 0.75rem;
    }

    .blog-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.75rem;
        transition: color 0.2s;
    }

    .blog-card:hover .blog-title {
        color: #ea580c;
    }

    .blog-excerpt {
        color: #6b7280;
        margin-bottom: 1rem;
        line-height: 1.7;
    }

    .blog-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .blog-author {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .blog-read-more {
        color: #ea580c;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: gap 0.2s;
    }

    .blog-card:hover .blog-read-more {
        gap: 0.75rem;
    }

    .blog-read-more svg {
        width: 16px;
        height: 16px;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 0;
        color: #6b7280;
    }

    /* CTA Section */
    .cta-section {
        background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
        padding: 4rem 1.5rem;
        text-align: center;
        border-radius: 24px;
        margin: 3rem auto;
        max-width: 800px;
    }

    .cta-section h2 {
        color: white;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .cta-section p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.125rem;
        margin-bottom: 2rem;
    }

    .cta-button {
        display: inline-block;
        background: white;
        color: #ea580c;
        padding: 1rem 2rem;
        border-radius: 999px;
        font-weight: 600;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .cta-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 2rem;
    }

    .pagination a, .pagination span {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .pagination a {
        background: white;
        color: #6b7280;
    }

    .pagination a:hover {
        background: #f3f4f6;
    }

    .pagination .active {
        background: #ea580c;
        color: white;
    }

    @media (max-width: 768px) {
        .hero h1 {
            font-size: 2rem;
        }

        .hero p {
            font-size: 1rem;
        }

        .blog-card {
            padding: 1.5rem;
        }

        .blog-title {
            font-size: 1.25rem;
        }

        .cta-section {
            padding: 2rem 1.5rem;
            margin: 2rem 1rem;
        }

        .cta-section h2 {
            font-size: 1.5rem;
        }
    }
</style>
@endpush

@section('content')
    <!-- Hero -->
    <div class="hero">
        <div class="container">
            <h1>Blog</h1>
            <p>Tips, guides, and insights about screen recording and async communication.</p>
        </div>
    </div>

    <!-- Blog List -->
    <div class="blog-list">
        @forelse($blogs as $blog)
            <article class="blog-card">
                <a href="/blog/{{ $blog->slug }}">
                    <div class="blog-meta">
                        <span class="blog-category">{{ $blog->category }}</span>
                        <span>{{ $blog->published_at->format('M d, Y') }}</span>
                        <span>{{ $blog->read_time }} min read</span>
                    </div>
                    <h2 class="blog-title">{{ $blog->title }}</h2>
                    <p class="blog-excerpt">{{ $blog->excerpt }}</p>
                    <div class="blog-footer">
                        <span class="blog-author">By {{ $blog->author }}</span>
                        <span class="blog-read-more">
                            Read more
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    </div>
                </a>
            </article>
        @empty
            <div class="empty-state">
                <p>No blog posts yet. Check back soon!</p>
            </div>
        @endforelse

        <!-- Pagination -->
        @if($blogs->hasPages())
            <div class="pagination">
                @if($blogs->onFirstPage())
                    <span class="disabled">&laquo;</span>
                @else
                    <a href="{{ $blogs->previousPageUrl() }}">&laquo;</a>
                @endif

                @foreach($blogs->getUrlRange(1, $blogs->lastPage()) as $page => $url)
                    @if($page == $blogs->currentPage())
                        <span class="active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                @if($blogs->hasMorePages())
                    <a href="{{ $blogs->nextPageUrl() }}">&raquo;</a>
                @else
                    <span class="disabled">&raquo;</span>
                @endif
            </div>
        @endif
    </div>

    <!-- CTA -->
    <section class="cta-section">
        <h2>Ready to start recording?</h2>
        <p>Join thousands of users creating screen recordings with ScreenBuddy.</p>
        <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="cta-button">Get Started Free</a>
    </section>
@endsection
