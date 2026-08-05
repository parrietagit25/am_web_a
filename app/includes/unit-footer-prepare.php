<?php
/**
 * Helpers de contacto por unidad (footer, topbar y páginas de contacto).
 */

/**
 * @return array<string, mixed>
 */
function am_unit_site_data_from_service(ContentService $cs, string $unitKey): array
{
    if ($unitKey === 'rentacar') {
        return $cs->get('homepage', []);
    }

    return $cs->get($unitKey, []);
}

/**
 * @param array<string, mixed> $unitData
 * @return array<string, mixed>
 */
function am_unit_footer_contact_array(array $unitData): array
{
    if (!empty($unitData['footer_contact']) && is_array($unitData['footer_contact'])) {
        return $unitData['footer_contact'];
    }

    $contact = $unitData['contact'] ?? [];
    if (!is_array($contact)) {
        return [];
    }

    if (isset($contact['messages']) || isset($contact['contact_emails']) || isset($contact['phone_1'])) {
        return [];
    }

    return $contact;
}

/**
 * @param array<string, mixed> $unitData
 */
function am_unit_footer_prepare(array $unitData): void
{
    global $_upsUnitSocialLinks, $_upsShowPayments, $_upsUnitContact, $_upsUnitData;

    $_upsUnitSocialLinks = is_array($unitData['social_links'] ?? null) ? $unitData['social_links'] : [];
    $_upsShowPayments = ($unitData['show_payment_methods'] ?? true) !== false;
    $_upsUnitContact = am_unit_footer_contact_array($unitData);
    $_upsUnitData = $unitData;
}

/**
 * Combina contacto de página con footer_contact sin sobrescribir campos explícitos.
 *
 * @param array<string, mixed> $pageContact
 * @param array<string, mixed> $unitData
 * @return array<string, mixed>
 */
function am_unit_contact_with_footer_fallback(array $pageContact, array $unitData): array
{
    $footer = am_unit_footer_contact_array($unitData);
    $merged = $pageContact;

    foreach (['phone_display', 'whatsapp_number', 'email', 'schedule'] as $key) {
        $pageVal = trim((string) ($merged[$key] ?? ''));
        $footerVal = trim((string) ($footer[$key] ?? ''));
        if ($pageVal === '' && $footerVal !== '') {
            $merged[$key] = $footer[$key];
        }
    }

    return $merged;
}

/**
 * Contacto listo para mostrar en páginas públicas (con fallback global).
 *
 * @param array<string, mixed> $contact
 * @param array<string, mixed> $siteGlobal
 * @return array<string, mixed>
 */
function am_unit_contact_resolved_for_display(array $contact, array $siteGlobal): array
{
    $phone = trim((string) ($contact['phone_display'] ?? ''));
    if ($phone === '') {
        $phone = trim((string) ($siteGlobal['phone_display'] ?? '(507) 279-2700'));
    }

    $phone2 = trim((string) ($contact['phone_2'] ?? ''));

    $waRaw = trim((string) ($contact['whatsapp_number'] ?? ''));
    $waDigits = preg_replace('/\D/', '', $waRaw);
    if ($waDigits === '') {
        $waDigits = preg_replace('/\D/', '', (string) ($siteGlobal['whatsapp_number'] ?? '50767470070'));
        $waLabel = '(507) 6747-0070';
    } else {
        $waLabel = $waRaw !== '' ? $waRaw : $waDigits;
    }

    $email = trim((string) ($contact['email'] ?? ''));
    if ($email === '') {
        $email = trim((string) ($siteGlobal['email'] ?? ''));
    }

    $schedule = trim((string) ($contact['schedule'] ?? ''));

    return [
        'phone_display' => $phone,
        'phone_tel' => preg_replace('/\D/', '', $phone),
        'phone_2_display' => $phone2,
        'phone_2_tel' => preg_replace('/\D/', '', $phone2),
        'whatsapp_digits' => $waDigits,
        'whatsapp_label' => $waLabel,
        'email' => $email,
        'schedule' => $schedule,
    ];
}

/**
 * Datos de contacto para el topbar según unidad activa.
 * Solo usa campos guardados en topbar de la unidad (sin fallback a pie/global).
 * Campo vacío = no mostrar en frontend. enabled=false = franja sin letras.
 *
 * @param array<string, mixed> $siteGlobal
 * @return array<string, mixed>
 */
function am_unit_topbar_array_from_unit_data(array $unitData, string $unitKey = '', array $siteGlobal = []): array
{
    $topbar = $unitData['topbar'] ?? null;
    if ((!is_array($topbar) || $topbar === []) && $unitKey !== '') {
        $custom = $siteGlobal['business_units'][$unitKey]['topbar'] ?? null;
        if (is_array($custom)) {
            $topbar = $custom;
        }
    }

    return is_array($topbar) ? $topbar : [];
}

/**
 * @param array<string, mixed> $siteGlobal
 * @return array<string, mixed>
 */
function am_unit_topbar_contact(string $unitKey, ContentService $cs, array $siteGlobal): array
{
    $unitData = am_unit_site_data_from_service($cs, $unitKey);
    $topbar = am_unit_topbar_array_from_unit_data($unitData, $unitKey, $siteGlobal);

    // Ausente = activo (compatibilidad con topbars ya guardados).
    $enabled = !array_key_exists('enabled', $topbar) || filter_var($topbar['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);

    $phone = trim((string) ($topbar['phone_display'] ?? ''));
    $whatsappRaw = trim((string) ($topbar['whatsapp_number'] ?? ''));
    $whatsappDigits = preg_replace('/\D/', '', $whatsappRaw) ?? '';
    $email = trim((string) ($topbar['email'] ?? ''));
    $tollFree = trim((string) ($topbar['toll_free'] ?? ''));
    $promoText = trim((string) ($topbar['promo_text'] ?? ''));

    return [
        'enabled' => $enabled,
        'promo_text' => $promoText,
        'phone_display' => $phone,
        'phone_tel' => preg_replace('/\D/', '', $phone) ?? '',
        'whatsapp_digits' => $whatsappDigits,
        'email' => $email,
        'toll_free' => $tollFree,
        'toll_free_tel' => preg_replace('/\D/', '', $tollFree) ?? '',
    ];
}
