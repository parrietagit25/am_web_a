<?php
/**
 * API: Branch list + closed return days for RAC search UI.
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/BranchDataService.php';

echo json_encode([
    'success' => true,
    'branches' => BranchDataService::getBranchPayloadForJs(),
    'imageBase' => BranchDataService::partnerImageBaseUrl(),
], JSON_UNESCAPED_UNICODE);
