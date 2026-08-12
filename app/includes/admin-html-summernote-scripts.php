<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-es-ES.min.js"></script>
<script>
(function () {
    'use strict';

    var ADMIN_HTML_EDITOR_TOOLBAR = [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'clear']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['insert', ['link', 'table']],
        ['edit', ['undo', 'redo']],
        ['view', ['codeview', 'fullscreen']]
    ];

    function adminHtmlEditorIsActive(el) {
        return window.jQuery && jQuery(el).next('.note-editor').length > 0;
    }

    window.adminHtmlEditorSetValue = function (id, html) {
        var el = document.getElementById(id);
        if (!el) return;
        var value = html || '';
        if (adminHtmlEditorIsActive(el)) {
            jQuery(el).summernote('code', value);
        } else {
            el.value = value;
        }
    };

    window.adminHtmlEditorSync = function (el) {
        if (!el || !adminHtmlEditorIsActive(el)) return;
        el.value = jQuery(el).summernote('code');
    };

    window.adminHtmlEditorSyncAll = function () {
        if (!window.jQuery) return;
        jQuery('.js-admin-html-editor').each(function () {
            adminHtmlEditorSync(this);
        });
    };

    function initAdminHtmlEditor(el) {
        if (!window.jQuery || !jQuery.fn.summernote) return;
        var $ta = jQuery(el);
        if ($ta.next('.note-editor').length) return;

        var height = parseInt(el.getAttribute('data-admin-html-height') || '350', 10);
        if (isNaN(height) || height < 200) height = 350;

        $ta.summernote({
            lang: 'es-ES',
            height: height,
            placeholder: 'Escriba o pegue contenido (acepta HTML; use Vista código para etiquetas avanzadas)...',
            toolbar: ADMIN_HTML_EDITOR_TOOLBAR,
            codeviewFilter: false,
            codeviewIframeFilter: false,
            disableDragAndDrop: false,
            callbacks: {
                onInit: function () {
                    var initial = $ta.val();
                    if (initial && initial.trim() !== '') {
                        $ta.summernote('code', initial);
                    }
                }
            }
        });
    }

    window.initAdminHtmlEditors = function () {
        document.querySelectorAll('.js-admin-html-editor').forEach(initAdminHtmlEditor);
    };

    document.addEventListener('DOMContentLoaded', function () {
        initAdminHtmlEditors();

        window.amAdminEncodePostedValue = function (raw) {
            var html = raw == null ? '' : String(raw);
            if (html === '' || html.indexOf('b64:') === 0) {
                return html;
            }
            try {
                return 'b64:' + btoa(unescape(encodeURIComponent(html)));
            } catch (e) {
                return html;
            }
        };

        document.querySelectorAll('form').forEach(function (form) {
            if (!form.querySelector('.js-admin-html-editor')) return;
            form.addEventListener('submit', function () {
                form.querySelectorAll('.js-admin-html-editor').forEach(function (el) {
                    adminHtmlEditorSync(el);
                    // Evitar que Summernote reescriba HTML crudo tras el encode (WAF/Cloudflare)
                    if (adminHtmlEditorIsActive(el)) {
                        jQuery(el).summernote('destroy');
                    }
                    el.value = window.amAdminEncodePostedValue(el.value || '');
                });
            });
        });

        var sidebarTabs = document.getElementById('adminSidebarTabs');
        if (sidebarTabs) {
            sidebarTabs.addEventListener('shown.bs.tab', function () {
                initAdminHtmlEditors();
            });
        }
    });
})();
</script>
