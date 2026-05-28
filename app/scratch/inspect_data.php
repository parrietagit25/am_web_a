<?php
$data = json_decode(file_get_contents(__DIR__ . '/../storage/site_data.json'), true);
echo "SUCURSALES: " . count($data['sucursales'] ?? []) . PHP_EOL;
foreach (($data['sucursales'] ?? []) as $s) {
    echo "  #" . $s['id'] . ": " . $s['name'] . PHP_EOL;
    echo "    phone: " . ($s['phone'] ?? '-') . PHP_EOL;
    echo "    email: " . ($s['email'] ?? '-') . PHP_EOL;
    echo "    address: " . ($s['address'] ?? '-') . PHP_EOL;
}
echo PHP_EOL;
echo "SEMI MESSAGES: " . count($data['homepage']['messages'] ?? []) . PHP_EOL;
echo "SEMI CONTACT MESSAGES: " . count($data['seminuevos']['contact_messages'] ?? []) . PHP_EOL;
