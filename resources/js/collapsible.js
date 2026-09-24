/**
 * Collapsible sections: a [data-collapsible="unique-key"] container with a
 * [data-collapse-toggle] button inside. The collapsed state is stored per key,
 * so it survives page reloads (e.g. after adding a task). Sections marked with
 * [data-collapsed-by-default] start collapsed until the user opens them.
 */
const storageKey = (section) => `collapsed:${section.dataset.collapsible}`;

const setCollapsed = (section, isCollapsed) => {
    section.toggleAttribute('data-collapsed', isCollapsed);
    section.querySelector('[data-collapse-toggle]')?.setAttribute('aria-expanded', String(!isCollapsed));

    try {
        localStorage.setItem(storageKey(section), isCollapsed ? '1' : '0');
    } catch {
        // Storage can be unavailable (e.g. private mode); collapsing still works for this page view.
    }
};

document.querySelectorAll('[data-collapsible]').forEach((section) => {
    let storedState = null;

    try {
        storedState = localStorage.getItem(storageKey(section));
    } catch {
        // Ignore unavailable storage.
    }

    const isCollapsed = storedState === null ? section.hasAttribute('data-collapsed-by-default') : storedState === '1';

    section.toggleAttribute('data-collapsed', isCollapsed);
    section.querySelector('[data-collapse-toggle]')?.setAttribute('aria-expanded', String(!isCollapsed));
});

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-collapse-toggle]');

    if (toggle) {
        const section = toggle.closest('[data-collapsible]');
        setCollapsed(section, !section.hasAttribute('data-collapsed'));

        return;
    }

    const groupButton = event.target.closest('[data-collapse-all], [data-expand-all]');

    if (groupButton) {
        const shouldCollapse = groupButton.hasAttribute('data-collapse-all');
        const group = groupButton.dataset.collapseAll ?? groupButton.dataset.expandAll;

        document
            .querySelectorAll(`[data-collapse-group="${group}"]`)
            .forEach((section) => setCollapsed(section, shouldCollapse));
    }
});
