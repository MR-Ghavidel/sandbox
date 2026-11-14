import { onPageLoad } from './support/page';

onPageLoad(() => {
    const titles = [...document.querySelectorAll('[data-schedule-course-title]')];

    if (titles.length === 0) {
        return;
    }

    let isActive = true;

    const measureTitle = (title) => {
        const text = title.querySelector('[data-schedule-course-title-text]');

        if (!text) {
            return;
        }

        const overflow = Math.ceil(text.getBoundingClientRect().width - title.getBoundingClientRect().width);

        if (overflow > 2) {
            title.dataset.overflowing = 'true';
            title.style.setProperty('--schedule-course-title-shift', `${-overflow}px`);
            title.style.setProperty('--schedule-course-title-duration', `${Math.min(20, Math.max(7, overflow / 14))}s`);

            return;
        }

        delete title.dataset.overflowing;
        title.style.removeProperty('--schedule-course-title-shift');
        title.style.removeProperty('--schedule-course-title-duration');
    };

    const measureAllTitles = () => titles.forEach(measureTitle);
    const observer = new ResizeObserver((entries) => entries.forEach(({ target }) => measureTitle(target)));

    titles.forEach((title) => observer.observe(title));
    measureAllTitles();

    document.fonts?.ready.then(() => {
        if (isActive) {
            measureAllTitles();
        }
    });

    return () => {
        isActive = false;
        observer.disconnect();
    };
});
