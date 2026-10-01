import { onPageLoad } from './support/page';
import { detectDirection } from './support/text-direction';

onPageLoad(() => {
    // Forms marked [data-confirm] ask before submitting (the task board has its own handler).
    const confirmSubmit = (event) => {
        const form = event.target.closest('form[data-confirm]');

        if (form && !form.closest('[data-task-board]') && !window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    };

    // Fields marked [data-auto-direction] follow their text while typing: Persian with a few
    // English words stays right-to-left, English text turns left-to-right.
    const followDirection = (event) => {
        if (event.target.matches('[data-auto-direction]')) {
            event.target.dir = detectDirection(event.target.value);
        }
    };

    // Buttons marked [data-select-sites="all"|"none"] tick or clear every enabled checkbox of their form.
    const selectAll = (event) => {
        const button = event.target.closest('[data-select-sites]');

        if (!button) {
            return;
        }

        button.form.querySelectorAll('input[type="checkbox"]:not(:disabled)').forEach((checkbox) => {
            checkbox.checked = button.dataset.selectSites === 'all';
        });
    };

    document.querySelectorAll('[data-auto-direction]').forEach((field) => {
        field.dir = detectDirection(field.value);
    });

    document.addEventListener('submit', confirmSubmit);
    document.addEventListener('input', followDirection);
    document.addEventListener('click', selectAll);

    return () => {
        document.removeEventListener('submit', confirmSubmit);
        document.removeEventListener('input', followDirection);
        document.removeEventListener('click', selectAll);
    };
});
