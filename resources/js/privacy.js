/**
 * The eye button in the top bar hides every [data-sensitive] amount behind dots (see app.css).
 * The choice is remembered in this browser; the layout applies it before the page paints.
 */
const root = document.documentElement;

const refreshButtons = () => {
    const isHidden = root.hasAttribute('data-hide-amounts');

    document.querySelectorAll('[data-privacy-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', String(isHidden));
        button.setAttribute('aria-label', isHidden ? 'نمایش مبالغ' : 'پنهان کردن مبالغ');
    });
};

document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-privacy-toggle]')) {
        return;
    }

    const isHidden = root.toggleAttribute('data-hide-amounts');

    try {
        localStorage.setItem('hide-amounts', isHidden ? '1' : '0');
    } catch {
        // Storage can be unavailable (e.g. private mode); hiding still works for this page view.
    }

    refreshButtons();
});

refreshButtons();
