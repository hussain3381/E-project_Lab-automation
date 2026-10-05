// Keep the theme choice consistent across pages and browser visits.
(function () {
    'use strict';

    const storageKey = 'lab-theme';
    const root = document.documentElement;

    // Apply a validated theme value and update the accessible toggle label.
    function applyTheme(theme) {
        const selectedTheme = theme === 'light' ? 'light' : 'dark';
        root.dataset.theme = selectedTheme;

        const button = document.querySelector('[data-theme-toggle]');
        if (button) {
            const nextTheme = selectedTheme === 'dark' ? 'light' : 'dark';
            button.textContent = selectedTheme === 'dark' ? '☀ Light theme' : '☾ Dark theme';
            button.setAttribute('aria-label', `Switch to ${nextTheme} theme`);
            button.setAttribute('aria-pressed', String(selectedTheme === 'light'));
            button.title = `Switch to ${nextTheme} theme`;
        }
    }

    // Add a small global toggle so existing screens need no duplicate markup.
    function addThemeToggle() {
        if (document.querySelector('[data-theme-toggle]')) {
            applyTheme(root.dataset.theme);
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'theme-toggle';
        button.dataset.themeToggle = 'true';
        button.addEventListener('click', function () {
            const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
            try {
                window.localStorage.setItem(storageKey, nextTheme);
            } catch (error) {
                // Keep the switch usable when browser storage is disabled.
            }
            applyTheme(nextTheme);
        });
        document.body.appendChild(button);
        applyTheme(root.dataset.theme);
    }

    // Restore the saved selection and support changes made in another browser tab.
    try {
        const savedTheme = window.localStorage.getItem(storageKey);
        if (savedTheme === 'light' || savedTheme === 'dark') {
            root.dataset.theme = savedTheme;
        }
    } catch (error) {
        root.dataset.theme = root.dataset.theme || 'dark';
    }

    window.addEventListener('storage', function (event) {
        if (event.key === storageKey && (event.newValue === 'light' || event.newValue === 'dark')) {
            applyTheme(event.newValue);
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addThemeToggle, { once: true });
    } else {
        addThemeToggle();
    }
})();
