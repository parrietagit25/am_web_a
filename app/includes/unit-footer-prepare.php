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
    global $_upsUnitSocialLinks, $_upsShowPayments, $_upsUnitContact;

    $_upsUnitSocialLinks = is_array($unitData['social_links'] ?? null) ? $unitData['social_links'] : [];
    $_upsShowPayments = ($unitData['show_payment_methods'] ?? true) !== false;
    $_upsUnitContact = am_unit_footer_contact_array($unitData);
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
 * Datos de contacto para el topbar según unidad activa.
 *
 * @param array<string, mixed> $siteGlobal
 * @return array<string, mixed>
 */
function am_unit_topbar_contact(string $unitKey, ContentService $cs, array $siteGlobal): array
{
    $unitData = am_unit_site_data_from_service($cs, $unitKey);
    $footer = am_unit_footer_contact_array($unitData);

    $globalPhone = trim((string) ($siteGlobal['phone_display'] ?? '(507) 279-2700'));
    $phone = trim((string) ($footer['phone_display'] ?? ''));
    if ($phone === '') {
        $phone = $globalPhone;
    }

    $whatsappDigits = preg_replace('/\D/', '', (string) ($footer['whatsapp_number'] ?? ''));
    if ($whatsappDigits === '') {
        $whatsappDigits = preg_replace('/\D/', '', (string) ($siteGlobal['whatsapp_number'] ?? '5072792700'));
    }

    $email = trim((string) ($footer['email'] ?? ''));
    if ($email === '') {
        $email = trim((string) ($siteGlobal['email'] ?? ''));
    }

    $tollFree = trim((string) ($siteGlobal['toll_free'] ?? '1-866-700-9904'));

    return [
        'phone_display' => $phone,
        'phone_tel' => preg_replace('/\D/', '', $phone),
        'whatsapp_digits' => $whatsappDigits,
        'email' => $email,
        'toll_free' => $tollFree,
        'toll_free_tel' => preg_replace('/\D/', '', $tollFree),
        'uses_unit_phone' => trim((string) ($footer['phone_display'] ?? '')) !== '',
        'uses_unit_whatsapp' => trim((string) ($footer['whatsapp_number'] ?? '')) !== '',
        'uses_unit_email' => trim((string) ($footer['email'] ?? '')) !== '',
    ];
}
