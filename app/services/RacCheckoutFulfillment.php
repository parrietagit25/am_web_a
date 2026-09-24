<?php
declare(strict_types=1);

require_once __DIR__ . '/RacCheckoutStore.php';
require_once __DIR__ . '/PowertranzClient.php';

/**
 * Tras pago aprobado: crea la reserva en RentWorks (una sola vez).
 */
final class RacCheckoutFulfillment
{
    /**
     * @return array<string, mixed>
     */
    public static function onPaymentApproved(string $checkoutToken, int $paymentId = 0): array
    {
        $row = RacCheckoutStore::get($checkoutToken);
        if ($row === null) {
            return ['ok' => false, 'error' => 'Checkout no encontrado.'];
        }

        $status = (string) ($row['status'] ?? '');
        if (($row['mode'] ?? '') === 'pay_existing') {
            return self::markExistingReservationPaid($checkoutToken, $paymentId, $row);
        }
        if ($status === 'fulfilled' && !empty($row['confirmation_code'])) {
            $rwPay = is_array($row['rentworks_payment'] ?? null) ? $row['rentworks_payment'] : [];
            if (empty($rwPay['ok']) && $paymentId > 0) {
                $rwPay = self::syncRentworksPayment(
                    (string) $row['confirmation_code'],
                    $paymentId,
                    is_array($row['payload'] ?? null) ? $row['payload'] : []
                );
                if ($rwPay !== []) {
                    RacCheckoutStore::update($checkoutToken, ['rentworks_payment' => $rwPay]);
                    $row = RacCheckoutStore::get($checkoutToken) ?? $row;
                }
            }

            return ['ok' => true, 'already' => true, 'record' => $row, 'rentworks_payment' => $rwPay];
        }
        if (!in_array($status, ['paid', 'fulfilling', 'paid_fulfill_failed'], true)) {
            RacCheckoutStore::update($checkoutToken, [
                'status' => 'paid',
                'payment_id' => $paymentId,
                'paid_at' => date('c'),
            ]);
        } else {
            RacCheckoutStore::update($checkoutToken, [
                'payment_id' => $paymentId,
                'paid_at' => $row['paid_at'] ?? date('c'),
            ]);
        }

        RacCheckoutStore::update($checkoutToken, ['status' => 'fulfilling']);

        $payload = is_array($row['payload'] ?? null) ? $row['payload'] : [];
        $payload['_checkout_fulfill'] = $checkoutToken;
        $payload['_checkout_hmac'] = RacCheckoutStore::fulfillHmac($checkoutToken);

        $result = self::postReservation($payload);
        if (!empty($result['success'])) {
            RacCheckoutStore::update($checkoutToken, [
                'status' => 'fulfilled',
                'confirmation_code' => $result['confirmation_code'] ?? $result['bars_confirmation_code'] ?? '',
                'reservation_code' => $result['reservation_code'] ?? '',
                'reservation_id' => $result['reservation_id'] ?? null,
                'fulfillment' => $result,
                'fulfillment_error' => null,
            ]);
            $fresh = RacCheckoutStore::get($checkoutToken);

            $rwPay = self::syncRentworksPayment(
                (string) ($result['confirmation_code'] ?? $result['bars_confirmation_code'] ?? ''),
                $paymentId,
                is_array($payload) ? $payload : []
            );
            if ($fresh !== null && $rwPay !== []) {
                RacCheckoutStore::update($checkoutToken, ['rentworks_payment' => $rwPay]);
                $fresh = RacCheckoutStore::get($checkoutToken);
            }

            return ['ok' => true, 'record' => $fresh, 'reservation' => $result, 'rentworks_payment' => $rwPay];
        }

        RacCheckoutStore::update($checkoutToken, [
            'status' => 'paid_fulfill_failed',
            'fulfillment_error' => $result['message'] ?? 'No se pudo crear la reserva tras el pago.',
            'fulfillment' => $result,
        ]);

        return ['ok' => false, 'error' => $result['message'] ?? 'Fallo al crear reserva', 'reservation' => $result];
    }

    /**
     * Pago de una reserva ya creada (mostrador → tarjeta). No vuelve a crear en RentWorks.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function markExistingReservationPaid(string $checkoutToken, int $paymentId, array $row): array
    {
        if (($row['status'] ?? '') === 'fulfilled' && !empty($row['paid_existing'])) {
            return ['ok' => true, 'already' => true, 'record' => $row];
        }

        require_once __DIR__ . '/RacReservationService.php';
        $resId = (int) ($row['reservation_id'] ?? 0);
        $svc = new RacReservationService();
        $ok = $resId > 0 && $svc->markPaidOnline($resId, [
            'payment_id' => $paymentId,
            'amount' => $row['amount'] ?? null,
        ]);

        $rwPay = [];
        if ($ok) {
            $local = $svc->findById($resId);
            $barsCode = '';
            $lastName = '';
            if (is_array($local)) {
                $barsCode = trim((string) ($local['bars_confirmation_code'] ?? ''));
                if ($barsCode === '') {
                    $barsCode = trim((string) ($local['reservation_code'] ?? ''));
                }
                $name = trim((string) ($local['customer_name'] ?? ''));
                if ($name !== '' && str_contains($name, ' ')) {
                    $parts = preg_split('/\s+/', $name) ?: [];
                    $lastName = (string) end($parts);
                }
            }
            if ($barsCode === '') {
                $barsCode = trim((string) ($row['confirmation_code'] ?? $row['bars_confirmation_code'] ?? ''));
            }
            $rwPay = self::syncRentworksPayment($barsCode, $paymentId, [
                'lastName' => $lastName,
                'last_name' => $lastName,
            ]);
            if (is_array($local) && $rwPay !== []) {
                $svc->markPaidOnline($resId, [
                    'payment_id' => $paymentId,
                    'amount' => $row['amount'] ?? null,
                    'rentworks_payment' => $rwPay,
                    'card_suffix' => $rwPay['card_suffix'] ?? null,
                ]);
            }
        }

        $fresh = RacCheckoutStore::update($checkoutToken, [
            'status' => $ok ? 'fulfilled' : 'paid_fulfill_failed',
            'payment_id' => $paymentId,
            'paid_at' => $row['paid_at'] ?? date('c'),
            'paid_existing' => $ok,
            'fulfillment_error' => $ok ? null : 'No se pudo marcar la reserva como pagada.',
            'rentworks_payment' => $rwPay !== [] ? $rwPay : null,
        ]);

        return [
            'ok' => $ok,
            'already' => false,
            'record' => $fresh,
            'error' => $ok ? null : 'No se pudo marcar la reserva como pagada.',
            'rentworks_payment' => $rwPay,
        ];
    }

    /**
     * Postea charge + últimos 4 a RentWorks. No falla el checkout si RW payment sync falla
     * (la reserva ya existe / ya está marcada pagada localmente).
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function syncRentworksPayment(string $barsConfirmation, int $paymentId, array $payload = []): array
    {
        $barsConfirmation = strtoupper(trim($barsConfirmation));
        if ($barsConfirmation === '' || $paymentId <= 0) {
            return ['ok' => false, 'skipped' => true, 'error' => 'Sin Res # o payment_id.'];
        }

        try {
            require_once __DIR__ . '/RacRentworksPaymentSync.php';
            $lastName = trim((string) ($payload['lastName'] ?? $payload['last_name'] ?? ''));
            $estimated = 0.0;
            if (isset($payload['estimatedTotal']) && is_numeric($payload['estimatedTotal'])) {
                $estimated = (float) $payload['estimatedTotal'];
            } elseif (isset($payload['price_total_estimated']) && is_numeric($payload['price_total_estimated'])) {
                $estimated = (float) $payload['price_total_estimated'];
            } elseif (isset($payload['total']) && is_numeric($payload['total'])) {
                $estimated = (float) $payload['total'];
            }

            return RacRentworksPaymentSync::postFromPowertranzPayment($barsConfirmation, $paymentId, [
                'last_name' => $lastName,
                'estimated_total' => $estimated,
            ]);
        } catch (Throwable $e) {
            am_log('RW payment sync exception: ' . $e->getMessage(), 'ERROR');

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function postReservation(array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $lastHttp = 0;
        foreach (self::reservationTargets() as $target) {
            $ch = curl_init($target['url']);
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Host: ' . $target['host'],
            ];
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 8,
            ]);
            $raw = curl_exec($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $lastHttp = $http;
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            'success' => false,
            'message' => 'Respuesta inválida al crear reserva (HTTP ' . $lastHttp . ').',
        ];
    }

    /**
     * @return list<array{url: string, host: string}>
     */
    private static function reservationTargets(): array
    {
        $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
        if ($host === '' || str_starts_with($host, '127.0.0.1') || str_starts_with($host, 'localhost')) {
            $host = 'test.automarket.com.pa';
        }

        $targets = [
            ['url' => 'http://automarket_web/api/rac-reservation.php', 'host' => $host],
            ['url' => 'https://' . $host . '/api/rac-reservation.php', 'host' => $host],
        ];
        if (defined('APP_PUBLIC_URL') && trim((string) APP_PUBLIC_URL) !== '') {
            array_unshift($targets, [
                'url' => rtrim((string) APP_PUBLIC_URL, '/') . '/api/rac-reservation.php',
                'host' => $host,
            ]);
        }

        return $targets;
    }
}
