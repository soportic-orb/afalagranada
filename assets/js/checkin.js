(function () {
    'use strict';
    var cfg = window.CHECKIN;
    var csrf = window.AFA.csrf;
    var result = document.getElementById('ci-result');
    var history = document.getElementById('ci-history');
    var activity = document.getElementById('ci-activity');
    var startBtn = document.getElementById('ci-start');
    var stopBtn = document.getElementById('ci-stop');
    var scanner = null;
    var busy = false;
    var lastCode = '', lastTime = 0;

    try { activity.value = localStorage.getItem('afa-ci-activity') || '0'; } catch (e) {}
    activity.addEventListener('change', function () { try { localStorage.setItem('afa-ci-activity', activity.value); } catch (e) {} });

    function beep(ok) {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var o = ctx.createOscillator(); var g = ctx.createGain();
            o.frequency.value = ok ? 880 : 220; o.connect(g); g.connect(ctx.destination);
            g.gain.value = 0.15; o.start(); setTimeout(function () { o.stop(); ctx.close(); }, ok ? 150 : 400);
        } catch (e) {}
        if (navigator.vibrate) navigator.vibrate(ok ? 80 : [100, 60, 100]);
    }

    function show(d) {
        result.hidden = false;
        result.className = 'ci-result ' + d.level;
        result.innerHTML = '';
        var s = document.createElement('strong'); s.textContent = d.message; result.appendChild(s);
        if (d.attendee) {
            var p = document.createElement('div');
            p.textContent = d.attendee + (d.course ? ' (' + d.course + ')' : '') + ' — ' + d.activity;
            result.appendChild(p);
        }
        var li = document.createElement('li');
        li.textContent = new Date().toLocaleTimeString('ca') + ' · ' + (d.ok ? '✅ ' : (d.level === 'warn' ? '⚠️ ' : '❌ ')) + (d.attendee || '') + ' — ' + d.message;
        history.insertBefore(li, history.firstChild);
        beep(d.ok);
    }

    function validate(code) {
        if (busy) return;
        var now = Date.now();
        if (code === lastCode && now - lastTime < 3000) return;
        lastCode = code; lastTime = now; busy = true;
        var fd = new FormData();
        fd.append('code', code); fd.append('activity_id', activity.value); fd.append('_csrf', csrf);
        fetch(cfg.url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(show)
            .catch(function () { show({ ok: false, level: 'bad', message: 'Error de connexió' }); })
            .finally(function () { setTimeout(function () { busy = false; }, 1200); });
    }

    startBtn.addEventListener('click', function () {
        if (!window.Html5Qrcode) { alert('No s\'ha pogut carregar el lector QR.'); return; }
        scanner = scanner || new Html5Qrcode('reader');
        scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 250, height: 250 } }, validate)
            .then(function () { startBtn.hidden = true; stopBtn.hidden = false; })
            .catch(function (err) { alert('No es pot accedir a la càmera: ' + err); });
    });
    stopBtn.addEventListener('click', function () {
        if (scanner) scanner.stop().then(function () { startBtn.hidden = false; stopBtn.hidden = true; });
    });
    document.getElementById('ci-manual').addEventListener('submit', function (e) {
        e.preventDefault();
        var input = document.getElementById('ci-code');
        if (input.value.trim()) { lastCode = ''; validate(input.value.trim()); input.value = ''; }
    });
})();
