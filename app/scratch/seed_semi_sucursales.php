<?php
/**
 * Seed Seminuevos sucursales into site_data.json
 */
$dataFile = __DIR__ . '/../storage/site_data.json';
$data = json_decode(file_get_contents($dataFile), true);

if (!isset($data['seminuevos'])) {
    $data['seminuevos'] = [];
}

// Seed sucursales if empty
if (empty($data['seminuevos']['sucursales'])) {
    $data['seminuevos']['sucursales'] = [
        [
            'id' => 1,
            'name' => 'Tumba Muerto',
            'address' => 'Av. Ricardo J. Alfaro, Tumba Muerto, Panamá',
            'phone' => '(507) 279-2700',
            'email' => 'tumbamuerto@automarket.com.pa',
            'whatsapp' => '50767470070',
            'schedule' => 'Lun-Sáb: 8:00am - 6:00pm | Dom: 9:00am - 4:00pm',
            'map_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.6435!2d-79.5239!3d8.9944!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8faca8c!2sAutomarket!5e0!3m2!1ses!2spa!4v1234567890',
            'sort_order' => 1,
            'active' => true
        ],
        [
            'id' => 2,
            'name' => 'Vía Israel',
            'address' => 'Vía Israel, San Francisco, Ciudad de Panamá',
            'phone' => '(507) 279-2700',
            'email' => 'viaisrael@automarket.com.pa',
            'whatsapp' => '50767470070',
            'schedule' => 'Lun-Sáb: 8:00am - 6:00pm | Dom: 9:00am - 4:00pm',
            'map_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.6435!2d-79.5239!3d8.9944!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8faca8c!2sAutomarket!5e0!3m2!1ses!2spa!4v1234567891',
            'sort_order' => 2,
            'active' => true
        ],
        [
            'id' => 3,
            'name' => 'Costa Verde',
            'address' => 'Costa Verde, Panamá',
            'phone' => '(507) 279-2700',
            'email' => 'costaverde@automarket.com.pa',
            'whatsapp' => '50767470070',
            'schedule' => 'Lun-Sáb: 8:00am - 6:00pm | Dom: 9:00am - 4:00pm',
            'map_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.6435!2d-79.5239!3d8.9944!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8faca8c!2sAutomarket!5e0!3m2!1ses!2spa!4v1234567892',
            'sort_order' => 3,
            'active' => true
        ],
        [
            'id' => 4,
            'name' => 'David, Chiriquí',
            'address' => 'Av. Domingo Díaz, David, Chiriquí',
            'phone' => '(507) 279-2700',
            'email' => 'david@automarket.com.pa',
            'whatsapp' => '50767470070',
            'schedule' => 'Lun-Sáb: 8:00am - 6:00pm | Dom: 9:00am - 4:00pm',
            'map_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.6435!2d-82.4344!3d8.4003!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8faca8c!2sAutomarket!5e0!3m2!1ses!2spa!4v1234567893',
            'sort_order' => 4,
            'active' => true
        ],
        [
            'id' => 5,
            'name' => 'Penonomé, Coclé',
            'address' => 'Vía Interamericana, Penonomé, Coclé',
            'phone' => '(507) 279-2700',
            'email' => 'penonome@automarket.com.pa',
            'whatsapp' => '50767470070',
            'schedule' => 'Lun-Sáb: 8:00am - 6:00pm | Dom: 9:00am - 3:00pm',
            'map_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.6435!2d-80.3500!3d8.5180!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8faca8c!2sAutomarket!5e0!3m2!1ses!2spa!4v1234567894',
            'sort_order' => 5,
            'active' => true
        ]
    ];
    echo "Seeded 5 sucursales." . PHP_EOL;
} else {
    echo "Sucursales already seeded: " . count($data['seminuevos']['sucursales']) . PHP_EOL;
}

// Ensure contact_messages array exists
if (!isset($data['seminuevos']['contact_messages'])) {
    $data['seminuevos']['contact_messages'] = [];
    echo "Initialized seminuevos.contact_messages." . PHP_EOL;
}

file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "Done!" . PHP_EOL;
