import { onBeforeLeave, onPageLoad } from './support/page';

onPageLoad(() => {
    const editor = document.querySelector('[data-note-editor]');

    if (!editor) {
        return;
    }

    const body = editor.querySelector('[data-note-body]');
    const preview = editor.querySelector('[data-note-preview]');
    const previewStatus = editor.querySelector('[data-preview-status]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let previewTimer = null;
    let previewController = null;

    // The preview is rendered by the server, so it matches the saved note exactly.
    const refreshPreview = async () => {
        previewController?.abort();
        previewController = new AbortController();
        previewStatus.textContent = 'در حال به‌روزرسانی...';

        try {
            const response = await fetch(editor.dataset.previewUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ body: body.value }),
                signal: previewController.signal,
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            preview.innerHTML = (await response.json()).html;
            previewStatus.textContent = '';
        } catch (error) {
            if (error.name !== 'AbortError') {
                previewStatus.textContent = 'پیش‌نمایش به‌روز نشد';
            }
        }
    };

    const handleInput = (event) => {
        // Read by onBeforeLeave() and beforeunload to warn about unsaved changes.
        // (Field directions follow the text in forms.js.)
        editor.dataset.dirty = '1';

        if (event.target === body) {
            clearTimeout(previewTimer);
            previewTimer = setTimeout(refreshPreview, 400);
        }
    };

    const warnBeforeUnload = (event) => {
        if (editor.dataset.dirty === '1') {
            event.preventDefault();
        }
    };

    editor.addEventListener('input', handleInput);
    editor.addEventListener('submit', () => {
        delete editor.dataset.dirty;
    });
    window.addEventListener('beforeunload', warnBeforeUnload);

    return () => {
        clearTimeout(previewTimer);
        previewController?.abort();
        window.removeEventListener('beforeunload', warnBeforeUnload);
    };
});

onBeforeLeave(() => {
    const editor = document.querySelector('[data-note-editor]');

    return editor?.dataset.dirty === '1' ? 'تغییرات یادداشت ذخیره نشده است. صفحه را ترک می‌کنید؟' : null;
});
