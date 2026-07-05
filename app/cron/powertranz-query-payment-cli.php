<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit(1); }
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Database.php';
require_once __DIR__ . '/../services/PowertranzSanitizer.php';
$id = (int) ($argv[1] ?? 0);
if ($id <= 0) { fwrite(STDERR, "usage: payment_id\n"); exit(1); }
$db = Database::getInstance();
$row = $db->selectOne('SELECT id,test_reference,order_identifier,transaction_identifier,amount,currency,status,iso_response_code,response_message,error_message,created_at,updated_at,redirect_data_present,request_payload_json,response_payload_json,complete_payload_json,complete_response_json,merchant_response_json_sanitized FROM rac_powertranz_payments WHERE id=:id',[':id'=>$id]);
if (!is_array($row)) { echo "NOT FOUND\n"; exit(2); }
$dec = static fn(?string $j) => is_array($d = json_decode((string)$j, true)) ? $d : null;
echo json_encode([
    'id'=>(int)$row['id'],'test_reference'=>$row['test_reference'],'order_identifier'=>$row['order_identifier'],
    'transaction_identifier'=>$row['transaction_identifier'],'amount'=>(float)$row['amount'],'currency'=>$row['currency'],
    'status'=>$row['status'],'iso_response_code'=>$row['iso_response_code'],'response_message'=>$row['response_message'],
    'error_message'=>$row['error_message'],'created_at'=>$row['created_at'],'updated_at'=>$row['updated_at'],
    'redirect_data_present'=>!empty($row['redirect_data_present']),
    'request_payload_json'=>PowertranzSanitizer::sanitizePayload($dec($row['request_payload_json'])),
    'response_payload_json'=>PowertranzSanitizer::sanitizePayload($dec($row['response_payload_json'])),
    'complete_payload_json'=>PowertranzSanitizer::sanitizePayload($dec($row['complete_payload_json'])),
    'complete_response_json'=>PowertranzSanitizer::sanitizePayload($dec($row['complete_response_json'])),
    'merchant_response_json_sanitized'=>PowertranzSanitizer::sanitizePayload($dec($row['merchant_response_json_sanitized'])),
], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . PHP_EOL;
