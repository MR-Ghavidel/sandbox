import { highlightJson, repairAndParse } from './json-repair';

const tool = document.querySelector('[data-json-formatter]');

if (tool) {
    const input = tool.querySelector('[data-json-input]');
    const output = tool.querySelector('[data-json-output]');
    const status = tool.querySelector('[data-json-status]');
    const notesElement = tool.querySelector('[data-json-notes]');
    const option = (name) => tool.querySelector(`[data-option="${name}"]`);
    let isMinified = false;
    let debounceTimer;

    const sample = JSON.stringify({
        user: { name: 'محمد', city: 'ØªÙ‡Ø±Ø§Ù†' },
        payload: '{"items":[1,2,3],"ok":true}',
        tags: ['کار', 'json',],
    }).replace(/[؀-ۿ]/g, (character) => `\\u${character.charCodeAt(0).toString(16).padStart(4, '0')}`);

    const setStatus = (text, classes) => {
        status.textContent = text;
        status.className = `rounded-full px-2 py-0.5 text-xs ${classes}`;
    };

    const render = () => {
        const result = repairAndParse(input.value, {
            fixBrokenCharacters: option('fixMojibake').checked,
            parseNested: option('parseNested').checked,
            sortKeys: option('sortKeys').checked,
        });

        notesElement.textContent = result.notes.join(' ');
        notesElement.classList.toggle('hidden', result.notes.length === 0);

        if (result.isEmpty) {
            output.textContent = '';
            setStatus('', '');

            return;
        }

        if (result.error) {
            // Still show the text with \uXXXX escapes decoded, so it can be read.
            output.textContent = result.fallbackText;
            setStatus(`نامعتبر — ${result.error}`, 'bg-red-100 text-red-700');

            return;
        }

        const indentChoice = option('indent').value;
        const indent = indentChoice === 'tab' ? '\t' : Number(indentChoice);
        const json = isMinified ? JSON.stringify(result.value) : JSON.stringify(result.value, null, indent);

        output.innerHTML = highlightJson(json);
        setStatus('معتبر ✓', 'bg-emerald-100 text-emerald-700');
    };

    const scheduleRender = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(render, 200);
    };

    input.addEventListener('input', scheduleRender);
    tool.querySelectorAll('[data-option]').forEach((element) => element.addEventListener('change', render));

    tool.addEventListener('click', (event) => {
        const action = event.target.closest('[data-action]')?.dataset.action;

        if (action === 'sample') {
            input.value = sample;
        } else if (action === 'clear') {
            input.value = '';
            input.focus();
        } else if (action === 'minify' || action === 'format') {
            isMinified = action === 'minify';
        } else {
            return;
        }

        render();
    });
}
