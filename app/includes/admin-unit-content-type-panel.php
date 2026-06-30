<?php
/**
 * Panel CRUD para un tipo de contenido (latest | blog | news).
 *
 * @var string $ucType
 * @var string $ucUnitKey
 * @var list<array<string, mixed>> $ucItems
 * @var array{categories: array, tags: array, topics: array} $ucTaxonomy
 */
$ucDomPrefix = 'uc-' . $ucUnitKey . '-' . $ucType;
$ucTypeLabel = UnitContentService::TYPE_LABELS[$ucType] ?? $ucType;
$ucIsBlog = $ucType === 'blog';
$ucIsLatest = $ucType === 'latest';
$ucIsNews = $ucType === 'news';
$ucTabSlug = $ucTabSlug ?? ($ucUnitKey . '-content-' . $ucType);
$ucUnitLabelPanel = UnitContentService::unitLabel($siteData, $ucUnitKey);
$ucHomePathPanel = UnitContentService::unitHomePath($siteData, $ucUnitKey);
$ucTabActive = ($defaultAdminTab ?? '') === $ucTabSlug;
?>
<div class="tab-pane fade<?php echo $ucTabActive ? ' show active' : ''; ?>" id="tab-<?php echo esc($ucTabSlug); ?>" role="tabpanel" aria-labelledby="tab-<?php echo esc($ucTabSlug); ?>-nav">
    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-2 font-montserrat text-navy">
            <i class="bi bi-<?php echo $ucIsBlog ? 'journal-text' : ($ucIsNews ? 'newspaper' : 'lightning-charge'); ?> me-2 text-danger"></i><?php echo esc($ucTypeLabel); ?>
        </h5>
        <p class="text-muted small mb-0">Gestión de <?php echo esc(strtolower($ucTypeLabel)); ?> para <?php echo esc($ucUnitLabelPanel); ?>.</p>
        <p class="text-muted small mt-2 mb-0">
            <i class="bi bi-eye-fill text-success"></i> Active <strong>Mostrar en bloque «más reciente» del home</strong> (o el ojo verde en la tabla) para que aparezca en la grilla de <code><?php echo esc(ltrim($ucHomePathPanel, '/')); ?></code>.
            Máximo: <?php echo intval($ucContent['settings']['latest_home_limit'] ?? 4); ?> ítems en total (mezcla más reciente, blog y noticias).
        </p>
    </div>

    <?php if ($ucIsBlog): ?>
    <div class="admin-card mb-4">
        <h6 class="fw-bold text-navy mb-3"><i class="bi bi-tags me-2 text-danger"></i>Taxonomía del blog</h6>
        <div class="row g-3">
            <?php foreach (['categories' => 'Categorías', 'tags' => 'Etiquetas', 'topics' => 'Temas'] as $taxKind => $taxLabel): ?>
            <div class="col-lg-4">
                <div class="border rounded-3 p-3 h-100 bg-light">
                    <div class="fw-semibold mb-2"><?php echo esc($taxLabel); ?></div>
                    <form method="POST" action="?tab=<?php echo esc($ucTabSlug); ?>" class="d-flex gap-2 mb-2">
                        <input type="hidden" name="action" value="add_unit_content_taxonomy">
                        <input type="hidden" name="content_unit" value="<?php echo esc($ucUnitKey); ?>">
                        <input type="hidden" name="taxonomy_kind" value="<?php echo esc($taxKind); ?>">
                        <input type="text" name="taxonomy_name" class="form-control form-control-sm" placeholder="Nuevo nombre" required>
                        <button type="submit" class="btn btn-sm btn-premium">+</button>
                    </form>
                    <?php $taxList = $ucTaxonomy[$taxKind] ?? []; ?>
                    <?php if (empty($taxList)): ?>
                        <div class="text-muted small">Sin registros.</div>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0 small">
                            <?php foreach ($taxList as $taxRow): ?>
                            <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                <span><?php echo esc($taxRow['name'] ?? ''); ?></span>
                                <form method="POST" action="?tab=<?php echo esc($ucTabSlug); ?>" class="d-inline" onsubmit="return confirm('¿Eliminar?');">
                                    <input type="hidden" name="action" value="delete_unit_content_taxonomy">
                                    <input type="hidden" name="content_unit" value="<?php echo esc($ucUnitKey); ?>">
                                    <input type="hidden" name="taxonomy_kind" value="<?php echo esc($taxKind); ?>">
                                    <input type="hidden" name="taxonomy_id" value="<?php echo intval($taxRow['id'] ?? 0); ?>">
                                    <button type="submit" class="btn btn-link btn-sm text-danger p-0"><i class="bi bi-x-lg"></i></button>
                                </form>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="<?php echo esc($ucDomPrefix); ?>-form-title">
            <i class="bi bi-file-plus me-2 text-danger"></i>Agregar <?php echo esc($ucTypeLabel); ?>
        </h5>
        <form method="POST" action="?tab=<?php echo esc($ucTabSlug); ?>" enctype="multipart/form-data" id="<?php echo esc($ucDomPrefix); ?>-form">
            <input type="hidden" name="action" id="<?php echo esc($ucDomPrefix); ?>-form-action" value="add_unit_content_item">
            <input type="hidden" name="content_unit" value="<?php echo esc($ucUnitKey); ?>">
            <input type="hidden" name="content_type" value="<?php echo esc($ucType); ?>">
            <input type="hidden" name="content_id" id="<?php echo esc($ucDomPrefix); ?>-id" value="">

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fecha visible</label>
                    <input type="text" name="content_date" id="<?php echo esc($ucDomPrefix); ?>-date" class="form-control form-control-premium" placeholder="Ej: 25 Mayo, 2026" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Título</label>
                    <input type="text" name="content_title" id="<?php echo esc($ucDomPrefix); ?>-title" class="form-control form-control-premium" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug (URL)</label>
                    <input type="text" name="content_slug" id="<?php echo esc($ucDomPrefix); ?>-slug" class="form-control form-control-premium" placeholder="auto si se deja vacío">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Resumen (tarjeta)</label>
                    <input type="text" name="content_excerpt" id="<?php echo esc($ucDomPrefix); ?>-excerpt" class="form-control form-control-premium" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Texto del enlace</label>
                    <input type="text" name="content_link_text" id="<?php echo esc($ucDomPrefix); ?>-link-text" class="form-control form-control-premium" value="Ver Más">
                </div>

                <?php if ($ucIsLatest): ?>
                <div class="col-md-4">
                    <label class="form-label">Subtipo</label>
                    <select name="content_subtype" id="<?php echo esc($ucDomPrefix); ?>-subtype" class="form-select form-control-premium">
                        <?php foreach (UnitContentService::LATEST_SUBTYPES as $subKey => $subLabel): ?>
                        <option value="<?php echo esc($subKey); ?>"><?php echo esc($subLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-md-4">
                    <label class="form-label">Orden</label>
                    <input type="number" name="content_sort_order" id="<?php echo esc($ucDomPrefix); ?>-sort" class="form-control form-control-premium" value="0" min="0">
                </div>

                <?php if ($ucIsNews || $ucIsBlog): ?>
                <div class="col-md-4">
                    <label class="form-label">Publicar desde (opcional)</label>
                    <input type="datetime-local" name="content_publish_from" id="<?php echo esc($ucDomPrefix); ?>-from" class="form-control form-control-premium">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Publicar hasta (opcional)</label>
                    <input type="datetime-local" name="content_publish_until" id="<?php echo esc($ucDomPrefix); ?>-until" class="form-control form-control-premium">
                </div>
                <?php endif; ?>

                <div class="col-md-6">
                    <label class="form-label">Encabezado interno</label>
                    <input type="text" name="content_subheading" id="<?php echo esc($ucDomPrefix); ?>-subheading" class="form-control form-control-premium">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Párrafo introductorio</label>
                    <input type="text" name="content_description" id="<?php echo esc($ucDomPrefix); ?>-description" class="form-control form-control-premium">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Imagen tarjeta</label>
                    <input type="file" name="content_thumbnail" id="<?php echo esc($ucDomPrefix); ?>-thumbnail" class="form-control form-control-premium" accept="image/*" required>
                    <small class="text-muted d-block mt-1">Recomendado: 800×600 px — JPG o WebP</small>
                    <div class="form-text" id="<?php echo esc($ucDomPrefix); ?>-thumb-help"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Banner detalle (opcional)</label>
                    <input type="file" name="content_banner" id="<?php echo esc($ucDomPrefix); ?>-banner" class="form-control form-control-premium" accept="image/*">
                    <small class="text-muted d-block mt-1">Recomendado: 1920×500 px — JPG o WebP</small>
                    <div class="form-text" id="<?php echo esc($ucDomPrefix); ?>-banner-help"></div>
                </div>

                <?php if ($ucIsBlog): ?>
                <div class="col-md-4">
                    <label class="form-label">Categorías</label>
                    <select name="content_category_ids[]" id="<?php echo esc($ucDomPrefix); ?>-categories" class="form-select form-control-premium" multiple size="4">
                        <?php foreach ($ucTaxonomy['categories'] ?? [] as $cat): ?>
                        <option value="<?php echo intval($cat['id']); ?>"><?php echo esc($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Etiquetas</label>
                    <select name="content_tag_ids[]" id="<?php echo esc($ucDomPrefix); ?>-tags" class="form-select form-control-premium" multiple size="4">
                        <?php foreach ($ucTaxonomy['tags'] ?? [] as $tag): ?>
                        <option value="<?php echo intval($tag['id']); ?>"><?php echo esc($tag['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Temas</label>
                    <select name="content_topic_ids[]" id="<?php echo esc($ucDomPrefix); ?>-topics" class="form-select form-control-premium" multiple size="4">
                        <?php foreach ($ucTaxonomy['topics'] ?? [] as $topic): ?>
                        <option value="<?php echo intval($topic['id']); ?>"><?php echo esc($topic['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-12">
                    <label class="form-label">Contenido detallado (HTML permitido)</label>
                    <textarea name="content_body" id="<?php echo esc($ucDomPrefix); ?>-body" class="form-control form-control-premium js-unit-content-editor" rows="10"></textarea>
                    <div class="form-text">Use el editor visual o la vista de código para insertar etiquetas HTML. Se renderiza de forma segura en el sitio público.</div>
                </div>

                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="content_published" id="<?php echo esc($ucDomPrefix); ?>-published" value="1" checked>
                        <label class="form-check-label" for="<?php echo esc($ucDomPrefix); ?>-published">Publicado</label>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="content_show_on_home" id="<?php echo esc($ucDomPrefix); ?>-show-home" value="1" <?php echo $ucIsLatest ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="<?php echo esc($ucDomPrefix); ?>-show-home">Mostrar en bloque «más reciente» del home</label>
                    </div>
                </div>
            </div>

            <div class="text-end mt-4 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary d-none" id="<?php echo esc($ucDomPrefix); ?>-cancel" onclick="resetUnitContentForm('<?php echo esc($ucDomPrefix); ?>')">Cancelar</button>
                <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="<?php echo esc($ucDomPrefix); ?>-submit">
                    <i class="bi bi-plus-lg"></i> <span id="<?php echo esc($ucDomPrefix); ?>-submit-text">Publicar</span>
                </button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-card-text me-2 text-danger"></i><?php echo esc($ucTypeLabel); ?> registrados
        </h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:90px;">Miniatura</th>
                        <th style="width:130px;">Fecha</th>
                        <th>Título</th>
                        <?php if ($ucIsLatest): ?><th>Subtipo</th><?php endif; ?>
                        <th style="width:90px;" class="text-center">Estado</th>
                        <th style="width:100px;" class="text-center">Más reciente</th>
                        <th style="width:110px;" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ucItems)): ?>
                    <tr><td colspan="<?php echo $ucIsLatest ? 7 : 6; ?>" class="text-center py-4 text-muted">Sin registros.</td></tr>
                    <?php else: ?>
                    <?php foreach ($ucItems as $item):
                        $onHome = UnitContentService::showsOnLatestHomeBlock($item, $ucType);
                        $published = !isset($item['published']) || filter_var($item['published'], FILTER_VALIDATE_BOOLEAN);
                    ?>
                    <tr>
                        <td>
                            <?php if (!empty($item['thumbnail'])): ?>
                            <img src="<?php echo esc($item['thumbnail']); ?>" alt="" class="rounded border" style="height:48px;width:72px;object-fit:cover;">
                            <?php else: ?>
                            <div class="bg-light border rounded text-center text-muted" style="height:48px;width:72px;line-height:48px;"><i class="bi bi-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?php echo esc($item['date'] ?? ''); ?></small></td>
                        <td><strong><?php echo esc($item['title'] ?? ''); ?></strong><br><small class="text-muted"><?php echo esc($item['excerpt'] ?? ''); ?></small></td>
                        <?php if ($ucIsLatest): ?>
                        <td><span class="badge bg-light text-dark border"><?php echo esc(UnitContentService::LATEST_SUBTYPES[$item['subtype'] ?? 'promotion'] ?? ''); ?></span></td>
                        <?php endif; ?>
                        <td class="text-center">
                            <span class="badge <?php echo $published ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $published ? 'Publicado' : 'Borrador'; ?></span>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="?tab=<?php echo esc($ucTabSlug); ?>" class="d-inline">
                                <input type="hidden" name="action" value="toggle_unit_content_home">
                                <input type="hidden" name="content_unit" value="<?php echo esc($ucUnitKey); ?>">
                                <input type="hidden" name="content_type" value="<?php echo esc($ucType); ?>">
                                <input type="hidden" name="content_id" value="<?php echo intval($item['id']); ?>">
                                <button type="submit" class="btn btn-sm <?php echo $onHome ? 'btn-success' : 'btn-outline-secondary'; ?>" title="<?php echo $onHome ? 'Visible en bloque más reciente' : 'Oculto del bloque más reciente'; ?>">
                                    <i class="bi <?php echo $onHome ? 'bi-eye-fill' : 'bi-eye-slash'; ?>"></i>
                                </button>
                            </form>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditUnitContent("<?php echo esc($ucDomPrefix); ?>", <?php echo json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                <form method="POST" action="?tab=<?php echo esc($ucTabSlug); ?>" onsubmit="return confirm('¿Eliminar este contenido?');" class="d-inline">
                                    <input type="hidden" name="action" value="delete_unit_content_item">
                                    <input type="hidden" name="content_unit" value="<?php echo esc($ucUnitKey); ?>">
                                    <input type="hidden" name="content_type" value="<?php echo esc($ucType); ?>">
                                    <input type="hidden" name="content_id" value="<?php echo intval($item['id']); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash3-fill"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
