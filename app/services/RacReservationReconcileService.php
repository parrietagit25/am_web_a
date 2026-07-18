<?php
/**
 * Reconciliación previa al pago (AM-ADJ-14).
 * No crea cobros, no cambia estado a pagado, no integra pasarelas.
 */

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/RacReservationService.php';
require_once __DIR__ . '/RacPublicRateService.php';
require_once __DIR__ . '/RacAddonService.php';
require_once __DIR__ . '/BranchDataService.php';

class RacReservationReconcileService
{
    public const RESULT_ELIGIBLE = 'eligible';
    public const RESULT_REQUIRES_REQUOTE = 'requires_requote';
    public const RESULT_AMOUNT_CHANGED = 'amount_changed';
    public const RESULT_UNAVAILABLE = 'unavailable';
    public const RESULT_NOT_FOUND = 'not_found';
    public const RESULT_STATUS_NOT_ALLOWED = 'status_not_allowed';
    public const RESULT_PROVIDER_UNAVAILABLE = 'provider_unavailable';

    /** Mensaje genérico anti-enumeración. */
    public const GENERIC_NOT_FOUND = 'No encontramos una reserva con esos datos. Verifique el número y el apellido.';

    /**
     * @return array<string, mixed>
     */
    public function reconcile(string $code, string $lastName): array
    {
        $code = strtoupper(trim($code));
        $lastName = trim($lastName);

        if ($code === '' || $lastName === '' || is_array($code) || strlen($code) > 64 || strlen($lastName) > 80) {
            return $this->fail(self::RESULT_NOT_FOUND, self::GENERIC_NOT_FOUND);
        }

        if (preg_match('/[<>\"\']/', $code . $lastName)) {
            return $this->fail(self::RESULT_NOT_FOUND, self::GENERIC_NOT_FOUND);
        }

        $svc = new RacReservationService();
        $row = $svc->findByBarsCode($code);
        if ($row === null || !$this->lastNameMatches($row, $lastName)) {
            return $this->fail(self::RESULT_NOT_FOUND, self::GENERIC_NOT_FOUND);
        }

        $status = strtolower(trim((string) ($row['status'] ?? 'pending')));
        if ($status === 'cancelled') {
            return $this->fail(
                self::RESULT_STATUS_NOT_ALLOWED,
                'Esta reserva no admite pago en línea en su estado actual.',
                $this->publicReservationSummary($row)
            );
        }
        if (!in_array($status, ['pending', 'confirmed'], true)) {
            return $this->fail(
                self::RESULT_STATUS_NOT_ALLOWED,
                'Esta reserva no admite pago en línea en su estado actual.',
                $this->publicReservationSummary($row)
            );
        }

        $storedTotal = round((float) ($row['price_total_estimated'] ?? $row['price_total'] ?? 0), 2);
        $currency = trim((string) ($row['currency'] ?? 'USD')) ?: 'USD';
        $quoteToken = trim((string) ($row['quote_token'] ?? ''));
        $rateType = (($row['rate_type'] ?? 'web') === 'counter') ? 'counter' : 'web';

        $search = [
            'locationCode' => (string) ($row['location_code'] ?? ''),
            'returnLocationCode' => (string) ($row['return_location_code'] ?? $row['location_code'] ?? ''),
            'pickupDate' => (string) ($row['pickup_date'] ?? ''),
            'pickupTime' => (string) ($row['pickup_time'] ?? '10:00'),
            'returnDate' => (string) ($row['return_date'] ?? ''),
            'returnTime' => (string) ($row['return_time'] ?? '10:00'),
            'age' => (string) ($row['driver_age'] ?? '25'),
        ];
        $vehicleCode = strtoupper(trim((string) ($row['vehicle_code'] ?? $row['sipp_code'] ?? '')));

        $extrasInput = $this->extrasFromReservation($row);
        $recalculated = null;
        $quoteValid = null;
        $quoteRefreshed = false;
        $resultCode = self::RESULT_ELIGIBLE;
        $message = 'Monto reconciliado desde la reserva. El pago en línea aún no está disponible.';

        if ($quoteToken !== '' && $vehicleCode !== '' && RacPublicRateService::isBarsPricingEnabled()) {
            $rateSvc = new RacPublicRateService();
            $validation = $rateSvc->validateQuote($quoteToken, array_merge($search, [
                'vehicle_code' => $vehicleCode,
                'sippCode' => $vehicleCode,
            ]));
            $quoteValid = !empty($validation['ok']);

            $preview = $rateSvc->previewTotals(
                $search,
                $vehicleCode,
                $quoteToken,
                $extrasInput,
                $rateType
            );

            if (!empty($preview['ok'])) {
                $recalculated = $preview['totals'] ?? null;
                $quoteRefreshed = !empty($preview['refreshed']);
                if ($quoteRefreshed) {
                    $resultCode = self::RESULT_REQUIRES_REQUOTE;
                    $message = 'La tarifa bloqueada venció; se obtuvo una cotización actualizada solo para consulta. Confirme el monto antes de un pago futuro.';
                }
                $newTotal = isset($recalculated['total']) ? round((float) $recalculated['total'], 2) : null;
                if ($newTotal !== null && abs($newTotal - $storedTotal) > 0.009) {
                    $resultCode = self::RESULT_AMOUNT_CHANGED;
                    $message = 'El monto vigente difiere del registrado en la reserva. Debe confirmarse antes de un pago futuro.';
                }
            } elseif (!$quoteValid) {
                $resultCode = self::RESULT_REQUIRES_REQUOTE;
                $message = 'No fue posible validar la tarifa bloqueada. Consulte nuevamente o contacte a Automarket.';
            }
        }

        $amountDue = $storedTotal;
        if (is_array($recalculated) && isset($recalculated['total'])) {
            // Mostrar vigente para información; no sobrescribe la reserva.
            $amountDue = round((float) $recalculated['total'], 2);
        }

        if ($amountDue <= 0) {
            return $this->fail(
                self::RESULT_UNAVAILABLE,
                'No hay un monto elegible para pago en línea.',
                $this->publicReservationSummary($row)
            );
        }

        return [
            'ok' => true,
            'result' => $resultCode,
            'message' => $message,
            'payment_available' => false,
            'provider_available' => false,
            'prepayment_available' => false,
            'payment_provider_available' => false,
            'online_payment_available' => false,
            'reservation_modified' => false,
            'payment_created' => false,
            'reservation' => $this->publicReservationSummary($row),
            'rate_channel' => RacPublicRateService::rateChannelDescriptor($rateType),
            'amount_due' => $amountDue,
            'amount_stored' => $storedTotal,
            'amount_recalculated' => is_array($recalculated) ? round((float) ($recalculated['total'] ?? 0), 2) : null,
            'currency' => $currency,
            'totals' => is_array($recalculated) ? [
                'base' => round((float) ($recalculated['base'] ?? 0), 2),
                'coverage' => round((float) ($recalculated['coverage'] ?? 0), 2),
                'extras' => round((float) ($recalculated['extras'] ?? 0), 2),
                'itbms' => round((float) ($recalculated['itbms'] ?? 0), 2),
                'total' => round((float) ($recalculated['total'] ?? 0), 2),
                'currency' => (string) ($recalculated['currency'] ?? $currency),
            ] : null,
            'quote' => [
                'present' => $quoteToken !== '',
                'valid' => $quoteValid,
                'refreshed_for_preview' => $quoteRefreshed,
                // No exponer token completo.
                'token_prefix' => $quoteToken !== '' ? substr($quoteToken, 0, 8) : null,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function publicReservationSummary(array $row): array
    {
        $pickup = BranchDataService::findByCode((string) ($row['location_code'] ?? ''));
        $return = BranchDataService::findByCode((string) ($row['return_location_code'] ?? $row['location_code'] ?? ''));

        return [
            'confirmation_number' => RacReservationService::displayConfirmationCode($row),
            'status' => (string) ($row['status'] ?? 'pending'),
            'customer_name_masked' => $this->maskName((string) ($row['customer_name'] ?? '')),
            'customer_email_masked' => $this->maskEmail((string) ($row['customer_email'] ?? '')),
            'vehicle_name' => (string) ($row['vehicle_name'] ?? ''),
            'vehicle_code' => (string) ($row['vehicle_code'] ?? $row['sipp_code'] ?? ''),
            'coverage_name' => (string) ($row['coverage_name'] ?? ''),
            'pickup_location' => (string) ($pickup['name'] ?? $row['location_code'] ?? ''),
            'return_location' => (string) ($return['name'] ?? $row['return_location_code'] ?? ''),
            'pickup_date' => (string) ($row['pickup_date'] ?? ''),
            'pickup_time' => (string) ($row['pickup_time'] ?? ''),
            'return_date' => (string) ($row['return_date'] ?? ''),
            'return_time' => (string) ($row['return_time'] ?? ''),
            'rate_type' => (($row['rate_type'] ?? 'web') === 'counter') ? 'counter' : 'web',
            'rate_channel' => RacPublicRateService::rateChannelDescriptor(
                (string) ($row['rate_type'] ?? 'web')
            ),
            'currency' => (string) ($row['currency'] ?? 'USD'),
        ];
    }

    public function maskEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '' || !str_contains($email, '@')) {
            return '—';
        }
        [$user, $domain] = explode('@', $email, 2);
        $initial = $user !== '' ? mb_substr($user, 0, 1, 'UTF-8') : '*';
        return $initial . '***@' . $domain;
    }

    public function maskName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '—';
        }
        $parts = preg_split('/\s+/', $name) ?: [];
        $out = [];
        foreach ($parts as $p) {
            if ($p === '') {
                continue;
            }
            $out[] = mb_substr($p, 0, 1, 'UTF-8') . '***';
        }
        return $out !== [] ? implode(' ', $out) : '—';
    }

    /**
     * @param array<string, mixed> $row
     */
    public function lastNameMatches(array $row, string $lastName): bool
    {
        $needle = mb_strtolower(trim($lastName), 'UTF-8');
        if ($needle === '') {
            return false;
        }
        $stored = mb_strtolower(trim((string) ($row['customer_name'] ?? '')), 'UTF-8');
        if ($stored === '') {
            return false;
        }
        $parts = preg_split('/\s+/', $stored) ?: [];
        $storedLast = $parts !== [] ? (string) end($parts) : '';
        return $storedLast === $needle || str_contains($stored, $needle);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function extrasFromReservation(array $row): array
    {
        $snap = [];
        if (!empty($row['extras_snapshot_json'])) {
            $decoded = json_decode((string) $row['extras_snapshot_json'], true);
            if (is_array($decoded)) {
                $snap = $decoded;
            }
        }

        $protection = strtoupper(trim((string) ($row['coverage_code'] ?? $snap['protection'] ?? 'NONE')));
        if ($protection === '') {
            $protection = 'NONE';
        }

        $items = [];
        $additionalDrivers = 0;
        if (is_array($snap['items'] ?? null)) {
            foreach ($snap['items'] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $code = strtoupper(trim((string) ($item['code'] ?? '')));
                if ($code === '') {
                    continue;
                }
                $qty = max(1, (int) ($item['quantity'] ?? 1));
                if ($code === 'CONDADIC') {
                    $additionalDrivers = $qty;
                    continue;
                }
                $items[] = ['code' => $code, 'quantity' => $qty];
            }
        }
        if ($additionalDrivers <= 0 && isset($snap['additionalDrivers'])) {
            $additionalDrivers = max(0, (int) $snap['additionalDrivers']);
        }

        $mandatory = (float) ($snap['mandatoryTotal'] ?? $snap['totals']['mandatory'] ?? 0);

        return [
            'protection' => $protection,
            'items' => $items,
            'additionalDrivers' => $additionalDrivers,
            'mandatoryTotal' => max(0, $mandatory),
            'rental_days' => max(1, (int) ($row['rental_days'] ?? 1)),
            'vehicle_name' => (string) ($row['vehicle_category'] ?? $row['vehicle_name'] ?? ''),
            'vehicle_category' => (string) ($row['vehicle_category'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed>|null $reservation
     * @return array<string, mixed>
     */
    private function fail(string $result, string $message, ?array $reservation = null): array
    {
        return [
            'ok' => false,
            'result' => $result,
            'message' => $message,
            'payment_available' => false,
            'provider_available' => false,
            'prepayment_available' => false,
            'payment_provider_available' => false,
            'online_payment_available' => false,
            'reservation_modified' => false,
            'payment_created' => false,
            'reservation' => $reservation,
        ];
    }
}
