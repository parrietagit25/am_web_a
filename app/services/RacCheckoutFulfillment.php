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
        if ($status === 'fulfilled' && !empty($row['confirmation_code'])) {
            return ['ok' => true, 'already' => true, 'record' => $row];
        }
        if ($status !== 'paid' && $status !== 'fulfilling') {
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
            ]);
            $fresh = RacCheckoutStore::get($checkoutToken);

            return ['ok' => true, 'record' => $fresh, 'reservation' => $result];
        }

        RacCheckoutStore::update($checkoutToken, [
            'status' => 'paid_fulfill_failed',
            'fulfillment_error' => $result['message'] ?? 'No se pudo crear la reserva tras el pago.',
            'fulfillment' => $result,
        ]);

        return ['ok' => false, 'error' => $result['message'] ?? 'Fallo al crear reserva', 'reservation' => $result];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function postReservation(array $payload): array
    {
        $url = self::reservationUrl();
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $raw = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'message' => 'Respuesta inválida al crear reserva (HTTP ' . $http . ').',
            ];
        }

        return $decoded;
    }

    private static function reservationUrl(): string
    {
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? '127.0.0.1'));
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        if (defined('APP_PUBLIC_URL') && trim((string) APP_PUBLIC_URL) !== '') {
            return rtrim((string) APP_PUBLIC_URL, '/') . '/api/rac-reservation.php';
        }

        return $scheme . '://' . $host . '/api/rac-reservation.php';
    }
}
