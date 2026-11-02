(function () {
    'use strict';
    // Menú mòbil
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.getElementById('nav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Donacions: import personalitzat
    var donate = document.querySelector('.donate-form');
    if (donate) {
        var other = donate.querySelector('.other-amount');
        var sync = function () {
            var sel = donate.querySelector('input[name="amount"]:checked');
            var show = sel && sel.value === 'other';
            other.classList.toggle('show', !!show);
            other.querySelector('input').required = !!show;
        };
        donate.querySelectorAll('input[name="amount"]').forEach(function (r) { r.addEventListener('change', sync); });
        sync();
    }

    // Inscripcions: participants i total
    var reg = document.getElementById('reg-form');
    if (reg) {
        var list = reg.querySelector('.attendees');
        var addBtn = reg.querySelector('.add-attendee');
        var max = parseInt(reg.dataset.max || '1', 10);
        var price = parseInt(reg.dataset.price || '0', 10);
        var memberPrice = reg.dataset.memberPrice === '' ? null : parseInt(reg.dataset.memberPrice, 10);
        var memberBox = reg.querySelector('input[name="is_member"]');
        var totalEl = reg.querySelector('[data-total]');
        var fmt = function (c) {
            var v = c / 100;
            return (v % 1 === 0 ? v.toFixed(0) : v.toFixed(2)).replace('.', ',') + ' €';
        };
        var update = function () {
            var n = list.querySelectorAll('.attendee').length;
            var unit = (memberBox && memberBox.checked && memberPrice !== null) ? memberPrice : price;
            if (totalEl) totalEl.textContent = unit * n === 0 ? 'Gratuïta' : fmt(unit * n) + (n > 1 ? ' (' + n + ' × ' + fmt(unit) + ')' : '');
            if (addBtn) addBtn.hidden = n >= max;
        };
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var first = list.querySelector('.attendee');
                var clone = first.cloneNode(true);
                clone.querySelectorAll('input').forEach(function (i) { i.value = ''; });
                list.appendChild(clone);
                clone.querySelector('input').focus();
                update();
            });
        }
        list.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove') && list.querySelectorAll('.attendee').length > 1) {
                e.target.closest('.attendee').remove();
                update();
            }
        });
        if (memberBox) memberBox.addEventListener('change', update);
        update();
    }

    // Validació d'entrada des de la pàgina del tiquet (personal autoritzat)
    var ci = document.querySelector('[data-checkin]');
    if (ci) {
        ci.addEventListener('click', function () {
            ci.disabled = true;
            var body = new FormData();
            body.append('code', ci.dataset.checkin);
            body.append('_csrf', ci.dataset.csrf);
            fetch(ci.dataset.url, { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var out = document.querySelector('.checkin-result');
                    out.textContent = d.message;
                    out.style.color = d.level === 'ok' ? '#1a7f37' : (d.level === 'warn' ? '#b7791f' : '#c62828');
                    if (d.ok) ci.textContent = 'Validada ✓';
                })
                .catch(function () { ci.disabled = false; alert('Error de connexió'); });
        });
    }
})();

// Efecte confetti (pàgina de missatge enviat)
(function () {
    'use strict';
    var canvas = document.querySelector('canvas[data-confetti]');
    if (!canvas || !canvas.getContext) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var ctx = canvas.getContext('2d');
    var colors = ['#d9151c', '#078838', '#fbdc08', '#d6d7d7', '#ffffff'];
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var W, H, pieces = [], start = performance.now();

    function resize() {
        W = canvas.clientWidth; H = canvas.clientHeight;
        canvas.width = W * dpr; canvas.height = H * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    function burst(x, y, n, spread) {
        for (var i = 0; i < n; i++) {
            var a = (-Math.PI / 2) + (Math.random() - 0.5) * spread;
            var v = 7 + Math.random() * 9;
            pieces.push({
                x: x, y: y, vx: Math.cos(a) * v, vy: Math.sin(a) * v,
                w: 6 + Math.random() * 7, h: 8 + Math.random() * 10,
                r: Math.random() * Math.PI, vr: (Math.random() - 0.5) * 0.35,
                c: colors[(Math.random() * colors.length) | 0],
                shape: Math.random() < 0.3 ? 'circle' : 'rect',
                life: 0
            });
        }
    }
    function rain(n) {
        for (var i = 0; i < n; i++) {
            pieces.push({
                x: Math.random() * W, y: -20 - Math.random() * H * 0.5, vx: (Math.random() - 0.5) * 2, vy: 2 + Math.random() * 3,
                w: 6 + Math.random() * 6, h: 8 + Math.random() * 8, r: Math.random() * Math.PI, vr: (Math.random() - 0.5) * 0.3,
                c: colors[(Math.random() * colors.length) | 0], shape: Math.random() < 0.3 ? 'circle' : 'rect', life: 0
            });
        }
    }
    function frame(t) {
        ctx.clearRect(0, 0, W, H);
        for (var i = pieces.length - 1; i >= 0; i--) {
            var p = pieces[i];
            p.life++;
            p.vy += 0.22; p.vx *= 0.985; p.vy *= 0.985;
            p.x += p.vx + Math.sin((p.life + i) / 12) * 0.6; p.y += p.vy; p.r += p.vr;
            if (p.y > H + 30) { pieces.splice(i, 1); continue; }
            ctx.save();
            ctx.translate(p.x, p.y); ctx.rotate(p.r);
            ctx.fillStyle = p.c;
            if (p.c === '#ffffff') { ctx.strokeStyle = '#d6d7d7'; ctx.lineWidth = 1; }
            if (p.shape === 'circle') { ctx.beginPath(); ctx.arc(0, 0, p.w / 2, 0, Math.PI * 2); ctx.fill(); }
            else { ctx.scale(1, Math.cos(p.life / 6)); ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h); }
            ctx.restore();
        }
        if (pieces.length || t - start < 1500) requestAnimationFrame(frame);
    }
    resize();
    window.addEventListener('resize', resize);
    burst(W * 0.2, H * 0.95, 90, 1.1);
    burst(W * 0.8, H * 0.95, 90, 1.1);
    setTimeout(function () { burst(W * 0.5, H * 0.9, 120, 1.6); }, 350);
    setTimeout(function () { rain(80); }, 900);
    requestAnimationFrame(frame);
})();
