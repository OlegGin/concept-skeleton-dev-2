/**
 * Frontend scripts for CONCEPT.
 */

document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.navbar');

    /**
     * Keep flash toasts below the fixed navbar (height changes on scroll).
     */
    const syncSiteHeaderHeight = function() {
        if (!navbar) {
            return;
        }
        document.documentElement.style.setProperty(
            '--site-header-height',
            navbar.getBoundingClientRect().height + 'px'
        );
    };

    /**
     * Handle navbar transparency on scroll
     */
    const handleNavbarScroll = function() {
        if (navbar) {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            syncSiteHeaderHeight();
        }
    };

    syncSiteHeaderHeight();
    handleNavbarScroll();

    window.addEventListener('scroll', handleNavbarScroll);
    window.addEventListener('resize', syncSiteHeaderHeight);

    if (typeof hljs !== 'undefined') {
        hljs.highlightAll();
    }

    initProviderBoard();
    initSmoothScroll();
});

/**
 * Smooth scroll for in-page anchor links (fixed navbar offset handled by scroll-margin in CSS).
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            var id = this.getAttribute('href');
            if (!id || id.length < 2) {
                return;
            }

            var target = document.querySelector(id);
            if (!target) {
                return;
            }

            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
}

/**
 * Interactive service provider toggles with live app.php preview.
 */
function initProviderBoard() {
    const board = document.getElementById('provider-board');
    const codeEl = document.getElementById('provider-code');

    if (!board || !codeEl) {
        return;
    }

    const renderProviderCode = function() {
        const lines = ['return ['];

        board.querySelectorAll('.provider-toggle').forEach(function(toggle) {
            if (!toggle.checked) {
                return;
            }

            lines.push('    ' + toggle.dataset.provider + '::class,');
        });

        lines.push('];');
        codeEl.textContent = lines.join('\n');
        codeEl.removeAttribute('data-highlighted');

        if (typeof hljs !== 'undefined') {
            hljs.highlightElement(codeEl);
        }
    };

    const syncChipState = function(toggle) {
        const chip = toggle.closest('.provider-chip');
        if (!chip) {
            return;
        }

        chip.classList.toggle('provider-chip--on', toggle.checked);
        chip.classList.toggle('provider-chip--off', !toggle.checked);
    };

    const getExclusiveGroupToggles = function(toggle) {
        const group = toggle.dataset.exclusiveGroup;
        if (!group) {
            return [];
        }

        return Array.from(
            board.querySelectorAll('.provider-toggle[data-exclusive-group="' + group + '"]')
        );
    };

    const applyExclusiveGroupRules = function(changedToggle) {
        const groupToggles = getExclusiveGroupToggles(changedToggle);
        if (groupToggles.length === 0) {
            return;
        }

        if (changedToggle.checked) {
            groupToggles.forEach(function(toggle) {
                if (toggle !== changedToggle) {
                    toggle.checked = false;
                    syncChipState(toggle);
                }
            });
            return;
        }

        const anyChecked = groupToggles.some(function(toggle) {
            return toggle.checked;
        });

        if (!anyChecked) {
            changedToggle.checked = true;
        }
    };

    board.querySelectorAll('.provider-toggle').forEach(function(toggle) {
        syncChipState(toggle);
        toggle.addEventListener('change', function() {
            applyExclusiveGroupRules(toggle);
            syncChipState(toggle);
            renderProviderCode();
        });
    });

    renderProviderCode();
}
