/*
 * Small progressive enhancements. Every page works without JavaScript.
 */

// Live character count for the policy textarea (a passive hint, not announced).
const formatter = new Intl.NumberFormat(document.documentElement.lang || undefined);

for (const output of document.querySelectorAll('[data-character-count]')) {
    const field = document.getElementById(output.dataset.characterCount);
    const template = output.dataset.template ?? ':count';

    if (!(field instanceof HTMLTextAreaElement)) {
        continue;
    }

    const update = () => {
        // Count code points, as the server does.
        output.textContent = template.replace(':count', formatter.format([...field.value].length));
    };

    field.addEventListener('input', update);
    update();
}

// Prevent double submissions once a valid form has been sent.
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-submit-once')) {
        return;
    }

    for (const button of form.querySelectorAll('button[type="submit"]')) {
        button.disabled = true;
        button.dataset.label ??= button.textContent;
        button.textContent = button.dataset.busyLabel ?? button.textContent;
    }
});

// Jumping to a rule (from the coverage strip or the review list) opens its card.
const openRule = (hash) => {
    const target = hash ? document.getElementById(decodeURIComponent(hash.slice(1))) : null;

    if (target instanceof HTMLDetailsElement) {
        target.open = true;
    }
};

document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[href^="#rule-"]') : null;

    if (link instanceof HTMLAnchorElement) {
        openRule(link.hash);
    }
});

window.addEventListener('hashchange', () => openRule(location.hash));
openRule(location.hash);

// Re-enable the buttons when the page is restored from the back/forward cache.
window.addEventListener('pageshow', () => {
    for (const button of document.querySelectorAll('form[data-submit-once] button[type="submit"]')) {
        button.disabled = false;

        if (button.dataset.label) {
            button.textContent = button.dataset.label;
        }
    }
});
