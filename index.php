<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$content_settings = get_content_settings();

// SVG icons per slot
$slot_icons = [
    'infographics' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',
    'video' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>',
    'presentation' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>',
    'module' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
    '360-video' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/></svg>',
];

$slot_icons_sm = [
    'infographics' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',
    'video' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>',
    'presentation' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>',
    'module' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
    '360-video' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/></svg>',
];

// Scan content directories for file lists
$slot_file_list = [];
$valid_slots = ['infographics', 'video', 'presentation', 'module', '360-video'];
foreach ($valid_slots as $s) {
    $dir = __DIR__ . '/content/' . $s;
    $slot_file_list[$s] = [];
    if (is_dir($dir)) {
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..' || $f === '.htaccess') continue;
            if (is_file($dir . '/' . $f)) {
                $slot_file_list[$s][] = $f;
            }
        }
    }
}

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

        <section class="dashboard-intro">
            <div class="container">
                <p>This guide provides all the necessary materials and instructions to support the delivery of a Crafting Coral ceramic workshop. It includes teaching guidance, logistical considerations, and step-by-step facilitation support to ensure a smooth and engaging session. The workshop is designed to run for approximately 90–120 minutes, including a 30–40 minute presentation, followed by a hands-on creative activity. This format allows participants to learn about coral reefs while actively creating their own CoralPod.</p>
            </div>
        </section>

        <section class="materials">
            <div class="container">
                <div class="materials-grid">
                    <?php foreach ($content_settings as $slot => $setting): ?>
                        <?php if (!$setting['visible']) continue; ?>
                        <?php $files = $slot_file_list[$slot] ?? []; ?>
                        <div class="material-card">
                            <div class="material-icon">
                                <?= $slot_icons[$slot] ?? '' ?>
                            </div>
                            <h3><?= htmlspecialchars($setting['title']) ?></h3>
                            <p><?= htmlspecialchars($setting['description']) ?></p>
                            <?php if (!empty($setting['url'])): ?>
                                <a href="<?= htmlspecialchars($setting['url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary"><?= htmlspecialchars($setting['btn_label']) ?></a>
                            <?php elseif (count($files) === 0): ?>
                                <span class="btn btn-secondary" style="opacity: 0.5; cursor: default;">Coming Soon</span>
                            <?php elseif (count($files) === 1): ?>
                                <a href="/download.php?file=<?= htmlspecialchars($slot) ?>" class="btn btn-secondary"><?= htmlspecialchars($setting['btn_label']) ?></a>
                            <?php else: ?>
                                <div class="material-files">
                                    <?php foreach ($files as $fname): ?>
                                        <a href="/download.php?file=<?= htmlspecialchars($slot) ?>&amp;name=<?= urlencode($fname) ?>" class="material-file-link">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                            <?= htmlspecialchars($fname) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="teaching-gallery">
            <div class="container">
                <h2>The Workshop in Action</h2>
                <div class="teaching-gallery-grid">
                    <figure><img src="/assets/images/gallery/workshop-1.webp" alt="Crafting Coral workshop at Rumah Hujan" loading="lazy"></figure>
                    <figure><img src="/assets/images/gallery/workshop-2.webp" alt="Students working on coral pods" loading="lazy"></figure>
                    <figure><img src="/assets/images/gallery/workshop-3.webp" alt="Hands-on ceramic workshop" loading="lazy"></figure>
                    <figure><img src="/assets/images/gallery/workshop-4.webp" alt="Workshop participants creating coral" loading="lazy"></figure>
                    <figure class="teaching-gallery-wide"><img src="/assets/images/gallery/workshop-5.webp" alt="Crafting Coral Workshop by Sara Howard" loading="lazy"></figure>
                    <figure><img src="/assets/images/gallery/workshop-6.webp" alt="Workshop session in progress" loading="lazy"></figure>
                    <figure><img src="/assets/images/gallery/workshop-7.webp" alt="Finished coral pod creations" loading="lazy"></figure>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once __DIR__ . '/includes/footer.php';
} else {
    // Sales page view
    $page_title = 'Bring Coral Reef Conservation Into Your Classroom — ' . SITE_NAME;
    $price = get_current_price();
    require_once __DIR__ . '/includes/header.php';
    ?>

    <main class="sales">
        <section class="hero">
            <div class="hero-overlay"></div>
            <div class="container hero-content">
                <?php if ($price['is_launch']): ?>
                    <div class="launch-badge">
                        <span class="launch-dot"></span>
                        Launch offer — <?= $price['spots_left'] ?> of <?= $price['spots_total'] ?> spots left at <?= $price['display'] ?>
                    </div>
                <?php endif; ?>
                <h1>Bring Coral Reef Conservation Into Your Classroom</h1>
                <p class="hero-sub">A hands-on teaching pack for educators who want to raise the next generation of ocean stewards.</p>
                <a href="/stripe-checkout.php" class="btn btn-primary btn-lg">
                    <?php if ($price['is_launch']): ?>
                        Get the Teaching Pack &mdash; <span class="price-old"><?= $price['regular_display'] ?></span> <?= $price['display'] ?>
                    <?php else: ?>
                        Get the Teaching Pack &mdash; <?= $price['display'] ?>
                    <?php endif; ?>
                </a>
                <p class="hero-note">One-time payment. Lifetime access. Updated materials included.</p>
            </div>
        </section>

        <section class="preview-section">
            <div class="container">
                <h2>Your Members Dashboard</h2>
                <p class="preview-subtitle">Here's what you'll get instant access to after purchase</p>

                <div class="workshop-info">
                    <div class="workshop-info-block">
                        <h3>About This Workshop</h3>
                        <p>This Teaching Pack supports the delivery of the Crafting Coral ceramic workshop, a hands-on session combining environmental science, ceramics art, and ocean awareness for youth. Each module is designed to be self-contained and can be delivered in one school session (approximately 60–90 minutes).</p>
                    </div>
                    <div class="workshop-info-block">
                        <h3>Learning Objectives</h3>
                        <p>By the end of the session, students will be able to:</p>
                        <ul>
                            <li>Explain what coral is and why it is classified as an animal</li>
                            <li>Describe the relationship between coral and zooxanthellae algae</li>
                            <li>Identify at least two threats to coral reefs</li>
                            <li>Understand what coral bleaching is and why it happens</li>
                            <li>Explain why ceramic pods are used in reef restoration</li>
                            <li>Successfully complete a 5-step ceramic CoralPod</li>
                        </ul>
                    </div>
                </div>

                <div class="session-structure">
                    <h3>Session Structure</h3>
                    <div class="session-timeline">
                        <div class="session-step">
                            <span class="session-time">5 min</span>
                            <div class="session-detail">
                                <span class="session-section">Section 1</span>
                                <span class="session-activity">Welcome &amp; Today's Adventure</span>
                            </div>
                        </div>
                        <div class="session-step">
                            <span class="session-time">10 min</span>
                            <div class="session-detail">
                                <span class="session-section">Section 2</span>
                                <span class="session-activity">What is Coral? (Quiz + Explanation)</span>
                            </div>
                        </div>
                        <div class="session-step">
                            <span class="session-time">10 min</span>
                            <div class="session-detail">
                                <span class="session-section">Section 3</span>
                                <span class="session-activity">Why Reefs Matter</span>
                            </div>
                        </div>
                        <div class="session-step">
                            <span class="session-time">10 min</span>
                            <div class="session-detail">
                                <span class="session-section">Section 4</span>
                                <span class="session-activity">Threats, Bleaching &amp; Restoration</span>
                            </div>
                        </div>
                        <div class="session-step">
                            <span class="session-time">5 min</span>
                            <div class="session-detail">
                                <span class="session-section">Section 5</span>
                                <span class="session-activity">What Can We Do? + Ceramics Intro</span>
                            </div>
                        </div>
                        <div class="session-step session-step-highlight">
                            <span class="session-time">30–40 min</span>
                            <div class="session-detail">
                                <span class="session-section">Section 6</span>
                                <span class="session-activity">Make Your CoralPod (5 Steps)</span>
                            </div>
                        </div>
                        <div class="session-step">
                            <span class="session-time">5 min</span>
                            <div class="session-detail">
                                <span class="session-section">Section 7</span>
                                <span class="session-activity">What Happens Next + Wrap Up</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="preview-wrapper">
                    <div class="preview-blur">
                        <div class="preview-header">
                            <h3>Your Teaching Pack</h3>
                            <p>Everything you need to run a Crafting Coral workshop.</p>
                        </div>
                        <div class="preview-grid">
                            <?php foreach ($content_settings as $slot => $setting): ?>
                                <?php if (!$setting['visible']) continue; ?>
                                <div class="preview-card">
                                    <div class="preview-card-icon">
                                        <?= $slot_icons_sm[$slot] ?? '' ?>
                                    </div>
                                    <h4><?= htmlspecialchars($setting['title']) ?></h4>
                                    <p><?= htmlspecialchars($setting['description']) ?></p>
                                    <span class="preview-btn"><?= htmlspecialchars($setting['btn_label']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="preview-overlay">
                        <div class="preview-overlay-content">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 16px; opacity: 0.9;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <h3>Unlock Your Teaching Pack</h3>
                            <p>Get instant access to all resources with a one-time payment</p>
                            <a href="/stripe-checkout.php" class="btn btn-primary btn-lg">
                                <?php if ($price['is_launch']): ?>
                                    Get Access &mdash; <span class="price-old"><?= $price['regular_display'] ?></span> <?= $price['display'] ?>
                                <?php else: ?>
                                    Get Access &mdash; <?= $price['display'] ?>
                                <?php endif; ?>
                            </a>
                        </div>
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

        <section class="gallery">
            <div class="container">
                <h2>The Workshop in Action</h2>
                <p class="gallery-subtitle">See how students around the world are learning about coral reefs through creativity and play.</p>
                <div class="gallery-grid">
                    <figure class="gallery-item">
                        <img src="/assets/images/coral-interaction-1024x1024.jpg" alt="Students crafting coral" loading="lazy">
                    </figure>
                    <figure class="gallery-item">
                        <img src="/assets/images/community-interaction-1024x1024.jpg" alt="Community workshop" loading="lazy">
                    </figure>
                    <figure class="gallery-item">
                        <img src="/assets/images/crativity-interaction-1024x1024.jpg" alt="Creative session" loading="lazy">
                    </figure>
                    <figure class="gallery-item gallery-item-wide">
                        <img src="/assets/images/crafting-coral-landing-video-overlay-scaled-1.jpg" alt="Crafting Coral workshop" loading="lazy">
                    </figure>
                    <figure class="gallery-item gallery-item-wide">
                        <img src="/assets/images/Crafting-Coral-home-get-involved-invert-1.jpg" alt="Get involved with Crafting Coral" loading="lazy">
                    </figure>
                </div>
            </div>
        </section>

        <section class="pricing">
            <div class="container">
                <div class="pricing-card<?= $price['is_launch'] ? ' pricing-card-launch' : '' ?>">
                    <?php if ($price['is_launch']): ?>
                        <span class="pricing-launch-tag">Launch offer &mdash; <?= $price['spots_left'] ?> of <?= $price['spots_total'] ?> spots left</span>
                    <?php endif; ?>
                    <span class="pricing-label">Teaching Pack</span>
                    <div class="pricing-amount">
                        <?php if ($price['is_launch']): ?>
                            <span class="price-old"><?= $price['regular_display'] ?></span> <?= $price['display'] ?>
                        <?php else: ?>
                            <?= $price['display'] ?>
                        <?php endif; ?>
                    </div>
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
