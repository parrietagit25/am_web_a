<?php
/**
 * Prepara variables globales para unit-payment-social.php desde datos de unidad.
 *
 * @param array<string, mixed> $unitData
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
