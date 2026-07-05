<?php
/**
 * Fixes navegación del sidebar en páginas admin standalone (sin tab-panes de index.php).
 * AM-DASH-ADMIN-AVANCES-1C
 */
?>
<style>
    /* Sidebar por encima de modal-backdrop huérfano (Bootstrap z-index ~1050) */
    .admin-sidebar {
        position: relative;
        z-index: 1070;
    }
    /* Modales ocultos no interceptan clicks fuera del contenido principal */
    .admin-standalone-modals-root {
        pointer-events: none;
    }
    .admin-standalone-modals-root .modal.show {
        pointer-events: auto;
    }
</style>
<script>
(function () {
    function cleanupModalArtifacts() {
        document.querySelectorAll('.modal-backdrop').forEach(function (el) {
            el.remove();
        });
        if (!document.querySelector('.modal.show')) {
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        }
    }

    cleanupModalArtifacts();

    var sidebar = document.querySelector('.admin-sidebar');
    if (sidebar) {
        sidebar.addEventListener('click', function (event) {
            var pageLink = event.target.closest('a.admin-sidebar-page-link[href]');
            if (pageLink) {
                return;
            }

            var tabBtn = event.target.closest('[data-bs-target^="#tab-"]');
            if (!tabBtn) {
                return;
            }

            var targetSel = tabBtn.getAttribute('data-bs-target');
            if (!targetSel || document.querySelector(targetSel)) {
                return;
            }

            var slug = targetSel.slice(5);
            if (!slug) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            window.location.href = '/admin/?tab=' + encodeURIComponent(slug);
        }, true);
    }

    var modalsRoot = document.getElementById('ppd-modals-root');
    var modalNodes = modalsRoot
        ? modalsRoot.querySelectorAll('.modal')
        : document.querySelectorAll('.admin-standalone-modals-root .modal');
    modalNodes.forEach(function (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            window.setTimeout(cleanupModalArtifacts, 0);
        });
    });
})();
</script>
