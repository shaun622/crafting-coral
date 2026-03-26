<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    // Dashboard view
    $page_title = 'Your Teaching Pack — ' . SITE_NAME;
    require_once __DIR__ . '/includes/header.php';
    ?>

    <main class="dashboard">
        <section class="dashboard-hero">
            <div class="container">
                <h1>Your Teaching Pack</h1>
                <p>Everything you need to run a Crafting Coral workshop. Materials are updated periodically — you'll always have the latest versions here.</p>
            </div>
        </section>

        <section class="materials">
            <div class="container">
                <div class="materials-grid">
                    <div class="material-card">
                        <div class="material-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                        </div>
                        <h3>Display Graphics</h3>
                        <p>Infographics, statistics, images and a workshop summary for your classroom.</p>
                        <a href="/download.php?file=infographics" class="btn btn-secondary">Download Pack</a>
                    </div>

                    <div class="material-card">
                        <div class="material-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        </div>
                        <h3>Video Tutorial</h3>
                        <p>Theory and practical guidance to help you deliver the workshop with confidence.</p>
                        <a href="/download.php?file=video" class="btn btn-secondary">Download Video</a>
                    </div>

                    <div class="material-card">
                        <div class="material-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
                        </div>
                        <h3>Presentation Deck</h3>
                        <p>Ready-to-use classroom slides you can present or adapt to your needs.</p>
                        <a href="/download.php?file=presentation" class="btn btn-secondary">Download Slides</a>
                    </div>

                    <div class="material-card">
                        <div class="material-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        </div>
                        <h3>Teaching Module</h3>
                        <p>Structured lesson content with learning objectives, activities and assessment ideas.</p>
                        <a href="/download.php?file=module" class="btn btn-secondary">Download Module</a>
                    </div>

                    <div class="material-card">
                        <div class="material-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/></svg>
                        </div>
                        <h3>360&deg; Video</h3>
                        <p>Take your students on a virtual visit to our coral restoration site.</p>
                        <a href="/download.php?file=360-video" class="btn btn-secondary">Download Video</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once __DIR__ . '/includes/footer.php';
} else {
    // Sales page view
    $page_title = 'Bring Coral Reef Conservation Into Your Classroom — ' . SITE_NAME;
    require_once __DIR__ . '/includes/header.php';
    ?>

    <main class="sales">
        <section class="hero">
            <div class="hero-overlay"></div>
            <div class="container hero-content">
                <h1>Bring Coral Reef Conservation Into Your Classroom</h1>
                <p class="hero-sub">A hands-on teaching pack for educators who want to raise the next generation of ocean stewards.</p>
                <a href="/stripe-checkout.php" class="btn btn-primary btn-lg">Get the Teaching Pack &mdash; &pound;100</a>
                <p class="hero-note">One-time payment. Lifetime access. Updated materials included.</p>
            </div>
        </section>

        <section class="whats-inside">
            <div class="container">
                <h2>What's Inside the Pack</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                        </div>
                        <h3>Display Graphics</h3>
                        <p>Infographics, statistics, images and a workshop summary.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        </div>
                        <h3>Video Tutorial</h3>
                        <p>Theory and practical guidance for delivering the workshop.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
                        </div>
                        <h3>Presentation Deck</h3>
                        <p>Ready-to-use classroom slides you can present or adapt.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        </div>
                        <h3>Teaching Module</h3>
                        <p>Structured lesson content with objectives and activities.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/></svg>
                        </div>
                        <h3>360&deg; Video</h3>
                        <p>A virtual visit to our coral restoration site.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="why-matters">
            <div class="container">
                <div class="why-content">
                    <h2>Why This Matters</h2>
                    <p>Over 90% of coral reefs could be lost by 2060. But education is the first step to change. This pack gives you everything you need to run a Crafting Coral workshop at your school — no marine biology degree required.</p>
                </div>
            </div>
        </section>

        <section class="who-for">
            <div class="container">
                <h2>Who It's For</h2>
                <p>Teachers, educators, and youth leaders around the world who want to bring ocean conservation into their classrooms through creativity and hands-on learning.</p>
            </div>
        </section>

        <section class="pricing">
            <div class="container">
                <div class="pricing-card">
                    <span class="pricing-label">Teaching Pack</span>
                    <div class="pricing-amount">&pound;100</div>
                    <p class="pricing-detail">Lifetime Access</p>
                    <ul class="pricing-features">
                        <li>One-time purchase — no subscriptions</li>
                        <li>All five resources included</li>
                        <li>Future updates included free</li>
                        <li>Instant access after payment</li>
                    </ul>
                    <a href="/stripe-checkout.php" class="btn btn-primary btn-lg">Get the Teaching Pack</a>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once __DIR__ . '/includes/footer.php';
}
