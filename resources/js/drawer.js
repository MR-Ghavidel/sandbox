const desktopQuery = window.matchMedia('(min-width: 1024px)');

const setDrawerOpen = (isOpen) => {
    document.body.dataset.drawer = isOpen ? 'open' : 'closed';

    // Only remember the choice on desktop; on mobile the drawer always starts closed.
    if (desktopQuery.matches) {
        try {
            localStorage.setItem('drawer', isOpen ? 'open' : 'closed');
        } catch {
            // Storage can be unavailable (e.g. private mode); the drawer still works.
        }
    }
};

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-drawer-toggle]')) {
        setDrawerOpen(document.body.dataset.drawer !== 'open');
    } else if (event.target.closest('[data-drawer-close], [data-drawer-overlay]')) {
        setDrawerOpen(false);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !desktopQuery.matches && document.body.dataset.drawer === 'open') {
        setDrawerOpen(false);
    }
});
