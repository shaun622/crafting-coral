<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

session_start();

// --- Auth ---
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Login
    if ($_POST['action'] === 'login') {
        if (($_POST['password'] ?? '') === ADMIN_PASSWORD) {
            $_SESSION['is_admin'] = true;
            header('Location: /admin.php');
            exit;
        }
        $login_error = true;
    }

    // Logout
    if ($_POST['action'] === 'logout' && $is_admin) {
        unset($_SESSION['is_admin']);
        header('Location: /admin.php');
        exit;
    }

    // Add member manually
    if ($_POST['action'] === 'add_member' && $is_admin) {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            create_member($email, 'manual', 'manual');
            $msg = 'Member added: ' . htmlspecialchars($email);
        } else {
            $msg_error = 'Invalid email address.';
        }
    }

    // Delete member
    if ($_POST['action'] === 'delete_member' && $is_admin) {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!empty($email)) {
            $db = get_db();
            $stmt = $db->prepare('DELETE FROM members WHERE email = :email');
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->execute();
            $msg = 'Member removed: ' . htmlspecialchars($email);
        }
    }

    // Update content settings
    if ($_POST['action'] === 'update_settings' && $is_admin) {
        $slots = $_POST['slot'] ?? [];
        $titles = $_POST['title'] ?? [];
        $descriptions = $_POST['description'] ?? [];
        $btn_labels = $_POST['btn_label'] ?? [];
        $visible_slots = $_POST['visible'] ?? [];

        foreach ($slots as $i => $slot) {
            $vis = in_array($slot, $visible_slots) ? 1 : 0;
            update_content_setting(
                $slot,
                $titles[$i] ?? '',
                $descriptions[$i] ?? '',
                $btn_labels[$i] ?? 'Download',
                $vis
            );
        }
        $msg = 'Content settings saved successfully.';
    }

    // Upload file
    if ($_POST['action'] === 'upload' && $is_admin && isset($_FILES['file'])) {
        $slot = $_POST['slot'] ?? '';
        $targets = [
            'module' => 'content/module.pdf',
            'presentation' => 'content/presentation.pptx',
            'video' => 'content/video-tutorial.mp4',
            'infographics' => 'content/infographics/infographics-pack.zip',
            '360-video' => 'content/360-video/video.mp4',
        ];

        if (isset($targets[$slot]) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $dest = __DIR__ . '/' . $targets[$slot];
            $dir = dirname($dest);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                $msg = 'File uploaded: ' . $targets[$slot];
            } else {
                $msg_error = 'Upload failed. Check file permissions.';
            }
        } else {
            $msg_error = 'Invalid upload.';
        }
    }
}

// --- Get data ---
if ($is_admin) {
    $db = get_db();
    $results = $db->query('SELECT email, stripe_customer_id, paid_at, created_at FROM members ORDER BY created_at DESC');
    $members = [];
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $members[] = $row;
    }

    // Check which content files exist
    $content_files = [
        'module' => ['path' => 'content/module.pdf', 'label' => 'Teaching Module (.pdf)'],
        'presentation' => ['path' => 'content/presentation.pptx', 'label' => 'Presentation Deck (.pptx)'],
        'video' => ['path' => 'content/video-tutorial.mp4', 'label' => 'Video Tutorial (.mp4)'],
        'infographics' => ['path' => 'content/infographics/infographics-pack.zip', 'label' => 'Infographics Pack (.zip)'],
        '360-video' => ['path' => 'content/360-video/video.mp4', 'label' => '360 Video (.mp4)'],
    ];
    foreach ($content_files as $key => &$file) {
        $full = __DIR__ . '/' . $file['path'];
        $file['exists'] = file_exists($full);
        $file['size'] = $file['exists'] ? filesize($full) : 0;
    }
    unset($file);

    // Content settings
    $content_settings = get_content_settings();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --deep: #0c3547;
            --primary: #42718f;
            --sand: #f5f0eb;
            --white: #ffffff;
            --text: #1a2a35;
            --text-light: #5a7080;
            --muted: #8a9baa;
            --success: #2e8b57;
            --danger: #c0392b;
            --radius: 8px;
        }
        body { font-family: 'Inter', -apple-system, sans-serif; font-size: 15px; line-height: 1.6; color: var(--text); background: var(--sand); }

        .admin-nav { background: var(--deep); padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; }
        .admin-nav h1 { color: var(--white); font-size: 16px; font-weight: 600; }
        .admin-nav-links { display: flex; gap: 16px; align-items: center; }
        .admin-nav a, .admin-nav button { color: rgba(255,255,255,0.7); font-size: 13px; text-decoration: none; background: none; border: none; cursor: pointer; font-family: inherit; }
        .admin-nav a:hover, .admin-nav button:hover { color: var(--white); }

        .container { max-width: 960px; margin: 0 auto; padding: 32px 24px; }

        .card { background: var(--white); border-radius: var(--radius); padding: 28px; margin-bottom: 24px; box-shadow: 0 2px 12px rgba(12,53,71,0.06); }
        .card h2 { font-size: 1.1rem; color: var(--deep); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .card h2 .badge { font-size: 12px; background: var(--deep); color: var(--white); padding: 2px 10px; border-radius: 20px; font-weight: 500; }

        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat { background: var(--white); border-radius: var(--radius); padding: 20px; text-align: center; box-shadow: 0 2px 12px rgba(12,53,71,0.06); }
        .stat-value { font-size: 2rem; font-weight: 700; color: var(--deep); }
        .stat-label { font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 10px 12px; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--sand); font-weight: 600; }
        td { padding: 10px 12px; border-bottom: 1px solid var(--sand); }
        tr:last-child td { border-bottom: none; }
        .tag { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 4px; font-weight: 500; }
        .tag-paid { background: #eef7f1; color: var(--success); }
        .tag-manual { background: #fef3e2; color: #b87333; }

        .btn { display: inline-block; padding: 8px 18px; border-radius: var(--radius); font-family: inherit; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; text-decoration: none; }
        .btn-primary { background: var(--primary); color: var(--white); }
        .btn-primary:hover { background: var(--deep); }
        .btn-danger { background: none; color: var(--danger); padding: 4px 8px; font-size: 12px; }
        .btn-danger:hover { background: #fde8e6; }
        .btn-sm { padding: 6px 14px; font-size: 12px; }

        .form-row { display: flex; gap: 10px; align-items: flex-end; }
        .form-row input[type="email"] { flex: 1; padding: 8px 14px; border: 2px solid #e8e0d8; border-radius: var(--radius); font-family: inherit; font-size: 14px; }
        .form-row input:focus { outline: none; border-color: var(--primary); }

        .file-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid var(--sand); gap: 12px; flex-wrap: wrap; }
        .file-row:last-child { border-bottom: none; }
        .file-info { flex: 1; min-width: 200px; }
        .file-name { font-weight: 600; font-size: 14px; color: var(--deep); }
        .file-status { font-size: 12px; color: var(--muted); margin-top: 2px; }
        .file-status.uploaded { color: var(--success); }
        .file-actions { display: flex; gap: 8px; align-items: center; }
        .file-actions input[type="file"] { font-size: 12px; max-width: 200px; }

        .alert { padding: 12px 16px; border-radius: var(--radius); font-size: 13px; margin-bottom: 20px; }
        .alert-success { background: #eef7f1; color: var(--success); }
        .alert-error { background: #fde8e6; color: var(--danger); }

        /* Login */
        .login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .login-card { max-width: 380px; width: 100%; background: var(--white); border-radius: var(--radius); padding: 40px 32px; box-shadow: 0 8px 40px rgba(12,53,71,0.12); text-align: center; }
        .login-card h1 { font-size: 1.3rem; color: var(--deep); margin-bottom: 4px; }
        .login-card p { color: var(--text-light); font-size: 14px; margin-bottom: 24px; }
        .login-card input[type="password"] { display: block; width: 100%; padding: 10px 14px; border: 2px solid #e8e0d8; border-radius: var(--radius); font-family: inherit; font-size: 14px; margin-bottom: 16px; text-align: center; }
        .login-card input:focus { outline: none; border-color: var(--primary); }
        .login-card .btn { width: 100%; padding: 10px; }
        .login-error { color: var(--danger); font-size: 13px; margin-bottom: 12px; }

        @media (max-width: 640px) {
            .form-row { flex-direction: column; }
            .file-row { flex-direction: column; align-items: flex-start; }
            .stats { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<?php if (!$is_admin): ?>
    <div class="login-wrap">
        <div class="login-card">
            <h1>Admin Access</h1>
            <p>Crafting Coral course management</p>
            <?php if (!empty($login_error)): ?>
                <p class="login-error">Incorrect password.</p>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <input type="password" name="password" placeholder="Password" autofocus>
                <button type="submit" class="btn btn-primary">Sign In</button>
            </form>
        </div>
    </div>

<?php else: ?>
    <nav class="admin-nav">
        <h1>Crafting Coral Admin</h1>
        <div class="admin-nav-links">
            <a href="<?= SITE_URL ?>" target="_blank">View Site</a>
            <form method="POST" style="display:inline;"><input type="hidden" name="action" value="logout"><button type="submit">Log Out</button></form>
        </div>
    </nav>

    <div class="container">
        <?php if (!empty($msg)): ?>
            <div class="alert alert-success"><?= $msg ?></div>
        <?php endif; ?>
        <?php if (!empty($msg_error)): ?>
            <div class="alert alert-error"><?= $msg_error ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats">
            <div class="stat">
                <div class="stat-value"><?= count($members) ?></div>
                <div class="stat-label">Total Members</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?= count(array_filter($members, fn($m) => $m['stripe_customer_id'] !== 'test_customer' && $m['stripe_customer_id'] !== 'manual')) ?></div>
                <div class="stat-label">Paid Members</div>
            </div>
            <div class="stat">
                <div class="stat-value">&pound;<?= count(array_filter($members, fn($m) => $m['stripe_customer_id'] !== 'test_customer' && $m['stripe_customer_id'] !== 'manual')) * 100 ?></div>
                <div class="stat-label">Revenue</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?= count(array_filter($content_files, fn($f) => $f['exists'])) ?>/<?= count($content_files) ?></div>
                <div class="stat-label">Files Uploaded</div>
            </div>
        </div>

        <!-- Members -->
        <div class="card">
            <h2>Members <span class="badge"><?= count($members) ?></span></h2>

            <form method="POST" class="form-row" style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="add_member">
                <input type="email" name="email" placeholder="Add member manually (email)" required>
                <button type="submit" class="btn btn-primary btn-sm">Add Member</button>
            </form>

            <?php if (empty($members)): ?>
                <p style="color: var(--muted); font-size: 14px;">No members yet.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['email']) ?></td>
                                    <td>
                                        <?php if ($m['stripe_customer_id'] === 'test_customer' || $m['stripe_customer_id'] === 'manual'): ?>
                                            <span class="tag tag-manual">Manual</span>
                                        <?php else: ?>
                                            <span class="tag tag-paid">Paid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: var(--muted); font-size: 13px;"><?= date('j M Y', strtotime($m['paid_at'])) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Remove <?= htmlspecialchars($m['email']) ?>?');">
                                            <input type="hidden" name="action" value="delete_member">
                                            <input type="hidden" name="email" value="<?= htmlspecialchars($m['email']) ?>">
                                            <button type="submit" class="btn btn-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Content Settings -->
        <div class="card">
            <h2>Teaching Materials — Settings</h2>
            <p style="color: var(--text-light); font-size: 13px; margin-bottom: 20px;">Edit titles, descriptions and visibility for each resource. Changes appear on the site immediately after saving.</p>

            <form method="POST" id="settingsForm">
                <input type="hidden" name="action" value="update_settings">

                <?php foreach ($content_settings as $slot => $setting): ?>
                    <div class="setting-row" style="padding: 20px 0; border-bottom: 1px solid var(--sand);">
                        <input type="hidden" name="slot[]" value="<?= htmlspecialchars($slot) ?>">

                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="font-weight: 600; font-size: 14px; color: var(--deep);"><?= htmlspecialchars($slot) ?></span>
                                <?php if (isset($content_files[$slot]) && $content_files[$slot]['exists']): ?>
                                    <span class="tag tag-paid" style="font-size: 10px;">File uploaded</span>
                                <?php else: ?>
                                    <span class="tag" style="font-size: 10px; background: #fde8e6; color: var(--danger);">No file</span>
                                <?php endif; ?>
                            </div>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; color: var(--text-light);">
                                <input type="checkbox" name="visible[]" value="<?= htmlspecialchars($slot) ?>" <?= $setting['visible'] ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer;">
                                Visible
                            </label>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 8px;">
                            <div>
                                <label style="display: block; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Title</label>
                                <input type="text" name="title[]" value="<?= htmlspecialchars($setting['title']) ?>" style="width: 100%; padding: 8px 12px; border: 2px solid #e8e0d8; border-radius: 6px; font-family: inherit; font-size: 14px;" required>
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Button Label</label>
                                <input type="text" name="btn_label[]" value="<?= htmlspecialchars($setting['btn_label']) ?>" style="width: 100%; padding: 8px 12px; border: 2px solid #e8e0d8; border-radius: 6px; font-family: inherit; font-size: 14px;" required>
                            </div>
                        </div>

                        <div>
                            <label style="display: block; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Description</label>
                            <textarea name="description[]" rows="2" style="width: 100%; padding: 8px 12px; border: 2px solid #e8e0d8; border-radius: 6px; font-family: inherit; font-size: 14px; resize: vertical;"><?= htmlspecialchars($setting['description']) ?></textarea>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div style="padding-top: 20px; display: flex; align-items: center; gap: 12px;">
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save All Changes</button>
                    <span id="saveConfirm" style="font-size: 13px; color: var(--success); display: none;">Changes saved!</span>
                </div>
            </form>
        </div>

        <!-- File Uploads -->
        <div class="card">
            <h2>Upload Files</h2>
            <p style="color: var(--text-light); font-size: 13px; margin-bottom: 20px;">Upload or replace the content files that members can download.</p>

            <?php foreach ($content_files as $key => $file): ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="file-row">
                        <div class="file-info">
                            <div class="file-name"><?= $file['label'] ?></div>
                            <?php if ($file['exists']): ?>
                                <div class="file-status uploaded">Uploaded — <?= number_format($file['size'] / 1048576, 1) ?> MB</div>
                            <?php else: ?>
                                <div class="file-status">Not uploaded</div>
                            <?php endif; ?>
                        </div>
                        <div class="file-actions">
                            <input type="hidden" name="action" value="upload">
                            <input type="hidden" name="slot" value="<?= $key ?>">
                            <input type="file" name="file" required>
                            <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                        </div>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<script>
// Show confirmation after save
<?php if (!empty($msg) && strpos($msg, 'Content settings saved') !== false): ?>
document.addEventListener('DOMContentLoaded', function() {
    var confirm = document.getElementById('saveConfirm');
    if (confirm) {
        confirm.style.display = 'inline';
        setTimeout(function() { confirm.style.display = 'none'; }, 4000);
    }
    // Scroll to settings section
    var form = document.getElementById('settingsForm');
    if (form) form.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
<?php endif; ?>

// Warn on unsaved changes
(function() {
    var form = document.getElementById('settingsForm');
    if (!form) return;
    var changed = false;
    form.addEventListener('input', function() { changed = true; });
    form.addEventListener('submit', function() { changed = false; });
    window.addEventListener('beforeunload', function(e) {
        if (changed) { e.preventDefault(); e.returnValue = ''; }
    });
})();
</script>
</body>
</html>
