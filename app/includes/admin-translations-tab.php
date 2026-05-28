<?php
/**
 * Admin tab — Translation dictionary
 */
require_once __DIR__ . '/../services/TranslationService.php';
$translationDictionary = TranslationService::getInstance()->getDictionaryForAdmin();
?>
<div class="tab-pane fade" id="tab-translations" role="tabpanel" aria-labelledby="tab-translations-nav">
    <div class="admin-card">
        <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-translate me-2 text-danger"></i>Diccionario de Traducciones
        </h5>
        <p class="text-muted small mb-4">
            Edite el texto en español e inglés. Los cambios se aplican en todo el sitio público al seleccionar el idioma.
            Las claves no deben modificarse; solo los textos de cada columna.
        </p>

        <form method="POST" action="?tab=translations">
            <input type="hidden" name="action" value="save_translations">

            <div class="mb-3">
                <input type="text" id="translationSearch" class="form-control form-control-premium" placeholder="Buscar por clave o texto...">
            </div>

            <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                <table class="table table-sm table-hover align-middle" id="translationsTable">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="min-width:220px;">Clave</th>
                            <th style="min-width:280px;">Español</th>
                            <th style="min-width:280px;">English</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($translationDictionary as $key => $row): ?>
                        <tr class="translation-row">
                            <td class="small text-muted font-monospace translation-key"><?php echo esc($key); ?></td>
                            <td>
                                <input type="hidden" name="trans_keys[]" value="<?php echo esc($key); ?>">
                                <textarea name="trans_es[]" class="form-control form-control-sm form-control-premium" rows="2"><?php echo esc($row['es'] ?? ''); ?></textarea>
                            </td>
                            <td>
                                <textarea name="trans_en[]" class="form-control form-control-sm form-control-premium" rows="2"><?php echo esc($row['en'] ?? ''); ?></textarea>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                    <i class="bi bi-save"></i> Guardar diccionario
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var input = document.getElementById('translationSearch');
    if (!input) return;
    input.addEventListener('input', function() {
        var q = this.value.toLowerCase().trim();
        document.querySelectorAll('#translationsTable .translation-row').forEach(function(row) {
            var text = row.innerText.toLowerCase();
            row.style.display = (!q || text.indexOf(q) !== -1) ? '' : 'none';
        });
    });
})();
</script>
