/**
 * Copy buttons: [data-copy-from="<selector>"] copies that element's value/text,
 * [data-copy-text="..."] copies the given text. The button briefly shows "کپی شد ✓".
 */
const copyText = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
    } catch {
        // Fallback for browsers/contexts without the async clipboard API.
        const helper = Object.assign(document.createElement('textarea'), { value: text });
        helper.style.position = 'fixed';
        helper.style.opacity = '0';
        document.body.append(helper);
        helper.select();
        document.execCommand('copy');
        helper.remove();
    }
};

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy-from], [data-copy-text]');

    if (!button) {
        return;
    }

    const source = button.dataset.copyFrom ? document.querySelector(button.dataset.copyFrom) : null;
    const text = button.dataset.copyText ?? (source && 'value' in source ? source.value : source?.textContent) ?? '';

    if (text === '') {
        return;
    }

    await copyText(text);

    button.dataset.originalLabel ??= button.textContent;
    button.textContent = 'کپی شد ✓';
    clearTimeout(button.copyTimer);
    button.copyTimer = setTimeout(() => {
        button.textContent = button.dataset.originalLabel;
    }, 1500);
});
