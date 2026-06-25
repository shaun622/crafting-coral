    <footer class="footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <img src="/assets/logo.svg" alt="Crafting Coral" height="30">
                <p>Conservation through creativity.</p>
            </div>
            <div class="footer-links">
                <a href="<?= MAIN_SITE_URL ?>">Main Site</a>
                <a href="https://www.instagram.com/craftingcoral/" target="_blank" rel="noopener">Instagram</a>
                <a href="mailto:hello@craftingcoral.com">Contact</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Crafting Coral. All rights reserved.</p>
        </div>
    </footer>

    <script>
    // Show/hide password toggle (delegated — works for any .password-toggle)
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.password-toggle');
        if (!btn) return;
        var wrap = btn.closest('.password-wrap');
        var input = wrap && wrap.querySelector('input');
        if (!input) return;
        var reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';
        wrap.classList.toggle('is-revealed', reveal);
        btn.setAttribute('aria-pressed', reveal ? 'true' : 'false');
        btn.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
    });
    </script>
</body>
</html>
