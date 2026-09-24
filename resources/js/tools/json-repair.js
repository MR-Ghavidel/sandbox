/**
 * Pure helpers of the JSON formatter tool: lenient parsing, fixing broken characters and highlighting.
 */

/* ---------- Broken characters ("mojibake") ---------- */

// Windows-1252 characters that stand for the bytes 0x80–0x9F.
const windows1252Bytes = new Map([
    ['€', 0x80], ['‚', 0x82], ['ƒ', 0x83], ['„', 0x84], ['…', 0x85], ['†', 0x86], ['‡', 0x87], ['ˆ', 0x88],
    ['‰', 0x89], ['Š', 0x8a], ['‹', 0x8b], ['Œ', 0x8c], ['Ž', 0x8e], ['‘', 0x91], ['’', 0x92], ['“', 0x93],
    ['”', 0x94], ['•', 0x95], ['–', 0x96], ['—', 0x97], ['˜', 0x98], ['™', 0x99], ['š', 0x9a], ['›', 0x9b],
    ['œ', 0x9c], ['ž', 0x9e], ['Ÿ', 0x9f],
]);

const specialCharacters = [...windows1252Bytes.keys()].join('');

// A UTF-8 lead byte (Â–ô) followed by continuation bytes, as they look when UTF-8 is read as Windows-1252/Latin-1.
const mojibakeRun = new RegExp(`[\\u00C2-\\u00F4][\\u0080-\\u00BF${specialCharacters}][\\u0080-\\u00FF${specialCharacters}]*`, 'g');

const utf8Decoder = new TextDecoder('utf-8', { fatal: true });

/**
 * Repairs text that was UTF-8 but got decoded as Windows-1252/Latin-1, e.g. "Ø³Ù„Ø§Ù…" → "سلام".
 * Only runs that turn into valid UTF-8 are replaced, so normal accented text is left alone.
 */
export const fixMojibake = (text) =>
    text.replace(mojibakeRun, (run) => {
        const bytes = [...run].map((character) => windows1252Bytes.get(character) ?? character.charCodeAt(0));

        try {
            return utf8Decoder.decode(new Uint8Array(bytes));
        } catch {
            return run;
        }
    });

/* ---------- Lenient parsing ---------- */

/**
 * Removes // and /* *\/ comments and trailing commas outside of strings.
 */
export const stripCommentsAndTrailingCommas = (text) => {
    let result = '';
    let index = 0;

    while (index < text.length) {
        const character = text[index];

        if (character === '"') {
            const end = findStringEnd(text, index);
            result += text.slice(index, end);
            index = end;
        } else if (character === '/' && text[index + 1] === '/') {
            while (index < text.length && text[index] !== '\n') {
                index++;
            }
        } else if (character === '/' && text[index + 1] === '*') {
            const end = text.indexOf('*/', index + 2);
            index = end === -1 ? text.length : end + 2;
        } else if (character === ',' && /^\s*[}\]]/.test(text.slice(index + 1))) {
            index++;
        } else {
            result += character;
            index++;
        }
    }

    return result;
};

const findStringEnd = (text, start) => {
    let index = start + 1;

    while (index < text.length && text[index] !== '"') {
        index += text[index] === '\\' ? 2 : 1;
    }

    return index + 1;
};

/**
 * Turns \uXXXX escapes into characters, for showing text that is not valid JSON.
 */
export const decodeUnicodeEscapes = (text) =>
    text.replace(/\\u([0-9a-fA-F]{4})/g, (_, hex) => String.fromCharCode(parseInt(hex, 16)));

const describeParseError = (error, text) => {
    const position = Number(error.message.match(/position (\d+)/)?.[1]);

    if (Number.isNaN(position)) {
        return error.message;
    }

    const before = text.slice(0, position);
    const line = before.split('\n').length;
    const column = position - before.lastIndexOf('\n');

    return `خط ${line.toLocaleString('fa-IR')}، ستون ${column.toLocaleString('fa-IR')}: ${error.message}`;
};

/* ---------- Transforming parsed values ---------- */

const mapValue = (value, mapString) => {
    if (typeof value === 'string') {
        return mapString(value);
    }

    if (Array.isArray(value)) {
        return value.map((item) => mapValue(item, mapString));
    }

    if (value !== null && typeof value === 'object') {
        return Object.fromEntries(Object.entries(value).map(([key, item]) => [mapString(key, true), mapValue(item, mapString)]));
    }

    return value;
};

export const sortKeysDeep = (value) => {
    if (Array.isArray(value)) {
        return value.map(sortKeysDeep);
    }

    if (value !== null && typeof value === 'object') {
        return Object.fromEntries(
            Object.keys(value)
                .sort((first, second) => first.localeCompare(second))
                .map((key) => [key, sortKeysDeep(value[key])]),
        );
    }

    return value;
};

const looksLikeJson = (text) => /^\s*[[{]/.test(text) && /[\]}]\s*$/.test(text);

/**
 * @returns {{value?: unknown, notes: string[], error?: string, fallbackText?: string, isEmpty?: boolean}}
 */
export const repairAndParse = (raw, { fixBrokenCharacters = true, parseNested = true, sortKeys = false } = {}) => {
    const notes = [];
    let text = raw.replace(/^\uFEFF/, '').trim();

    if (text === '') {
        return { notes, isEmpty: true };
    }

    if (fixBrokenCharacters) {
        const fixed = fixMojibake(text);

        if (fixed !== text) {
            notes.push('حروف خراب (مثل Ø³Ù„Ø§Ù…) اصلاح شد.');
            text = fixed;
        }
    }

    let value;

    try {
        value = JSON.parse(text);
    } catch (error) {
        try {
            value = JSON.parse(stripCommentsAndTrailingCommas(text));
            notes.push('کامنت‌ها یا ویرگول‌های اضافه حذف شد.');
        } catch {
            return { notes, error: describeParseError(error, text), fallbackText: decodeUnicodeEscapes(text) };
        }
    }

    // JSON that was encoded as a string, e.g. "{\"name\":\"...\"}".
    let unwrapCount = 0;

    while (typeof value === 'string' && looksLikeJson(value)) {
        try {
            value = JSON.parse(value);
            unwrapCount++;
        } catch {
            break;
        }
    }

    if (unwrapCount > 0) {
        notes.push('کل ورودی یک رشته JSON بود و باز شد.');
    }

    if (fixBrokenCharacters) {
        // Escapes like \u00d8\u00b3 only become broken characters after parsing.
        let fixedCount = 0;
        value = mapValue(value, (string) => {
            const fixed = fixMojibake(string);
            fixedCount += fixed !== string ? 1 : 0;

            return fixed;
        });

        if (fixedCount > 0 && !notes.some((note) => note.startsWith('حروف خراب'))) {
            notes.push('حروف خراب (مثل Ø³Ù„Ø§Ù…) اصلاح شد.');
        }
    }

    if (parseNested) {
        let nestedCount = 0;
        value = mapValue(value, (string, isKey) => {
            if (isKey || !looksLikeJson(string)) {
                return string;
            }

            try {
                const parsed = JSON.parse(string);
                nestedCount++;

                return parsed;
            } catch {
                return string;
            }
        });

        if (nestedCount > 0) {
            notes.push(`${nestedCount.toLocaleString('fa-IR')} رشته JSON داخلی باز شد.`);
        }
    }

    return { value: sortKeys ? sortKeysDeep(value) : value, notes };
};

/* ---------- Highlighting ---------- */

const escapeHtml = (text) => text.replace(/[&<>"']/g, (character) => `&#${character.charCodeAt(0)};`);

const tokenPattern = /("(?:\\.|[^"\\])*")(\s*:)?|\b(?:true|false|null)\b|-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?/g;

/**
 * Wraps keys, strings, numbers and literals of formatted JSON in coloured spans (HTML-escaped).
 */
export const highlightJson = (json) => {
    let html = '';
    let lastIndex = 0;

    for (const match of json.matchAll(tokenPattern)) {
        html += escapeHtml(json.slice(lastIndex, match.index));

        const [token, string, colon] = match;
        const className = string
            ? colon
                ? 'text-sky-700 dark:text-sky-300'
                : 'text-emerald-700 dark:text-emerald-300'
            : /^[tfn]/.test(token)
              ? 'text-purple-700 dark:text-purple-300'
              : 'text-amber-700 dark:text-amber-300';

        html += string
            ? `<span class="${className}">${escapeHtml(string)}</span>${colon ? escapeHtml(colon) : ''}`
            : `<span class="${className}">${escapeHtml(token)}</span>`;
        lastIndex = match.index + token.length;
    }

    return html + escapeHtml(json.slice(lastIndex));
};
