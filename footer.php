</main>

<footer class="site-footer">
    <div class="container d-flex flex-wrap justify-content-between gap-2">
        <span>Kolehiyo ng Lungsod ng Dasmariñas &middot; Student Council Election 2026</span>
        <span>
            <a href="candidates.php">Candidates</a> &nbsp;|&nbsp;
            <?php if (results_visible()): ?><a href="results.php">Results</a> &nbsp;|&nbsp;<?php endif; ?>
            <a href="faq.php">Help</a> &nbsp;|&nbsp; <a href="about.php">About</a> &nbsp;|&nbsp; <a href="privacy.php">Data privacy</a>
        </span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Show/hide button on every password field ror
document.querySelectorAll('input[type=password]').forEach(function (inp) {
    var wrap = document.createElement('div');
    wrap.className = 'pw-wrap';
    inp.parentNode.insertBefore(wrap, inp);
    wrap.appendChild(inp);
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'pw-toggle';
    b.textContent = 'Show';
    b.setAttribute('aria-label', 'Show password');
    b.addEventListener('click', function () {
        var hidden = inp.type === 'password';
        inp.type = hidden ? 'text' : 'password';
        b.textContent = hidden ? 'Hide' : 'Show';
    });
    wrap.appendChild(b);
});

// Countdowns (data-countdown = unix time in seconds)
document.querySelectorAll('[data-countdown]').forEach(function (el) {
    var target = parseInt(el.dataset.countdown, 10) * 1000;
    var startedPositive = target - Date.now() > 0;
    function tick() {
        var d = target - Date.now();
        if (d <= 0) {
            el.textContent = 'any moment now';
            if (startedPositive) { startedPositive = false; setTimeout(function () { location.reload(); }, 1500); }
            return;
        }
        var s = Math.floor(d / 1000), days = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600),
            m = Math.floor(s % 3600 / 60), sec = s % 60;
        el.textContent = (days ? days + 'd ' : '') + h + 'h ' + m + 'm ' + sec + 's';
    }
    tick();
    setInterval(tick, 1000);
});
</script>
</body>
</html>
