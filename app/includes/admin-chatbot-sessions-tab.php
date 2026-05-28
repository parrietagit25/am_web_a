<?php
require_once __DIR__ . '/../services/ChatbotSessionService.php';

$chatbotSessionSvc = new ChatbotSessionService();
$chatbotSessionsList = $chatbotSessionSvc->listSessions(250);

$businessUnits = $siteData['global']['business_units'] ?? require __DIR__ . '/../config/business-units.php';
$unitLabels = [];
foreach ($businessUnits as $key => $unit) {
    $unitLabels[$key] = $unit['label'] ?? $key;
}
?>
<div class="tab-pane fade" id="tab-chatbot-sessions" role="tabpanel" aria-labelledby="tab-chatbot-sessions-nav">
    <div class="admin-card">
        <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-chat-left-text-fill me-2 text-danger"></i>Historial de sesiones (Chatbot IA)
        </h5>
        <p class="text-muted small mb-4">
            Conversaciones guardadas de visitantes en el sitio público. Cada «Nueva conversación» en el chat inicia otra sesión.
        </p>

        <?php if (empty($chatbotSessionsList)): ?>
            <p class="text-muted mb-0">Aún no hay sesiones registradas. Aparecerán cuando los visitantes envíen mensajes al chatbot.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle text-sm">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Inicio</th>
                            <th>Última actividad</th>
                            <th>Idioma</th>
                            <th>Unidad</th>
                            <th>Mensajes</th>
                            <th>Primer mensaje del cliente</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chatbotSessionsList as $sess): ?>
                        <?php
                            $unitKey = $sess['active_unit'] ?? '';
                            $unitLabel = $unitKey !== '' ? ($unitLabels[$unitKey] ?? $unitKey) : '—';
                            $preview = trim((string) ($sess['first_user_message'] ?? ''));
                            if (mb_strlen($preview) > 80) {
                                $preview = mb_substr($preview, 0, 80) . '…';
                            }
                            $isActive = empty($sess['ended_at']);
                            $sessId = (int) ($sess['id'] ?? 0);
                        ?>
                        <tr>
                            <td><span class="badge bg-light text-dark border">#<?php echo (int) ($sess['id'] ?? 0); ?></span></td>
                            <td class="text-nowrap small text-muted"><?php echo esc(substr($sess['created_at'] ?? '', 0, 16)); ?></td>
                            <td class="text-nowrap small"><?php echo esc(substr($sess['updated_at'] ?? '', 0, 16)); ?></td>
                            <td><?php echo esc(strtoupper($sess['lang'] ?? 'es')); ?></td>
                            <td><small><?php echo esc($unitLabel); ?></small></td>
                            <td><strong><?php echo (int) ($sess['message_count'] ?? 0); ?></strong></td>
                            <td><small><?php echo esc($preview !== '' ? $preview : '—'); ?></small></td>
                            <td>
                                <?php if ($isActive): ?>
                                    <span class="badge bg-success-subtle text-success">Activa</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary">Cerrada</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <button type="button"
                                    class="btn btn-sm btn-outline-primary rounded-pill me-1 chatbot-session-detail-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#chatbotSessionDetailModal"
                                    data-session-id="<?php echo $sessId; ?>">
                                    <i class="bi bi-eye me-1"></i> Ver conversación
                                </button>
                                <form method="POST" action="?tab=chatbot-sessions" class="d-inline" onsubmit="return confirm('¿Eliminar esta sesión y todos sus mensajes?');">
                                    <input type="hidden" name="action" value="delete_chatbot_session">
                                    <input type="hidden" name="session_id" value="<?php echo (int) ($sess['id'] ?? 0); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="chatbotSessionDetailModal" tabindex="-1" aria-labelledby="chatbotSessionDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-navy" id="chatbotSessionDetailModalLabel">Conversación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <dl class="row small mb-3" id="chatbotSessionMeta"></dl>
                    <div id="chatbotSessionMessages" class="d-flex flex-column gap-2"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function escapeChatHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function renderChatbotSession(data) {
        var meta = document.getElementById('chatbotSessionMeta');
        meta.innerHTML =
            '<dt class="col-sm-3 text-navy">Sesión</dt><dd class="col-sm-9">#' + (data.id || '') + ' · ' + (data.session_token || '') + '</dd>' +
            '<dt class="col-sm-3 text-navy">Idioma</dt><dd class="col-sm-9">' + (data.lang || '—') + '</dd>' +
            '<dt class="col-sm-3 text-navy">Unidad</dt><dd class="col-sm-9">' + (data.active_unit || '—') + '</dd>' +
            '<dt class="col-sm-3 text-navy">Página inicial</dt><dd class="col-sm-9">' + (data.page_url || '—') + '</dd>' +
            '<dt class="col-sm-3 text-navy">Modelo</dt><dd class="col-sm-9">' + (data.model_used || '—') + '</dd>' +
            '<dt class="col-sm-3 text-navy">IP</dt><dd class="col-sm-9">' + (data.ip_address || '—') + '</dd>' +
            '<dt class="col-sm-3 text-navy">Inicio / fin</dt><dd class="col-sm-9">' + (data.created_at || '') + (data.ended_at ? ' → ' + data.ended_at : ' (activa)') + '</dd>';

        var box = document.getElementById('chatbotSessionMessages');
        box.innerHTML = '';
        (data.messages || []).forEach(function (m) {
            var div = document.createElement('div');
            var isUser = m.role === 'user';
            div.className = 'p-3 rounded-3 small ' + (isUser ? 'bg-primary-subtle ms-auto' : 'bg-light border');
            div.style.maxWidth = '95%';
            if (isUser) div.style.alignSelf = 'flex-end';
            div.innerHTML = '<div class="fw-semibold text-navy mb-1">' + (isUser ? 'Cliente' : 'Asistente') +
                ' <span class="text-muted fw-normal">· ' + (m.created_at || '') + '</span></div>' +
                '<div style="white-space:pre-wrap;">' + escapeChatHtml(m.content || '') + '</div>';
            box.appendChild(div);
        });
        if (!(data.messages || []).length) {
            box.innerHTML = '<p class="text-muted small mb-0">Sin mensajes.</p>';
        }
    }

    document.querySelectorAll('.chatbot-session-detail-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var id = btn.getAttribute('data-session-id');
            var box = document.getElementById('chatbotSessionMessages');
            var meta = document.getElementById('chatbotSessionMeta');
            meta.innerHTML = '';
            box.innerHTML = '<p class="text-muted small">Cargando conversación…</p>';
            try {
                var res = await fetch('/admin/chatbot-session-json.php?session_id=' + encodeURIComponent(id), { credentials: 'same-origin' });
                var payload = await res.json();
                if (!res.ok || payload.status !== 'success') {
                    throw new Error(payload.message || 'Error');
                }
                renderChatbotSession(payload.session);
            } catch (err) {
                box.innerHTML = '<p class="text-danger small">' + escapeChatHtml(err.message || 'No se pudo cargar.') + '</p>';
            }
        });
    });
    </script>
</div>
