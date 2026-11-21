/* Libraria — Admin UI interactions.
   Fully offline. Enhancements only: the page works without this file. */
(function () {
    'use strict';

    var body = document.body;
    if (!body || !body.classList.contains('admin-page')) return;

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Theme switcher ---------- */
    var THEME_KEY = 'libraria-admin-theme';
    var themes = ['coffee', 'midnight', 'emerald', 'violet'];

    function applyTheme(theme) {
        if (themes.indexOf(theme) === -1) theme = 'coffee';
        body.setAttribute('data-theme', theme);
        document.querySelectorAll('.theme-dot').forEach(function (dot) {
            var active = dot.getAttribute('data-theme') === theme;
            dot.classList.toggle('active', active);
            dot.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    var saved = 'coffee';
    try { saved = window.localStorage.getItem(THEME_KEY) || 'coffee'; } catch (e) {}
    applyTheme(saved);

    document.querySelectorAll('.theme-dot').forEach(function (dot) {
        dot.addEventListener('click', function () {
            var theme = dot.getAttribute('data-theme');
            applyTheme(theme);
            try { window.localStorage.setItem(THEME_KEY, theme); } catch (e) {}
        });
    });

    /* ---------- Live clock (Asia/Jakarta) ---------- */
    var clock = document.getElementById('adminClock');
    if (clock) {
        var slot = clock.querySelector('span');
        var formatter;
        try {
            formatter = new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Jakarta',
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
            });
        } catch (e) {
            formatter = null;
        }

        function tick() {
            if (!slot) return;
            slot.textContent = formatter ? formatter.format(new Date()) : new Date().toLocaleTimeString('id-ID');
        }
        tick();
        window.setInterval(tick, 1000);
    }

    /* ---------- Count-up for numeric stat values ---------- */
    if (!reduceMotion) {
        document.querySelectorAll('[data-count]').forEach(function (el) {
            var target = Number(el.getAttribute('data-count'));
            if (!isFinite(target)) return;

            var text = el.textContent.trim();
            var decimals = text.indexOf(',') !== -1 ? text.split(',')[1].length : 0;
            var started = false;

            function run() {
                if (started) return;
                started = true;
                var duration = 620;
                var begin = performance.now();

                function frame(now) {
                    var progress = Math.min(1, (now - begin) / duration);
                    var eased = 1 - Math.pow(1 - progress, 3);
                    var current = target * eased;
                    el.textContent = current.toLocaleString('id-ID', {
                        minimumFractionDigits: decimals,
                        maximumFractionDigits: decimals
                    });
                    if (progress < 1) {
                        requestAnimationFrame(frame);
                    } else {
                        el.textContent = text;
                    }
                }
                requestAnimationFrame(frame);
            }

            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) { run(); observer.disconnect(); }
                    });
                }, { threshold: .2 });
                observer.observe(el);
            } else {
                run();
            }
        });
    }
})();
