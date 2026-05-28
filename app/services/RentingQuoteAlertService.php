<?php
/**
 * Correos de alerta — nuevas cotizaciones Renting (site_data.json).
 */

require_once __DIR__ . '/ResendService.php';

class RentingQuoteAlertService {
    public static function normalizeList(array $renting): array {
        $list = $renting['quote_alert_emails'] ?? [];
        return is_array($list) ? $list : [];
    }

    public static function listActiveEmails(array $renting): array {
        $emails = [];
        foreach (self::normalizeList($renting) as $row) {
            if (empty($row['active'])) {
                continue;
            }
            $e = filter_var($row['email'] ?? '', FILTER_VALIDATE_EMAIL);
            if ($e) {
                $emails[] = $e;
            }
        }
        return array_values(array_unique($emails));
    }

    public static function add(array &$renting, string $email, string $label = ''): array {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Correo electrónico no válido.'];
        }
        if (!isset($renting['quote_alert_emails']) || !is_array($renting['quote_alert_emails'])) {
            $renting['quote_alert_emails'] = [];
        }
        foreach ($renting['quote_alert_emails'] as $row) {
            if (strtolower($row['email'] ?? '') === $email) {
                return ['ok' => false, 'message' => 'Ese correo ya está registrado.'];
            }
        }
        $renting['quote_alert_emails'][] = [
            'id' => 'rqa_' . time() . '_' . bin2hex(random_bytes(3)),
            'email' => $email,
            'label' => trim($label),
            'active' => true,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        return ['ok' => true, 'message' => 'Correo registrado correctamente.'];
    }

    public static function deleteById(array &$renting, string $id): bool {
        $id = trim($id);
        if ($id === '') {
            return false;
        }
        $before = count(self::normalizeList($renting));
        $renting['quote_alert_emails'] = array_values(array_filter(
            self::normalizeList($renting),
            fn($row) => ($row['id'] ?? '') !== $id
        ));
        return count($renting['quote_alert_emails']) < $before;
    }

    public static function setActiveById(array &$renting, string $id, bool $active): bool {
        $id = trim($id);
        $found = false;
        foreach (self::normalizeList($renting) as $i => $row) {
            if (($row['id'] ?? '') === $id) {
                $renting['quote_alert_emails'][$i]['active'] = $active;
                $found = true;
                break;
            }
        }
        return $found;
    }

    /**
     * @return array{sent: bool, recipients: int, error: ?string}
     */
    public static function notifyNewQuote(array $lead, array $renting): array {
        $recipients = self::listActiveEmails($renting);
        if (empty($recipients)) {
            am_log('Renting quote alert: no active recipients', 'INFO');
            return ['sent' => false, 'recipients' => 0, 'error' => 'Sin correos de alerta configurados'];
        }

        $esc = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $subject = 'Nueva cotización Renting — ' . ($lead['name'] ?? 'Cliente');
        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px;'>
            <h2 style='color: #c51f17;'>Nueva solicitud de cotización Renting</h2>
            <p><strong>Nombre:</strong> {$esc($lead['name'] ?? '')}</p>
            <p><strong>Correo:</strong> {$esc($lead['email'] ?? '')}</p>
            <p><strong>Teléfono:</strong> {$esc($lead['phone'] ?? '—')}</p>
            <p><strong>Rango de ingresos:</strong> {$esc($lead['income_range'] ?? '—')}</p>
            <p><strong>Auto de interés:</strong> {$esc($lead['car_interest'] ?? '—')}</p>
            <p><strong>Fecha:</strong> {$esc($lead['date'] ?? '')}</p>
            <hr>
            <p style='font-size: 12px; color: #666;'>Revise el panel administrativo en Renting → Cotizaciones.</p>
        </div>";

        $resend = new ResendService();
        $result = $resend->sendEmail($recipients, $subject, $html);

        if (($result['status'] ?? '') === 'success') {
            return ['sent' => true, 'recipients' => count($recipients), 'error' => null];
        }

        $err = $result['message'] ?? 'Error al enviar correo';
        am_log('Renting quote alert failed: ' . $err, 'ERROR');
        return ['sent' => false, 'recipients' => count($recipients), 'error' => $err];
    }
}
