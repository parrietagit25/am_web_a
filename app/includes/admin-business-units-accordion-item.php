<?php
/**
 * Un acordeón de una unidad de negocio en admin global.
 * Requiere: $key (string), $unit (array).
 */
$isCustom = !empty($unit['is_custom']);
$collapseId = 'collapse-' . preg_replace('/[^a-z0-9_-]/i', '-', $key);
?>
<div class="accordion-item border rounded-3 mb-2 overflow-hidden bu-unit-item" data-unit-key="<?php echo esc($key); ?>" data-is-custom="<?php echo $isCustom ? '1' : '0'; ?>">
    <h2 class="accordion-header d-flex align-items-stretch">
        <span class="bu-unit-handle d-flex align-items-center px-3 text-muted bg-white border-end" title="Arrastrar unidad">
            <i class="bi bi-grip-vertical fs-5"></i>
        </span>
        <button class="accordion-button collapsed fw-bold text-navy-light flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc($collapseId); ?>">
            <span class="badge me-3" style="background-color: <?php echo esc($unit['color']); ?>; width: 15px; height: 15px; border-radius: 50%; padding: 0;"> </span>
            <span class="bu-unit-title-label"><?php echo esc($unit['label']); ?></span>
            <?php if ($isCustom): ?>
                <span class="badge bg-info-subtle text-info border ms-2">Personalizada</span>
            <?php endif; ?>
        </button>
        <?php if ($isCustom): ?>
        <button type="button" class="btn btn-sm btn-outline-danger rounded-0 px-3 bu-unit-delete-btn" data-unit="<?php echo esc($key); ?>" title="Eliminar unidad">
            <i class="bi bi-trash"></i>
        </button>
        <?php endif; ?>
    </h2>
    <div id="<?php echo esc($collapseId); ?>" class="accordion-collapse collapse" data-bs-parent="#businessUnitsAccordion">
        <div class="accordion-body bg-light-gray p-4">
            <div class="row g-3">
                <?php if ($isCustom): ?>
                <input type="hidden" name="business_units[<?php echo esc($key); ?>][is_custom]" value="1">
                <div class="col-md-4">
                    <label class="form-label">Clave interna</label>
                    <input type="text" class="form-control form-control-premium bg-white" value="<?php echo esc($key); ?>" readonly>
                    <div class="form-text">Identificador único (no editable).</div>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Página principal (slug / URL)</label>
                    <input type="text" name="business_units[<?php echo esc($key); ?>][slug]" class="form-control form-control-premium bg-white bu-unit-slug-input" value="<?php echo esc($unit['slug'] ?? ''); ?>" required>
                    <div class="form-text">Por defecto: <code>unidad.php?u=<?php echo esc($key); ?></code></div>
                </div>
                <?php endif; ?>

                <div class="col-md-4">
                    <label class="form-label">Etiqueta de Menú Superior</label>
                    <input type="text" name="business_units[<?php echo esc($key); ?>][label]" class="form-control form-control-premium bg-white bu-unit-label-input" value="<?php echo esc($unit['label']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sub-título del Logo (Header)</label>
                    <input type="text" name="business_units[<?php echo esc($key); ?>][logo_subtitle]" class="form-control form-control-premium bg-white" value="<?php echo esc($unit['logo_subtitle']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Color de Tema (Hexadecimal)</label>
                    <div class="d-flex gap-2">
                        <input type="color" name="business_units[<?php echo esc($key); ?>][color]" class="form-control form-control-color bu-unit-color-input" value="<?php echo esc($unit['color']); ?>" required style="height: 43px; width: 60px;">
                        <input type="text" class="form-control form-control-premium bg-white flex-grow-1 bu-unit-color-text" value="<?php echo esc($unit['color']); ?>" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Título Hero Principal</label>
                    <input type="text" name="business_units[<?php echo esc($key); ?>][heroTitle]" class="form-control form-control-premium bg-white" value="<?php echo esc($unit['heroTitle'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Subtítulo Hero Principal</label>
                    <input type="text" name="business_units[<?php echo esc($key); ?>][heroSubtitle]" class="form-control form-control-premium bg-white" value="<?php echo esc($unit['heroSubtitle'] ?? ''); ?>">
                </div>

                <?php require __DIR__ . '/admin-business-units-menu-list.php'; ?>
            </div>
        </div>
    </div>
</div>
