/**
 * Light / dark theme. The layout sets data-theme on <html> before the page paints: the saved choice,
 * or the system preference when nothing was chosen yet. The sun/moon button switches and saves it.
 */
const root = document.documentElement;
const systemDarkQuery = window.matchMedia('(prefers-color-scheme: dark)');

const savedTheme = () => {
    try {
        return localStorage.getItem('theme');
    } catch {
        return null;
    }
};

const applyTheme = (theme) => {
    root.dataset.theme = theme;

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-label', theme === 'dark' ? 'تم روشن' : 'تم تیره');
    });
};

document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-theme-toggle]')) {
        return;
    }

    const theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    applyTheme(theme);

    try {
        localStorage.setItem('theme', theme);
    } catch {
        // Storage can be unavailable (e.g. private mode); the theme still changes for this page view.
    }
});

// Follow the system setting while the user has not picked a theme.
systemDarkQuery.addEventListener('change', (event) => {
    if (!savedTheme()) {
        applyTheme(event.matches ? 'dark' : 'light');
    }
});

applyTheme(root.dataset.theme ?? (systemDarkQuery.matches ? 'dark' : 'light'));
