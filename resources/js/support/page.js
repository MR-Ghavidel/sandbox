/**
 * Page lifecycle shared by the page scripts, so they also work after a soft navigation
 * (see navigation.js), when only the page content is replaced instead of the whole document.
 *
 * - onPageLoad(init): init() runs on the first load and after every soft navigation. It may return
 *   a cleanup function, which runs before the content is replaced (timers, document listeners, ...).
 * - onBeforeLeave(check): check() runs before a soft navigation; return a message to ask the user
 *   to confirm leaving (e.g. while an auto-save is still running).
 */
const initializers = [];
const leaveChecks = [];
let cleanups = [];

export const onPageLoad = (init) => {
    initializers.push(init);
};

export const onBeforeLeave = (check) => {
    leaveChecks.push(check);
};

export const runPageInitializers = () => {
    cleanups = initializers.map((init) => init()).filter((cleanup) => typeof cleanup === 'function');
};

export const runPageCleanups = () => {
    cleanups.forEach((cleanup) => cleanup());
    cleanups = [];
};

/**
 * @returns {string|null} The first warning of a page that should not be left yet.
 */
export const leaveWarning = () => leaveChecks.map((check) => check()).find(Boolean) ?? null;
