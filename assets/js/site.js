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
