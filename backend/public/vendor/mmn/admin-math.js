/*
 * Draws formulas in the admin: $...$ and $$...$$ (and \(...\) / \[...\]) inside any element with class "cb-math"
 * (builder rows, table cells, the live preview in the question editor). Editors are never touched, so typing LaTeX is safe.
 * KaTeX, auto-render and mhchem (chemistry) are loaded before this file, from public/vendor/katex.
 */
(function () {
    const options = {
        delimiters: [
            {left: '$$', right: '$$', display: true},
            {left: '\[', right: '\]', display: true},
            {left: '$', right: '$', display: false},
            {left: '\(', right: '\)', display: false},
        ],
        throwOnError: false,
        strict: 'ignore',
    };
    let queued = false;

    function render() {
        queued = false;
        if (!window.renderMathInElement) return setTimeout(schedule, 200);
        document.querySelectorAll('.cb-math').forEach((el) => {
            if (el.dataset.mathSource === el.textContent) return;
            window.renderMathInElement(el, options);
            el.dataset.mathSource = el.textContent;
        });
    }

    function schedule() {
        if (queued) return;
        queued = true;
        setTimeout(render, 60);
    }

    new MutationObserver(schedule).observe(document.documentElement, {childList: true, subtree: true});
    document.addEventListener('DOMContentLoaded', schedule);
})();
