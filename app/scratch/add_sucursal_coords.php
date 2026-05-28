<?php
/**
 * Update seminuevos sucursales to add lat/lng for Leaflet maps
 */
$dataFile = __DIR__ . '/../storage/site_data.json';
$data = json_decode(file_get_contents($dataFile), true);

// Real approximate lat/lng for each sucursal
$coords = [
    1 => ['lat' => 8.9946,  'lng' => -79.5239, 'location' => 'Av. Ricardo J. Alfaro, Tumba Muerto, Panamá'],
    2 => ['lat' => 8.9919,  'lng' => -79.5012, 'location' => 'Vía Israel, San Francisco, Panamá'],
    3 => ['lat' => 8.9737,  'lng' => -79.5508, 'location' => 'Costa Verde, Panamá'],
    4 => ['lat' => 8.4003,  'lng' => -82.4344, 'location' => 'Av. Domingo Díaz, David, Chiriquí'],
    5 => ['lat' => 8.5180,  'lng' => -80.3500, 'location' => 'Vía Interamericana, Penonomé, Coclé'],
];

foreach ($data['seminuevos']['sucursales'] as $idx => $suc) {
    $id = intval($suc['id']);
    if (isset($coords[$id])) {
        $data['seminuevos']['sucursales'][$idx]['lat']      = $coords[$id]['lat'];
        $data['seminuevos']['sucursales'][$idx]['lng']      = $coords[$id]['lng'];
        $data['seminuevos']['sucursales'][$idx]['location'] = $coords[$id]['location'];
        echo "Updated #{$id}: {$suc['name']}" . PHP_EOL;
    }
}

file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "Done!" . PHP_EOL;
