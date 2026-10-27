(function () {
    'use strict';
    var base = document.body.dataset.base || '';
    var csrfMeta = document.querySelector('meta[name="csrf"]');
    var csrf = csrfMeta ? csrfMeta.content : '';

    /* Theme toggle */
    var themeBtn = document.getElementById('theme-toggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = next;
            try { localStorage.setItem('theme', next); } catch (e) {}
        });
    }

    /* Auto-submit filter selects */
    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });

    /* Confirm before destructive admin forms */
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    /* Print roadmap */
    document.querySelectorAll('[data-print]').forEach(function (btn) {
        btn.addEventListener('click', function () { window.print(); });
    });

    /* Search suggestions (datalist) */
    var list = document.getElementById('career-suggestions');
    var searchInput = document.querySelector('input[list="career-suggestions"]');
    if (list && searchInput) {
        var timer = null;
        searchInput.addEventListener('input', function () {
            clearTimeout(timer);
            var q = searchInput.value.trim();
            if (q.length < 2) return;
            timer = setTimeout(function () {
                fetch(base + 'api/search-careers.php?q=' + encodeURIComponent(q))
                    .then(function (r) { return r.ok ? r.json() : []; })
                    .then(function (items) {
                        list.replaceChildren.apply(list, items.map(function (c) {
                            var o = document.createElement('option');
                            o.value = c.title;
                            return o;
                        }));
                    })
                    .catch(function () {});
            }, 250);
        });
    }

    /* Progress tracking on career-details.php */
    var page = document.querySelector('[data-career-id]');
    var boxes = document.querySelectorAll('input[data-progress]');
    if (page && boxes.length) {
        var bar = document.getElementById('progress-bar');
        var text = document.getElementById('progress-text');
        var note = document.createElement('p');
        note.className = 'flash error';
        note.hidden = true;
        note.setAttribute('role', 'alert');
        page.insertBefore(note, page.firstChild);

        boxes.forEach(function (box) {
            box.addEventListener('change', function () {
                var body = new URLSearchParams({
                    csrf: csrf,
                    career_id: page.dataset.careerId,
                    type: box.dataset.progress,
                    key: box.dataset.key
                });
                box.disabled = true;
                fetch(base + 'api/toggle-progress.php', { method: 'POST', body: body })
                    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                    .then(function (res) {
                        if (!res.ok) throw new Error(res.d.error || 'Could not save.');
                        note.hidden = true;
                        box.checked = res.d.checked;
                        if (bar) bar.value = res.d.percent;
                        if (text) text.textContent = res.d.percent + '%';
                    })
                    .catch(function (err) {
                        box.checked = !box.checked; // revert
                        note.textContent = err.message;
                        note.hidden = false;
                    })
                    .finally(function () { box.disabled = false; });
            });
        });
    }
})();
