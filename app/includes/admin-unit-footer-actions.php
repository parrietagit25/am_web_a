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

    if (!isset($siteData[$ufUnitKey]) || !is_array($siteData[$ufUnitKey])) {
        $siteData[$ufUnitKey] = [];
    }

    $siteData[$ufUnitKey]['footer_contact'] = [
        'phone_display'   => trim($_POST['unit_footer_phone'] ?? ''),
        'whatsapp_number' => preg_replace('/\D/', '', $_POST['unit_footer_whatsapp'] ?? ''),
        'email'           => trim($_POST['unit_footer_email'] ?? ''),
        'schedule'        => trim($_POST['unit_footer_schedule'] ?? ''),
    ];
    $siteData[$ufUnitKey]['show_payment_methods'] = isset($_POST['unit_show_payment_methods']) && $_POST['unit_show_payment_methods'] === '1';

    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Contacto y medios de pago guardados correctamente.';
    } else {
        $errorMsg = 'Error al guardar contacto y medios de pago.';
    }
}
