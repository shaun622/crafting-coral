<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <nav class="nav">
        <div class="nav-inner">
            <a href="<?= MAIN_SITE_URL ?>" class="nav-logo" aria-label="Crafting Coral">
                <img src="/assets/logo.svg" alt="Crafting Coral" height="40">
            </a>
            <div class="nav-links">
                <?php if (is_logged_in()): ?>
                    <span class="nav-email"><?= htmlspecialchars(get_member_email()) ?></span>
                    <a href="/logout.php" class="nav-link">Log out</a>
                <?php else: ?>
                    <a href="/login.php" class="nav-link">Already a member? Log in</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
