<?php

namespace Database\Seeders;

use App\Models\Blog;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $blogs = [
            [
                'title' => 'Free Screen Recording: The Complete Guide for 2025',
                'slug' => 'free-screen-recording-complete-guide-2025',
                'excerpt' => 'Looking for a free screen recording solution? Learn how to capture your screen without spending a dime. Perfect for tutorials, demos, and presentations.',
                'content' => $this->getFreeScreenRecordingContent(),
                'author' => 'ScreenBuddy Team',
                'category' => 'Guides',
                'tags' => ['free', 'screen recording', 'tutorial', 'guide', 'beginners'],
                'meta_title' => 'Free Screen Recording Guide 2025 | ScreenBuddy',
                'meta_description' => 'Discover how to record your screen for free with ScreenBuddy. No watermarks, no time limits. Perfect for creating tutorials and demos.',
                'read_time' => 5,
                'is_published' => true,
                'published_at' => now()->subDays(7),
            ],
            [
                'title' => 'ScreenBuddy vs Loom: The Affordable Alternative That Gets the Job Done',
                'slug' => 'screenbuddy-vs-loom-affordable-alternative',
                'excerpt' => 'Tired of expensive screen recording subscriptions? See how ScreenBuddy offers the same features as Loom at a fraction of the cost.',
                'content' => $this->getLoomAlternativeContent(),
                'author' => 'ScreenBuddy Team',
                'category' => 'Comparisons',
                'tags' => ['loom alternative', 'affordable', 'comparison', 'pricing', 'screen recording'],
                'meta_title' => 'ScreenBuddy vs Loom: Affordable Screen Recording Alternative',
                'meta_description' => 'Compare ScreenBuddy and Loom. Get the same screen recording features at 80% lower cost. No compromises on quality.',
                'read_time' => 4,
                'is_published' => true,
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Why Simple Screen Recording Tools Win: The Case for MVP Solutions',
                'slug' => 'simple-screen-recording-tools-mvp',
                'excerpt' => 'In a world of bloated software, sometimes less is more. Discover why simple, focused screen recording tools are winning over complex alternatives.',
                'content' => $this->getMvpContent(),
                'author' => 'ScreenBuddy Team',
                'category' => 'Product',
                'tags' => ['mvp', 'simple', 'productivity', 'screen recording', 'minimalism'],
                'meta_title' => 'Simple Screen Recording Tools: Why Less is More | ScreenBuddy',
                'meta_description' => 'Learn why simple screen recording tools outperform bloated alternatives. ScreenBuddy focuses on what matters: easy, fast, reliable screen capture.',
                'read_time' => 3,
                'is_published' => true,
                'published_at' => now(),
            ],
        ];

        foreach ($blogs as $blog) {
            Blog::updateOrCreate(
                ['slug' => $blog['slug']],
                $blog
            );
        }
    }

    private function getFreeScreenRecordingContent(): string
    {
        return <<<'HTML'
<article class="blog-content">
    <p class="lead">Screen recording has become an essential skill in today's digital world. Whether you're creating tutorials, documenting bugs, or sharing quick demos with your team, having a reliable screen recording tool is crucial. The good news? You don't need to spend a fortune to get started.</p>

    <h2>Why Screen Recording Matters</h2>
    <p>A picture is worth a thousand words, but a video? That's worth a million. Screen recordings help you:</p>
    <ul>
        <li><strong>Save time</strong> - Show, don't tell. A 2-minute video can replace a 20-minute email chain.</li>
        <li><strong>Reduce miscommunication</strong> - Visual demonstrations eliminate ambiguity.</li>
        <li><strong>Create lasting documentation</strong> - Build a library of how-to videos for your team.</li>
        <li><strong>Enhance remote collaboration</strong> - Bridge the gap between distributed teams.</li>
    </ul>

    <h2>What to Look for in a Free Screen Recorder</h2>
    <p>Not all free screen recording tools are created equal. Here's what matters:</p>

    <h3>1. No Watermarks</h3>
    <p>Many "free" tools slap their logo on your recordings. That's fine for personal use, but unprofessional for business. Look for tools that offer clean, watermark-free recordings.</p>

    <h3>2. Reasonable Time Limits</h3>
    <p>Some free tools limit you to 5-minute recordings. That's barely enough for a quick demo. ScreenBuddy offers generous recording limits even on the free tier.</p>

    <h3>3. Easy Sharing</h3>
    <p>What good is a recording if you can't share it? Look for instant link generation and cloud storage.</p>

    <h3>4. Audio Support</h3>
    <p>Being able to record your microphone alongside your screen is essential for tutorials and explanations.</p>

    <h2>Getting Started with ScreenBuddy</h2>
    <p>ScreenBuddy makes screen recording dead simple:</p>
    <ol>
        <li><strong>Sign up for free</strong> - No credit card required.</li>
        <li><strong>Click record</strong> - Choose your screen and start capturing.</li>
        <li><strong>Share instantly</strong> - Get a shareable link the moment you stop recording.</li>
    </ol>

    <h2>Pro Tips for Better Screen Recordings</h2>
    <ul>
        <li><strong>Clean your desktop</strong> - Hide sensitive information and close unnecessary tabs.</li>
        <li><strong>Use a quality microphone</strong> - Good audio makes a huge difference.</li>
        <li><strong>Plan your flow</strong> - Know what you want to show before you hit record.</li>
        <li><strong>Keep it short</strong> - Aim for under 5 minutes when possible.</li>
    </ul>

    <h2>Start Recording Today</h2>
    <p>Don't let expensive software hold you back. With ScreenBuddy, you can start creating professional screen recordings in seconds - completely free. No downloads, no watermarks, no hassle.</p>

    <p><strong>Ready to try it?</strong> Sign up for free and record your first video today.</p>
</article>
HTML;
    }

    private function getLoomAlternativeContent(): string
    {
        return <<<'HTML'
<article class="blog-content">
    <p class="lead">Loom revolutionized async video communication, but at $15/month per user, it's not exactly budget-friendly. Enter ScreenBuddy: all the features you need at a price that makes sense.</p>

    <h2>The Problem with Loom's Pricing</h2>
    <p>Let's do the math. For a team of 10 people:</p>
    <ul>
        <li>Loom Business: $150/month ($1,800/year)</li>
        <li>ScreenBuddy Pro: $7/month per user ($840/year)</li>
        <li><strong>Your savings: $960/year</strong></li>
    </ul>
    <p>That's money you could spend on actually growing your business.</p>

    <h2>Feature Comparison</h2>
    <table class="comparison-table">
        <thead>
            <tr>
                <th>Feature</th>
                <th>Loom</th>
                <th>ScreenBuddy</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Screen Recording</td>
                <td>Yes</td>
                <td>Yes</td>
            </tr>
            <tr>
                <td>Microphone Audio</td>
                <td>Yes</td>
                <td>Yes</td>
            </tr>
            <tr>
                <td>Instant Sharing</td>
                <td>Yes</td>
                <td>Yes</td>
            </tr>
            <tr>
                <td>HD Quality</td>
                <td>Yes</td>
                <td>Yes (up to 4K)</td>
            </tr>
            <tr>
                <td>Cloud Storage</td>
                <td>Yes</td>
                <td>Yes</td>
            </tr>
            <tr>
                <td>Free Tier</td>
                <td>5 min limit</td>
                <td>1 video</td>
            </tr>
            <tr>
                <td>Pro Price</td>
                <td>$15/month</td>
                <td>$7/month</td>
            </tr>
        </tbody>
    </table>

    <h2>What ScreenBuddy Does Differently</h2>

    <h3>1. Focused Simplicity</h3>
    <p>We don't try to be everything. ScreenBuddy does one thing exceptionally well: screen recording. No bloated features you'll never use.</p>

    <h3>2. Transparent Pricing</h3>
    <p>$7/month for unlimited recordings. That's it. No tiers to navigate, no "contact sales" for basic features.</p>

    <h3>3. No Lock-in</h3>
    <p>Your videos are yours. Download them anytime. Cancel anytime. We earn your business every month.</p>

    <h2>Who Should Switch?</h2>
    <p>ScreenBuddy is perfect for:</p>
    <ul>
        <li><strong>Startups</strong> - Every dollar counts when you're bootstrapping.</li>
        <li><strong>Freelancers</strong> - Professional tools without enterprise pricing.</li>
        <li><strong>Small teams</strong> - Collaborate effectively without breaking the budget.</li>
        <li><strong>Educators</strong> - Create course content affordably.</li>
    </ul>

    <h2>Making the Switch</h2>
    <p>Switching from Loom is painless:</p>
    <ol>
        <li>Sign up for ScreenBuddy (takes 30 seconds)</li>
        <li>Start recording immediately</li>
        <li>Share your new videos with the same ease</li>
    </ol>

    <h2>The Bottom Line</h2>
    <p>Loom is a great product, but you're paying a premium for the brand name. ScreenBuddy delivers the same core functionality at a fraction of the cost. For most users, that's a no-brainer.</p>

    <p><strong>Try ScreenBuddy free today</strong> and see the difference for yourself.</p>
</article>
HTML;
    }

    private function getMvpContent(): string
    {
        return <<<'HTML'
<article class="blog-content">
    <p class="lead">In the age of feature bloat, there's something refreshing about software that just works. ScreenBuddy is built on a simple philosophy: do one thing, do it well, and get out of your way.</p>

    <h2>The Problem with "Full-Featured" Software</h2>
    <p>Have you ever opened a software application and felt overwhelmed by the sheer number of buttons, menus, and options? You're not alone. Feature creep is real, and it's killing productivity.</p>

    <p>Consider this: studies show that most users only use 20% of any software's features. The other 80%? It's just clutter that:</p>
    <ul>
        <li>Slows down the interface</li>
        <li>Creates decision fatigue</li>
        <li>Increases the learning curve</li>
        <li>Adds bugs and security vulnerabilities</li>
    </ul>

    <h2>The MVP Mindset</h2>
    <p>MVP stands for "Minimum Viable Product," but we prefer to think of it as "Maximum Value Product." By focusing ruthlessly on core functionality, we deliver:</p>

    <h3>Speed</h3>
    <p>ScreenBuddy loads instantly. No waiting for bloated JavaScript bundles or unnecessary animations. Click, record, done.</p>

    <h3>Reliability</h3>
    <p>Fewer features mean fewer things that can break. Our simple architecture means your recordings work, every time.</p>

    <h3>Ease of Use</h3>
    <p>No tutorials needed. No certification courses. If you can click a button, you can use ScreenBuddy.</p>

    <h2>What We Chose to Build</h2>
    <ul>
        <li><strong>One-click recording</strong> - Start capturing your screen instantly</li>
        <li><strong>Instant sharing</strong> - Get a link the moment you stop recording</li>
        <li><strong>High-quality video</strong> - Up to 4K resolution, because quality matters</li>
        <li><strong>Audio support</strong> - Record your microphone alongside your screen</li>
    </ul>

    <h2>What We Chose NOT to Build</h2>
    <ul>
        <li>Complicated editing suites</li>
        <li>AI transcription (yet)</li>
        <li>Integrations with every tool under the sun</li>
        <li>Features that 95% of users would never touch</li>
    </ul>

    <h2>The Result?</h2>
    <p>A screen recording tool that:</p>
    <ul>
        <li>Works in your browser - no downloads</li>
        <li>Takes 5 seconds to learn</li>
        <li>Costs less than your daily coffee</li>
        <li>Actually gets used by the people who pay for it</li>
    </ul>

    <h2>Simple Doesn't Mean Inferior</h2>
    <p>There's a misconception that simple software is "basic" or lacking. The truth? Building simple software is harder than building complex software. It requires discipline to say no to features, and courage to ship something focused.</p>

    <blockquote>
        <p>"Perfection is achieved not when there is nothing more to add, but when there is nothing left to take away."</p>
        <cite>- Antoine de Saint-Exupéry</cite>
    </blockquote>

    <h2>Try Simple</h2>
    <p>If you're tired of software that tries to do everything and does nothing well, give ScreenBuddy a try. Experience what screen recording feels like when it's designed around your workflow, not around a feature checklist.</p>

    <p><strong>Sign up free</strong> and record your first video in under 30 seconds. We promise it's that easy.</p>
</article>
HTML;
    }
}
