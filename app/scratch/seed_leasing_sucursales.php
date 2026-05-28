<?php
$path = __DIR__ . '/../storage/site_data.json';
$data = json_decode(file_get_contents($path), true);
$data['leasing']['sucursales'] = $data['homepage']['sucursales'] ?? [];
file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo 'Seeded ' . count($data['leasing']['sucursales']) . " leasing sucursales.\n";
