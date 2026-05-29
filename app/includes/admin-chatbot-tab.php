<?php
require_once __DIR__ . '/../services/ChatbotService.php';

$chatbotCfg = ChatbotService::mergeConfig($siteData['global'] ?? []);
$chatbotModels = ChatbotService::allowedModels();
$chatbotApiConfigured = ChatbotService::getApiKey() !== '';
$chatbotOperational = ChatbotService::isOperational($chatbotCfg);

$suggestionsEs = $chatbotCfg['suggested_questions_es'] ?? [];
$suggestionsEn = $chatbotCfg['suggested_questions_en'] ?? [];
if (!is_array($suggestionsEs)) {
    $suggestionsEs = [];
}
if (!is_array($suggestionsEn)) {
    $suggestionsEn = [];
}
?>
<div class="tab-pane fade" id="tab-chatbot" role="tabpanel" aria-labelledby="tab-chatbot-nav">
    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-sliders me-2 text-danger"></i>Configuración del chatbot
        </h5>
        <p class="text-muted small mb-3">
            Asistente virtual en el sitio público. Las respuestas usan OpenAI con contexto de Automarket (unidades de negocio, contacto y páginas clave).
        </p>

        <?php if (!$chatbotApiConfigured): ?>
            <div class="alert alert-warning small mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Defina <code>OPENAI_API_KEY</code> en <code>app/config/config.php</code> para activar las respuestas.
            </div>
        <?php elseif ($chatbotCfg['enabled'] && $chatbotOperational): ?>
            <div class="alert alert-success small mb-3">
                <i class="bi bi-check-circle-fill me-1"></i> Chatbot activo en el sitio público.
            </div>
        <?php elseif ($chatbotCfg['enabled']): ?>
            <div class="alert alert-info small mb-3">Activado en configuración; falta la API key de OpenAI.</div>
        <?php else: ?>
            <div class="alert alert-secondary small mb-3">El chatbot está desactivado para los visitantes.</div>
        <?php endif; ?>

        <form method="POST" action="?tab=chatbot">
            <input type="hidden" name="action" value="save_chatbot">

            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="chatbot_enabled" name="chatbot_enabled" value="1" <?php echo !empty($chatbotCfg['enabled']) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold" for="chatbot_enabled">Mostrar chatbot en el sitio web</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="chatbot_assistant_name">Nombre del asistente</label>
                    <input type="text" id="chatbot_assistant_name" name="chatbot_assistant_name" class="form-control form-control-premium"
                           value="<?php echo esc($chatbotCfg['assistant_name'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="chatbot_model">Modelo OpenAI</label>
                    <select id="chatbot_model" name="chatbot_model" class="form-select form-control-premium">
                        <?php foreach ($chatbotModels as $modelKey => $modelLabel): ?>
                            <option value="<?php echo esc($modelKey); ?>" <?php echo ($chatbotCfg['model'] ?? '') === $modelKey ? 'selected' : ''; ?>><?php echo esc($modelLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="chatbot_max_tokens">Máx. tokens respuesta</label>
                    <input type="number" id="chatbot_max_tokens" name="chatbot_max_tokens" class="form-control form-control-premium"
                           min="100" max="2000" step="50" value="<?php echo (int) ($chatbotCfg['max_tokens'] ?? 700); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="chatbot_temperature">Temperatura (0–1.5)</label>
                    <input type="number" id="chatbot_temperature" name="chatbot_temperature" class="form-control form-control-premium"
                           min="0" max="1.5" step="0.1" value="<?php echo esc((string) ($chatbotCfg['temperature'] ?? 0.6)); ?>">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="chatbot_welcome_es">Mensaje de bienvenida (ES)</label>
                    <textarea id="chatbot_welcome_es" name="chatbot_welcome_es" rows="3" class="form-control form-control-premium"><?php echo esc($chatbotCfg['welcome_message_es'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="chatbot_welcome_en">Mensaje de bienvenida (EN)</label>
                    <textarea id="chatbot_welcome_en" name="chatbot_welcome_en" rows="3" class="form-control form-control-premium"><?php echo esc($chatbotCfg['welcome_message_en'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="chatbot_system_instructions">Instrucciones adicionales para la IA</label>
                <textarea id="chatbot_system_instructions" name="chatbot_system_instructions" rows="4" class="form-control form-control-premium"
                          placeholder="Ej.: Priorizar leads de leasing corporativo; mencionar horario de atención lun–vie 8am–6pm…"><?php echo esc($chatbotCfg['system_instructions'] ?? ''); ?></textarea>
                <div class="form-text">Se suman al contexto automático del sitio. No incluya datos sensibles.</div>
            </div>

            <div class="row g-3 mb-3 border-top pt-4">
                <div class="col-12">
                    <h6 class="fw-bold text-navy mb-2"><i class="bi bi-volume-up-fill me-2 text-danger"></i>Voz del asistente (lectura en voz alta)</h6>
                    <p class="text-muted small mb-0">Las voces dependen del navegador y del sistema operativo (Windows/Mac). Escriba parte del nombre de la voz que prefiera; el visitante también puede cambiarla en el chat.</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="chatbot_voice_name">Nombre de voz (parcial)</label>
                    <input type="text" id="chatbot_voice_name" name="chatbot_voice_name" class="form-control form-control-premium"
                           value="<?php echo esc($chatbotCfg['voice_name'] ?? ''); ?>"
                           placeholder="Ej.: Sabina, Helena, Google español, Paulina">
                    <div class="form-text">Deje vacío para la voz en español/inglés por defecto del navegador.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="chatbot_voice_rate">Velocidad (0.5–1.5)</label>
                    <input type="number" id="chatbot_voice_rate" name="chatbot_voice_rate" class="form-control form-control-premium"
                           min="0.5" max="1.5" step="0.05" value="<?php echo esc((string) ($chatbotCfg['voice_rate'] ?? 1)); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="chatbot_voice_pitch">Tono (0.5–2)</label>
                    <input type="number" id="chatbot_voice_pitch" name="chatbot_voice_pitch" class="form-control form-control-premium"
                           min="0.5" max="2" step="0.05" value="<?php echo esc((string) ($chatbotCfg['voice_pitch'] ?? 1)); ?>">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label" for="chatbot_suggestions_es">Preguntas sugeridas (ES, una por línea)</label>
                    <textarea id="chatbot_suggestions_es" name="chatbot_suggestions_es" rows="5" class="form-control form-control-premium"><?php echo esc(implode("\n", $suggestionsEs)); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="chatbot_suggestions_en">Preguntas sugeridas (EN, una por línea)</label>
                    <textarea id="chatbot_suggestions_en" name="chatbot_suggestions_en" rows="5" class="form-control form-control-premium"><?php echo esc(implode("\n", $suggestionsEn)); ?></textarea>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-premium"><i class="bi bi-save me-2"></i>Guardar chatbot</button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h6 class="fw-bold text-navy mb-3"><i class="bi bi-info-circle me-2 text-danger"></i>Notas técnicas</h6>
        <ul class="small text-muted mb-0">
            <li>Endpoint: <code>POST /api/chat.php</code> — contexto en sesión PHP; cada mensaje se guarda en BD (ver <strong>Historial de sesiones</strong>).</li>
            <li>Límite: 40 mensajes por hora por sesión; contexto de las últimas 12 interacciones.</li>
            <li>El botón flotante aparece encima del botón de WhatsApp en todas las páginas públicas.</li>
            <li><strong>Voz:</strong> Chrome o Edge en HTTPS; permiso de micrófono al iniciar llamada. Configurar voz arriba o en el selector del chat.</li>
            <li><strong>Trámites guiados:</strong> reserva RAC, contacto Seminuevos, Leasing y Renting.</li>
            <li>Recomendado: modelo <strong>GPT-4o mini</strong> por costo y velocidad.</li>
        </ul>
    </div>
</div>
