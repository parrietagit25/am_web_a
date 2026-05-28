<?php
/**
 * Alert recipients for new RAC reservations.
 */

require_once __DIR__ . '/RacDatabaseSchema.php';
require_once __DIR__ . '/ResendService.php';

class RacAlertEmailService {
    public function __construct() {
        RacDatabaseSchema::ensure();
    }

    public function listAll(): array {
        $db = Database::getInstance();
        return $db->select('SELECT * FROM rac_alert_emails ORDER BY created_at DESC');
    }

    public function listActiveEmails(): array {
        $db = Database::getInstance();
        $rows = $db->select('SELECT email FROM rac_alert_emails WHERE is_active = 1 ORDER BY id ASC');
        $emails = [];
        foreach ($rows as $row) {
            $e = filter_var($row['email'] ?? '', FILTER_VALIDATE_EMAIL);
            if ($e) {
                $emails[] = $e;
            }
        }
        return array_values(array_unique($emails));
    }

    public function add(string $email, string $label = ''): array {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Correo electrónico no válido.'];
        }
        $db = Database::getInstance();
        try {
            $db->execute(
                'INSERT INTO rac_alert_emails (email, label, is_active) VALUES (:email, :label, 1)',
                [':email' => $email, ':label' => trim($label)]
            );
            return ['ok' => true, 'message' => 'Correo agregado correctamente.', 'id' => (int) $db->lastInsertId()];
        } catch (Exception $e) {
            if (stripos($e->getMessage(), 'UNIQUE') !== false || stripos($e->getMessage(), 'unique') !== false) {
                return ['ok' => false, 'message' => 'Ese correo ya está registrado.'];
            }
            throw $e;
        }
    }

    public function delete(int $id): bool {
        $db = Database::getInstance();
        return $db->execute('DELETE FROM rac_alert_emails WHERE id = :id', [':id' => $id]) > 0;
    }

    public function setActive(int $id, bool $active): bool {
        $db = Database::getInstance();
        return $db->execute(
            'UPDATE rac_alert_emails SET is_active = :active WHERE id = :id',
            [':active' => $active ? 1 : 0, ':id' => $id]
        ) > 0;
    }

    /**
     * @return array{sent: bool, recipients: int, error: ?string}
     */
    public function notifyNewReservation(array $reservation): array {
        $recipients = $this->listActiveEmails();
        if (empty($recipients)) {
            am_log('RAC reservation alert: no active recipients configured', 'INFO');
            return ['sent' => false, 'recipients' => 0, 'error' => 'Sin correos de alerta configurados'];
        }

        $code = $reservation['reservation_code'] ?? '';
        $subject = 'Nueva reserva RAC — ' . $code;
        $html = $this->buildEmailHtml($reservation);

        $resend = new ResendService();
        $result = $resend->sendEmail($recipients, $subject, $html);

        if (($result['status'] ?? '') === 'success') {
            return ['sent' => true, 'recipients' => count($recipients), 'error' => null];
        }

        $err = $result['message'] ?? 'Error al enviar correo';
        am_log('RAC alert email failed: ' . $err, 'ERROR');
        return ['sent' => false, 'recipients' => count($recipients), 'error' => $err];
    }

    private function buildEmailHtml(array $r): string {
        $esc = function ($v) {
            return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        };
        $pickupBranch = BranchDataService::findByCode($r['location_code'] ?? '');
        $returnBranch = BranchDataService::findByCode($r['return_location_code'] ?? '');
        $pickupName = $pickupBranch['name'] ?? ($r['location_code'] ?? '');
        $returnName = $returnBranch['name'] ?? ($r['return_location_code'] ?? '');

        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #c51f17;'>Nueva reserva Rent A Car</h2>
            <p><strong>Código:</strong> {$esc($r['reservation_code'] ?? '')}</p>
            <p><strong>Cliente:</strong> {$esc($r['customer_name'] ?? '')}<br>
               <strong>Email:</strong> {$esc($r['customer_email'] ?? '')}<br>
               <strong>Teléfono:</strong> {$esc($r['customer_phone'] ?? '')}</p>
            <p><strong>Vehículo:</strong> {$esc($r['vehicle_name'] ?? '')} ({$esc($r['sipp_code'] ?? '')})</p>
            <p><strong>Retiro:</strong> {$esc($pickupName)} — {$esc($r['pickup_date'] ?? '')} {$esc($r['pickup_time'] ?? '')}<br>
               <strong>Devolución:</strong> {$esc($returnName)} — {$esc($r['return_date'] ?? '')} {$esc($r['return_time'] ?? '')}</p>
            <p><strong>Póliza:</strong> {$esc($r['coverage_name'] ?? $r['coverage_code'] ?? '—')}<br>
               <strong>Monto protección:</strong> \$" . number_format((float) ($r['coverage_amount'] ?? 0), 2) . " USD</p>
            <p><strong>Desglose:</strong> Base \$" . number_format((float) ($r['price_rental_base'] ?? 0), 2) .
               " + SAF \$" . number_format((float) ($r['price_saf'] ?? 0), 2) .
               " + ITBMS \$" . number_format((float) ($r['price_itbms'] ?? 0), 2) .
               " = <strong>Total \$" . number_format((float) ($r['price_total_estimated'] ?? 0), 2) . " USD</strong></p>
            <p><strong>Comentarios:</strong><br>" . nl2br($esc($r['customer_comments'] ?? '—')) . "</p>
            <hr>
            <p style='font-size: 12px; color: #666;'>Revise el panel administrativo para gestionar esta reserva.</p>
        </div>";
    }
}
