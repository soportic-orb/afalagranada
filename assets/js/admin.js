(function () {
    'use strict';
    if (!window.Quill) return;

    function uploadImage(quill) {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/jpeg,image/png,image/webp,image/gif';
        input.onchange = function () {
            var file = input.files[0];
            if (!file) return;
            var fd = new FormData();
            fd.append('image', file);
            fd.append('_csrf', window.AFA.csrf);
            fetch(window.AFA.uploadUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.error) { alert(d.error); return; }
                    var range = quill.getSelection(true);
                    quill.insertEmbed(range.index, 'image', d.url, 'user');
                    quill.setSelection(range.index + 1);
                })
                .catch(function () { alert('No s\'ha pogut pujar la imatge'); });
        };
        input.click();
    }

    document.querySelectorAll('.editor').forEach(function (el) {
        var source = document.getElementById(el.dataset.target);
        var small = el.classList.contains('small');
        var toolbar = small
            ? [['bold', 'italic', 'underline'], ['link'], [{ list: 'ordered' }, { list: 'bullet' }], ['clean']]
            : [
                [{ header: [2, 3, 4, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ align: [] }],
                ['blockquote', 'link', 'image', 'video'],
                ['clean']
            ];
        var quill = new Quill(el, {
            theme: 'snow',
            placeholder: 'Escriu aquí...',
            modules: { toolbar: { container: toolbar, handlers: { image: function () { uploadImage(quill); } } } }
        });
        if (source.value) {
            quill.clipboard.dangerouslyPasteHTML(source.value);
        }
        var form = el.closest('form');
        form.addEventListener('submit', function () {
            var html = quill.root.innerHTML;
            source.value = (html === '<p><br></p>') ? '' : html;
        });
    });
})();
