// Keep the theme choice consistent across pages and browser visits.
(function () {
    'use strict';

    const storageKey = 'lab-theme';
    const root = document.documentElement;

    // Apply a validated theme value and update every accessible toggle label.
    function applyTheme(theme) {
        const selectedTheme = theme === 'light' ? 'light' : 'dark';
        root.dataset.theme = selectedTheme;

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            const nextTheme = selectedTheme === 'dark' ? 'light' : 'dark';
            const icon = document.createElement('i');
            icon.className = selectedTheme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
            icon.setAttribute('aria-hidden', 'true');
            const label = document.createElement('span');
            label.textContent = selectedTheme === 'dark' ? 'Light theme' : 'Dark theme';
            button.replaceChildren(icon, label);
            button.setAttribute('aria-label', `Switch to ${nextTheme} theme`);
            button.setAttribute('aria-pressed', String(selectedTheme === 'light'));
            button.title = `Switch to ${nextTheme} theme`;
        });
    }

    // Bind existing shared-shell buttons as well as the fallback used by legacy screens.
    function addThemeToggle() {
        if (!document.querySelector('[data-theme-toggle]')) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'theme-toggle theme-toggle--floating';
            button.dataset.themeToggle = 'true';
            document.body.appendChild(button);
        }

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            if (button.dataset.themeBound === 'true') {
                return;
            }
            button.dataset.themeBound = 'true';
            button.addEventListener('click', function () {
                const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
                try {
                    window.localStorage.setItem(storageKey, nextTheme);
                } catch (error) {
                    // Keep the switch usable when browser storage is disabled.
                }
                applyTheme(nextTheme);
            });
        });
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
