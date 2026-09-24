<?php
/**
 * Tras cobro PowerTranz: refleja pago + últimos 4 en RentWorks (VehModify).
 * Invisible al cliente; solo backend / mostrador.
 */
declare(strict_types=1);

require_once __DIR__ . '/BarsPaymentLabService.php';
require_once __DIR__ . '/BarsReservationClient.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PowertranzDatabaseSchema.php';

final class RacRentworksPaymentSync
{
    public static function isEnabled(): bool
    {
        if (defined('BARS_RW_POST_ONLINE_PAYMENT')) {
            return (bool) BARS_RW_POST_ONLINE_PAYMENT;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public static function postFromPowertranzPayment(string $barsConfirmation, int $paymentId, array $context = []): array
    {
        $barsConfirmation = strtoupper(trim($barsConfirmation));
        if ($barsConfirmation === '' || $paymentId <= 0) {
            return ['ok' => false, 'skipped' => true, 'error' => 'Falta confirmation o payment_id.'];
        }
        if (!self::isEnabled()) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'BARS_RW_POST_ONLINE_PAYMENT deshabilitado.'];
        }

        PowertranzDatabaseSchema::ensure();
        $db = Database::getInstance();
        $row = $db->selectOne('SELECT * FROM rac_powertranz_payments WHERE id = :id LIMIT 1', [':id' => $paymentId]);
        if (!is_array($row)) {
            return ['ok' => false, 'error' => 'Pago PowerTranz no encontrado.'];
        }
        if (empty($row['approved']) && strtolower((string) ($row['status'] ?? '')) !== 'approved') {
            return ['ok' => false, 'error' => 'Pago no aprobado; no se postea a RentWorks.'];
        }

        $suffix = BarsPaymentLabService::extractCardSuffixFromRow($row);
        if ($suffix === '' || !preg_match('/^\d{4}$/', $suffix)) {
            return ['ok' => false, 'error' => 'Sin CardSuffix (últimos 4); no se postea a RentWorks.'];
        }

        $amount = (float) ($row['amount'] ?? 0);
        // Preferir EstimatedTotal de RW si el contexto lo trae o vía lookup.
        $estimated = isset($context['estimated_total']) ? (float) $context['estimated_total'] : 0.0;
        if ($estimated <= 0) {
            $estimated = self::lookupEstimatedTotal($barsConfirmation, (string) ($context['last_name'] ?? ''));
        }
        // Gustavo: Amount = total del alquiler. Si RW tiene estimado > 0, usarlo;
        // si no, el monto cobrado en PowerTranz.
        $payAmount = $estimated > 0 ? round($estimated, 2) : round($amount, 2);
        if ($payAmount <= 0) {
            return ['ok' => false, 'error' => 'Monto inválido para postear a RentWorks.'];
        }

        $brand = trim((string) ($row['card_brand'] ?? ''));
        $auth = trim((string) ($row['authorization_code'] ?? ''));
        if ($auth === '') {
            $auth = 'VENTA DIR';
        }
        $expire = self::extractExpireMmyy($row);
        if ($expire === '') {
            $expire = self::expireFallback();
        }

        $result = BarsPaymentLabService::run([
            'action' => 'send',
            'confirm' => 'EJECUTAR',
            'dry_run' => 0,
            'reservation_code' => $barsConfirmation,
            'last_name' => (string) ($context['last_name'] ?? ''),
            'amount' => $payAmount,
            'card_suffix' => $suffix,
            'card_brand' => $brand,
            'card_code' => BarsPaymentLabService::mapBrandToCardCode($brand) ?: 'VI',
            'txn_type' => BarsPaymentLabService::TXN_CHARGE,
            'authorization_code' => $auth,
            'expire_date' => $expire,
            'mask_style' => 'x12',
            'payment_id' => $paymentId,
            'remark' => 'Automarket online payment',
        ]);

        $result['bars_confirmation'] = $barsConfirmation;
        $result['payment_id'] = $paymentId;
        $result['posted_amount'] = $payAmount;
        $result['card_suffix'] = $suffix;
        $result['expire_date'] = $expire;

        if (empty($result['ok'])) {
            am_log(
                'RW payment sync FAIL res=' . $barsConfirmation . ' pay=' . $paymentId . ' err=' . ($result['error'] ?? ''),
                'ERROR'
            );
        } else {
            am_log(
                'RW payment sync OK res=' . $barsConfirmation . ' pay=' . $paymentId . ' amount=' . $payAmount . ' last4=' . $suffix,
                'INFO'
            );
        }

        return $result;
    }

    private static function lookupEstimatedTotal(string $code, string $lastName): float
    {
        try {
            $client = new BarsReservationClient();
            if (!$client->isConfigured()) {
                return 0.0;
            }
            $lookup = $client->lookupReservation($code, $lastName, false);
            $res = is_array($lookup['reservation'] ?? null) ? $lookup['reservation'] : [];
            $est = $res['estimatedTotalAmount'] ?? $res['totalAmount'] ?? null;

            return is_numeric($est) ? (float) $est : 0.0;
        } catch (Throwable $e) {
            am_log('RW payment sync lookup: ' . $e->getMessage(), 'WARNING');

            return 0.0;
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function extractExpireMmyy(array $row): string
    {
        $direct = preg_replace('/\D+/', '', (string) ($row['card_expiry'] ?? $row['CardExpiration'] ?? '')) ?? '';
        if (strlen($direct) === 4) {
            return $direct;
        }
        if (strlen($direct) === 6) {
            // MMYYYY → MMYY
            return substr($direct, 0, 2) . substr($direct, -2);
        }

        foreach (['complete_response_json', 'payment_response_json_sanitized', 'response_payload_json'] as $col) {
            $raw = (string) ($row[$col] ?? '');
            if ($raw === '') {
                continue;
            }
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                continue;
            }
            foreach (['CardExpiration', 'cardExpiration', 'ExpiryDate', 'ExpireDate', 'ExpDate'] as $key) {
                if (!isset($data[$key])) {
                    continue;
                }
                $digits = preg_replace('/\D+/', '', (string) $data[$key]) ?? '';
                if (strlen($digits) === 4) {
                    return $digits;
                }
                if (strlen($digits) === 6) {
                    return substr($digits, 0, 2) . substr($digits, -2);
                }
            }
        }

        return '';
    }

    private static function expireFallback(): string
    {
        if (defined('BARS_RW_PAYMENT_EXPIRE_FALLBACK')) {
            $v = preg_replace('/\D+/', '', (string) BARS_RW_PAYMENT_EXPIRE_FALLBACK) ?? '';
            if (strlen($v) === 4) {
                return $v;
            }
        }

        // Sin Exp RentWorks avisa "expiration date is invalid". Placeholder operativo (lab OK).
        return '1230';
    }
}
