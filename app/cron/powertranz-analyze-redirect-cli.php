<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit(1); }
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/PowertranzPaymentService.php';
$id = (int) ($argv[1] ?? 0);
if ($id <= 0) { fwrite(STDERR, "usage: payment_id\n"); exit(1); }
$service = new PowertranzPaymentService();
$analysis = $service->analyzeRedirectDataForPayment($id);
echo json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
