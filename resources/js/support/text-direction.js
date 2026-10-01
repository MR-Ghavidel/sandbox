/**
 * Same rule as App\Support\TextDirection: the script with more words wins and a tie is right-to-left.
 * The browser's dir="auto" looks only at the first letter, so a Persian sentence starting with an
 * English word would turn left-to-right.
 *
 * @returns {'rtl'|'ltr'}
 */
export const detectDirection = (text, fallback = 'rtl') => {
    const words = (text ?? '').match(/\p{L}[\p{L}\p{M}]*/gu) ?? [];
    const rtlWords = words.filter((word) => /^[\p{Script=Arabic}\p{Script=Hebrew}]/u.test(word)).length;
    const ltrWords = words.length - rtlWords;

    if (words.length === 0) {
        return fallback;
    }

    return rtlWords >= ltrWords ? 'rtl' : 'ltr';
};
