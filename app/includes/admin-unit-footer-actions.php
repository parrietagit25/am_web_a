<?php
/**
 * Guardado de contacto inferior y medios de pago por unidad (3B2).
 *
 * @var string $action
 * @var array<string, mixed> $siteData
 * @var ContentService $contentService
 * @var string $successMsg
 * @var string $errorMsg
 */

$ufActionMap = [
    'save_seminuevos_unit_footer' => 'seminuevos',
    'save_leasing_unit_footer'    => 'leasing',
    'save_renting_unit_footer'    => 'renting',
    'save_taller_unit_footer'     => 'taller',
];

if (isset($ufActionMap[$action])) {
    $ufUnitKey = $ufActionMap[$action];
    require_once __DIR__ . '/../services/WhatsappContextService.php';

    $ufWhatsappRaw = trim((string) ($_POST['unit_footer_whatsapp'] ?? ''));
    $ufWhatsapp = WhatsappContextService::normalizePhone($ufWhatsappRaw);
    try {
        $ufWhatsappMessage = WhatsappContextService::normalizeMessage(
            (string) ($_POST['unit_footer_whatsapp_message'] ?? '')
        );
    } catch (InvalidArgumentException $e) {
        $ufWhatsappMessage = '';
        $errorMsg = $e->getMessage();
    }
    if ($ufWhatsappRaw !== '' && $ufWhatsapp === '') {
        $errorMsg = $errorMsg ?: 'El número de WhatsApp debe contener entre 8 y 15 dígitos y no admite letras ni URLs.';
    }

    if (empty($errorMsg) && (!isset($siteData[$ufUnitKey]) || !is_array($siteData[$ufUnitKey]))) {
        $siteData[$ufUnitKey] = [];
    }

    if (empty($errorMsg)) {
        $siteData[$ufUnitKey]['footer_contact'] = [
            'phone_display'      => trim($_POST['unit_footer_phone'] ?? ''),
            'whatsapp_number'    => $ufWhatsapp,
            'whatsapp_enabled'   => !empty($_POST['unit_footer_whatsapp_enabled']),
            'whatsapp_message'   => $ufWhatsappMessage,
            'email'              => trim($_POST['unit_footer_email'] ?? ''),
            'schedule'           => trim($_POST['unit_footer_schedule'] ?? ''),
        ];
        $siteData[$ufUnitKey]['show_payment_methods'] = isset($_POST['unit_show_payment_methods']) && $_POST['unit_show_payment_methods'] === '1';
    }

    if (empty($errorMsg) && $contentService->saveAll($siteData)) {
        $successMsg = 'Contacto y medios de pago guardados correctamente.';
    } elseif (empty($errorMsg)) {
        $errorMsg = 'Error al guardar contacto y medios de pago.';
    }
}
