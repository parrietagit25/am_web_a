/**
 * Header dropdowns — custom toggle (no Bootstrap Dropdown).
 * Stays open until the user selects an option or clicks outside.
 */
(function () {
    'use strict';

    var ROOT = '.site-header-stack';
    var documentClickBound = false;

    function closeAll() {
        document.querySelectorAll(ROOT + ' .dropdown.show').forEach(function (dropdown) {
            dropdown.classList.remove('show');
            var toggle = dropdown.querySelector('[data-am-dropdown-toggle]');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function openDropdown(dropdown) {
        document.querySelectorAll(ROOT + ' .dropdown.show').forEach(function (other) {
            if (other !== dropdown) {
                other.classList.remove('show');
                var otherToggle = other.querySelector('[data-am-dropdown-toggle]');
                if (otherToggle) {
                    otherToggle.setAttribute('aria-expanded', 'false');
                }
            }
        });
        dropdown.classList.add('show');
        var toggle = dropdown.querySelector('[data-am-dropdown-toggle]');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
        }
    }

    function blockBootstrapDropdownEvents() {
        ['hide.bs.dropdown', 'show.bs.dropdown', 'hidden.bs.dropdown', 'shown.bs.dropdown'].forEach(function (evt) {
            document.addEventListener(evt, function (e) {
                var related = e.target;
                if (related && related.closest && related.closest(ROOT + ' .dropdown')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }
            }, true);
        });
    }

    function bindDocumentCloseOnce() {
        if (documentClickBound) {
            return;
        }
        documentClickBound = true;
        blockBootstrapDropdownEvents();

        // Capture: runs before the toggle handler; ignore clicks inside any header dropdown.
        document.addEventListener('click', function (e) {
            if (e.target.closest(ROOT + ' .dropdown')) {
                return;
            }
            closeAll();
        }, true);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAll();
            }
        });
    }

    function initCustomDropdowns() {
        bindDocumentCloseOnce();

        document.querySelectorAll(ROOT + ' .dropdown').forEach(function (dropdown) {
            var toggle = dropdown.querySelector('[data-am-dropdown-toggle]');
            if (!toggle || toggle.dataset.amDropdownBound === '1') {
                return;
            }
            toggle.dataset.amDropdownBound = '1';

            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                if (dropdown.classList.contains('show')) {
                    closeAll();
                } else {
                    openDropdown(dropdown);
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCustomDropdowns);
    } else {
        initCustomDropdowns();
    }
})();
