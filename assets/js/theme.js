// Keep the selected palette in sync with the account preference and every visible theme control.
(function () {
    'use strict';

    const storageKey = 'lab-theme';
    const root = document.documentElement;

    function csrfValue() {
        return document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('[data-theme-csrf]')?.value
            || '';
    }

    // Apply a validated theme value and update every accessible toggle label.
    function applyTheme(theme) {
        const selectedTheme = theme === 'light' ? 'light' : 'dark';
        root.dataset.theme = selectedTheme;
        root.dataset.userTheme = selectedTheme;

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            const nextTheme = selectedTheme === 'dark' ? 'light' : 'dark';
            button.textContent = selectedTheme === 'dark' ? '☀ Light theme' : '☾ Dark theme';
            button.setAttribute('aria-label', `Switch to ${nextTheme} theme`);
            button.setAttribute('aria-pressed', String(selectedTheme === 'light'));
            button.title = `Switch to ${nextTheme} theme`;
        });
    }

    // Theme changes from a signed-in page are saved against this account, not only this browser.
    async function saveTheme(theme) {
        const token = csrfValue();
        if (!token) return;

        const body = new URLSearchParams({
            preference_ajax: '1',
            theme,
            csrf_token: token,
        });
        try {
            await fetch('save-preferences.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body,
            });
        } catch (error) {
            // The local control remains usable if the preference endpoint is temporarily offline.
        }
    }

    // Bind shared-shell buttons as well as the fallback used by older pages.
    function addThemeToggle() {
        if (!document.querySelector('[data-theme-toggle]')) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'theme-toggle theme-toggle--floating';
            button.dataset.themeToggle = 'true';
            document.body.appendChild(button);
        }

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            if (button.dataset.themeBound === 'true') return;
            button.dataset.themeBound = 'true';
            button.addEventListener('click', function () {
                const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
                try {
                    window.localStorage.setItem(storageKey, nextTheme);
                } catch (error) {
                    // Keep the switch usable when browser storage is disabled.
                }
                applyTheme(nextTheme);
                void saveTheme(nextTheme);
            });
        });
        applyTheme(root.dataset.theme);
    }

    // Signed-in pages use the account's saved palette; public pages fall back to this browser.
    const accountTheme = root.dataset.userTheme;
    if (accountTheme === 'light' || accountTheme === 'dark') {
        root.dataset.theme = accountTheme;
    } else {
        try {
            const savedTheme = window.localStorage.getItem(storageKey);
            if (savedTheme === 'light' || savedTheme === 'dark') root.dataset.theme = savedTheme;
        } catch (error) {
            root.dataset.theme = root.dataset.theme || 'dark';
        }
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
