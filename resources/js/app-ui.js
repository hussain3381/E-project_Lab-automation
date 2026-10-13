// Small shared interactions; visual transitions respect reduced-motion preferences in CSS.
(() => {
    'use strict';

    const sidebar = document.querySelector('[data-app-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    const toggle = document.querySelector('[data-sidebar-toggle]');

    function closeSidebar() {
        sidebar?.classList.remove('is-open');
        backdrop?.classList.remove('is-visible');
        toggle?.setAttribute('aria-expanded', 'false');
    }

    toggle?.addEventListener('click', () => {
        const isOpen = sidebar?.classList.toggle('is-open') ?? false;
        backdrop?.classList.toggle('is-visible', isOpen);
        toggle?.setAttribute('aria-expanded', String(isOpen));
    });

    backdrop?.addEventListener('click', closeSidebar);
    sidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeSidebar));

    const legacySidebar = document.querySelector('[data-legacy-sidebar]');
    const legacyBackdrop = document.querySelector('[data-legacy-sidebar-backdrop]');
    const legacyToggle = document.querySelector('[data-legacy-sidebar-toggle]');
    function closeLegacySidebar() {
        legacySidebar?.classList.remove('is-open');
        legacyBackdrop?.classList.remove('is-visible');
        legacyToggle?.setAttribute('aria-expanded', 'false');
    }
    legacyToggle?.addEventListener('click', () => {
        const isOpen = legacySidebar?.classList.toggle('is-open') ?? false;
        legacyBackdrop?.classList.toggle('is-visible', isOpen);
        legacyToggle?.setAttribute('aria-expanded', String(isOpen));
    });
    legacyBackdrop?.addEventListener('click', closeLegacySidebar);
    legacySidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeLegacySidebar));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
            closeLegacySidebar();
        }
    });

    // Keep the product/test choices and multi-tester picker aligned with the server's eligible list.
    const assignmentForm = document.querySelector('[data-assignment-form]');
    if (assignmentForm) {
        const productSelect = assignmentForm.querySelector('#assignment-product');
        const testSelect = assignmentForm.querySelector('#assignment-test');
        const testOptions = Array.from(testSelect?.querySelectorAll('option[data-products]') ?? []);
        const productHint = assignmentForm.querySelector('[data-product-hint]');
        const testHint = assignmentForm.querySelector('[data-test-hint]');
        const testerSearch = assignmentForm.querySelector('[data-tester-search]');
        const testerChoices = Array.from(assignmentForm.querySelectorAll('[data-tester-choice]'));
        const noTesterMatches = assignmentForm.querySelector('[data-tester-no-results]');
        const testerCount = assignmentForm.querySelector('[data-tester-count]');
        const resultSelect = assignmentForm.querySelector('#assignment-result');
        const actualOutputField = assignmentForm.querySelector('[data-actual-output-field]');
        const actualOutput = assignmentForm.querySelector('#assignment-actual');

        function syncEligibleTests() {
            const productId = productSelect?.value ?? '';
            let eligibleCount = 0;
            testOptions.forEach((option) => {
                const allowedProducts = (option.dataset.products ?? '').split(',');
                const allowed = productId !== '' && allowedProducts.includes(productId);
                option.hidden = !allowed;
                option.disabled = !allowed;
                if (allowed) eligibleCount += 1;
            });

            if (testSelect) {
                const selectedOption = testSelect.selectedOptions[0];
                if (selectedOption?.dataset.products && selectedOption.disabled) testSelect.value = '';
            }
            if (productHint) {
                productHint.textContent = productId === ''
                    ? 'Select a product to see its eligible next tests.'
                    : 'Cycle and status are checked again when the assignment is saved.';
            }
            if (testHint) {
                testHint.textContent = productId === ''
                    ? 'Test options follow the active family plan and current-cycle sequence.'
                    : eligibleCount > 0
                        ? `${eligibleCount} test type${eligibleCount === 1 ? '' : 's'} available for this product in the current cycle.`
                        : 'No unused test types remain in this cycle.';
            }
        }

        function syncTesterSearch() {
            const needle = (testerSearch?.value ?? '').trim().toLocaleLowerCase();
            let visibleCount = 0;
            testerChoices.forEach((choice) => {
                const matches = (choice.dataset.search ?? '').includes(needle);
                choice.hidden = !matches;
                if (matches) visibleCount += 1;
            });
            if (noTesterMatches) noTesterMatches.hidden = visibleCount !== 0 || testerChoices.length === 0;
        }

        function syncTesterCount() {
            const selectedCount = assignmentForm.querySelectorAll('[data-tester-checkbox]:checked:not(:disabled)').length;
            if (testerCount) testerCount.textContent = `${selectedCount} selected`;
        }

        function syncDirectResult() {
            if (!resultSelect || !actualOutputField || !actualOutput) return;
            const needsActual = resultSelect.value === 'PASS' || resultSelect.value === 'FAIL';
            actualOutputField.hidden = !needsActual;
            actualOutput.required = needsActual;
        }

        productSelect?.addEventListener('change', syncEligibleTests);
        testSelect?.addEventListener('change', () => {
            if (testSelect.value !== '') testSelect.setCustomValidity('');
        });
        testerSearch?.addEventListener('input', syncTesterSearch);
        assignmentForm.querySelectorAll('[data-tester-checkbox]').forEach((checkbox) => {
            checkbox.addEventListener('change', syncTesterCount);
        });
        resultSelect?.addEventListener('change', syncDirectResult);
        assignmentForm.addEventListener('submit', (event) => {
            const selectedTesters = assignmentForm.querySelectorAll('[data-tester-checkbox]:checked:not(:disabled)').length;
            if (selectedTesters === 0) {
                event.preventDefault();
                testerSearch?.setCustomValidity('Select at least one available Tester.');
                testerSearch?.reportValidity();
                testerSearch?.focus();
                return;
            }
            testerSearch?.setCustomValidity('');
        });

        testerSearch?.addEventListener('input', () => testerSearch.setCustomValidity(''));
        syncEligibleTests();
        syncTesterSearch();
        syncTesterCount();
        syncDirectResult();
    }

    // A live local clock adds a subtle sense of recency without implying database polling.
    document.querySelectorAll('[data-live-clock]').forEach((clock) => {
        const formatter = new Intl.DateTimeFormat(undefined, {
            weekday: 'short', day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23',
        });
        const updateClock = () => {
            const now = new Date();
            const parts = Object.fromEntries(formatter.formatToParts(now).map((part) => [part.type, part.value]));
            clock.textContent = `${parts.weekday}, ${parts.day} ${parts.month} ${parts.year} · ${parts.hour}:${parts.minute}:${parts.second}`;
            clock.dateTime = now.toISOString();
        };
        updateClock();
        window.setInterval(updateClock, 1000);
    });

    // Reveal content as it enters the viewport; fall back to visible content on older browsers.
    const revealItems = document.querySelectorAll('[data-reveal]');
    const reduceMotion = document.documentElement.dataset.reducedMotion === '1'
        || window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if ('IntersectionObserver' in window && !reduceMotion) {
        const observer = new IntersectionObserver((entries, currentObserver) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    currentObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        revealItems.forEach((item) => {
            item.classList.add('reveal');
            observer.observe(item);
        });
    } else {
        revealItems.forEach((item) => item.classList.add('is-visible'));
    }
})();
