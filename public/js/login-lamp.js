/* Libraria — interactive reading lamp for the login library.
   The lamp only responds to a real downward cord pull (plus keyboard access).
   No dependencies, fully offline. */
(function () {
    'use strict';

    var room = document.getElementById('room');
    if (!room) return;

    var panel = document.getElementById('loginPanel');
    var cord = document.getElementById('lampCord');
    var cordTip = document.getElementById('cordTip');
    var status = document.getElementById('roomStatus');
    var STORAGE_KEY = 'libraria-lamp';
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var isOn = false;

    function readStored() {
        try { return sessionStorage.getItem(STORAGE_KEY) === 'on'; }
        catch (e) { return false; }
    }

    function writeStored(on) {
        try { sessionStorage.setItem(STORAGE_KEY, on ? 'on' : 'off'); }
        catch (e) { /* storage can be blocked; the lamp still works */ }
    }

    function focusEmail(delay) {
        var email = panel ? panel.querySelector('input[name="email"]') : null;
        if (!email) return;
        window.setTimeout(function () {
            try { email.focus({ preventScroll: true }); } catch (e) { email.focus(); }
        }, reduceMotion ? 40 : delay);
    }

    function applyState(on, options) {
        var opts = options || {};
        isOn = !!on;

        room.classList.toggle('is-on', isOn);
        room.classList.toggle('is-off', !isOn);

        if (cord) {
            cord.setAttribute('aria-pressed', isOn ? 'true' : 'false');
            cord.setAttribute('aria-label', isOn
                ? 'Tarik lagi tali lampu ke bawah untuk mematikan lampu'
                : 'Tarik tali lampu ke bawah untuk menyalakan lampu');
        }

        if (panel) {
            panel.setAttribute('aria-hidden', isOn ? 'false' : 'true');
            if (isOn) panel.removeAttribute('inert');
            else panel.setAttribute('inert', '');
        }

        if (cordTip) cordTip.classList.toggle('is-hidden', isOn);

        if (status) {
            status.textContent = isOn
                ? 'Lampu menyala. Formulir masuk sudah tersedia.'
                : 'Lampu mati. Tarik tali lampu ke bawah untuk menyalakan ruangan.';
        }

        writeStored(isOn);

        if (isOn && !opts.silentFocus) {
            focusEmail(opts.focusDelay || 660);
        }
    }

    function toggle() {
        applyState(!isOn);
    }

    if (cord) {
        var lamp = document.getElementById('lamp');
        var startX = null;
        var startY = null;
        var pointerId = null;
        var limit = 40;
        var threshold = 24;

        /* Rubber-band: the cord follows the finger 1:1 at first, then resists
           progressively instead of stopping at a hard cap. */
        function elastic(dy) {
            if (dy <= limit) return dy;
            var extra = dy - limit;
            return limit + Math.min(extra * 0.34, limit * 0.72);
        }

        function paint(pull, tilt) {
            if (!lamp) return;
            lamp.style.setProperty('--pull', pull.toFixed(1) + 'px');
            lamp.style.setProperty('--stretch', (1 + pull / 150).toFixed(3));
            lamp.style.setProperty('--tilt', tilt.toFixed(2) + 'deg');
        }

        function resetPaint() {
            if (lamp) {
                lamp.style.removeProperty('--pull');
                lamp.style.removeProperty('--stretch');
                lamp.style.removeProperty('--tilt');
            }
        }

        cord.addEventListener('pointerdown', function (event) {
            if (event.button !== undefined && event.button !== 0) return;
            startX = event.clientX;
            startY = event.clientY;
            pointerId = event.pointerId;

            var h = cord.clientHeight || 100;
            limit = Math.max(18, h * 0.42);
            threshold = Math.max(16, h * 0.26);

            cord.classList.add('is-pulling');
            if (lamp) lamp.classList.add('is-pulling');
            paint(0, 0);
            try { cord.setPointerCapture(event.pointerId); } catch (e) {}
        });

        cord.addEventListener('pointermove', function (event) {
            if (startY === null) return;
            var dy = Math.max(0, event.clientY - startY);
            var dx = event.clientX - startX;
            /* CSS rotate positif menggeser ujung tali ke kiri, jadi arahnya dibalik
               supaya tarikan ke kanan membuat bead ikut ke kanan. */
            var tilt = Math.max(-7, Math.min(7, dx * -0.16));
            paint(elastic(dy), tilt);
            if (dy > 4) event.preventDefault();
        });

        function endPull(event, cancelled) {
            if (startY === null) return;
            var endY = event && typeof event.clientY === 'number' ? event.clientY : startY;
            var dy = Math.max(0, endY - startY);
            startX = null;
            startY = null;

            if (pointerId !== null) {
                try { cord.releasePointerCapture(pointerId); } catch (e) {}
                pointerId = null;
            }

            cord.classList.remove('is-pulling');
            if (lamp) lamp.classList.remove('is-pulling');
            resetPaint();

            if (!cancelled && dy >= threshold) toggle();
        }

        cord.addEventListener('pointerup', function (event) {
            endPull(event, false);
        });
        cord.addEventListener('pointercancel', function (event) {
            endPull(event, true);
        });

        /* A plain click/tap is deliberately ignored: the cord must be pulled.
           Keyboard users still get an equivalent control on the cord itself. */
        cord.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar') {
                event.preventDefault();
                toggle();
            }
        });
    }

    var startOn = room.getAttribute('data-start-on') === '1';
    var restore = readStored();
    var startLit = startOn || restore;

    if (panel) {
        panel.setAttribute('aria-hidden', startLit ? 'false' : 'true');
        if (startLit) panel.removeAttribute('inert');
        else panel.setAttribute('inert', '');
    }

    if (startLit) {
        room.classList.add('no-anim');
        applyState(true, {
            silentFocus: true,
            focusDelay: restore ? 140 : 700
        });
        function finishRestore() { room.classList.remove('no-anim'); }
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(finishRestore);
        });
        window.setTimeout(finishRestore, 400);
        focusEmail(restore ? 180 : 760);
    } else {
        applyState(false, { silentFocus: true });
    }
})();
