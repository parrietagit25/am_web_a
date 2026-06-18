<?php
/**
 * Automarket Business Units Configuration
 */

return [
    'rentacar' => [
        'key' => 'rentacar',
        'label' => 'RENT A CAR',
        'slug' => 'rent-a-car.php',
        'color' => '#c51f17', // Rojo Automarket
        'logo_title' => 'Automarket',
        'logo_subtitle' => 'Rent A Car',
        'menu' => [
            [
                'label' => 'ALQUILERES',
                'link' => '#',
                'submenu' => [
                    ['label' => 'Nuestra flota', 'link' => '/flota.php'],
                    ['label' => 'Sucursales', 'link' => '/sucursales.php'],
                    ['label' => 'Términos y condiciones', 'link' => '/terminos-condiciones.php'],
                ],
            ],
            ['label' => 'SUCURSALES', 'link' => '/taller-sucursales.php'],
            ['label' => 'PAGA TU RESERVA', 'link' => '/pago-seguro.php'],
            ['label' => 'MI RESERVA', 'link' => '/mi-reserva.php'],
            ['label' => 'CONTACTOS', 'link' => '/contactos.php'],
        ],
        'activeClass' => 'active-rentacar',
        'heroTitle' => 'Te acompañamos a tu destino',
        'heroSubtitle' => 'Reserva tu vehículo en línea en segundos con la flota más moderna',
        'ctaText' => 'VER FLOTA'
    ],
    'seminuevos' => [
        'key' => 'seminuevos',
        'label' => 'VENTA DE AUTOS',
        'slug' => 'venta-autos.php',
        'color' => '#1f347f', // Azul
        'logo_title' => 'Automarket',
        'logo_subtitle' => 'Seminuevos',
        'menu' => [
            ['label' => 'FINANCIAMIENTO', 'link' => '/financiamiento.php'],
            ['label' => 'INVENTARIO', 'link' => '/inventario.php'],
            ['label' => 'NUESTRO EQUIPO', 'link' => '/nuestro-equipo.php'],
            ['label' => 'CONTACTOS', 'link' => '/contactos.php?unit=seminuevos'],
            ['label' => 'SUCURSALES', 'link' => '/seminuevos-sucursales.php']
        ],
        'activeClass' => 'active-seminuevos',
        'heroTitle' => 'Encuentra tu próximo auto seminuevo',
        'heroSubtitle' => 'Calidad, garantía y financiamiento a tu medida',
        'ctaText' => 'VER INVENTARIO',
        'ctaLink' => '/inventario.php'
    ],
    'leasing' => [
        'key' => 'leasing',
        'label' => 'LEASING OPERATIVO',
        'slug' => 'leasing.php',
        'color' => '#ef5752', // Coral / Rojo claro
        'logo_title' => 'Automarket',
        'logo_subtitle' => 'Leasing Operativo',
        'menu' => [
            ['label' => 'SUCURSALES', 'link' => '/leasing-sucursales.php'],
            ['label' => 'NUESTRA FLOTA', 'link' => '/leasing-flota.php'],
            ['label' => 'NUESTRO EQUIPO', 'link' => '/leasing-equipo.php'],
            ['label' => 'CONTACTOS', 'link' => '/leasing-contactos.php']
        ],
        'activeClass' => 'active-leasing',
        'heroTitle' => 'Optimiza la flota de tu empresa',
        'heroSubtitle' => 'Soluciones integrales de movilidad y gestión de flota comercial',
        'ctaText' => 'COTIZAR LEASING'
    ],
    'renting' => [
        'key' => 'renting',
        'label' => 'RENTING',
        'slug' => 'renting.php',
        'color' => '#5b5f96', // Azul grisáceo
        'logo_title' => 'Automarket',
        'logo_subtitle' => 'Renting',
        'menu' => [
            ['label' => 'NUESTROS SERVICIOS', 'link' => '/renting-servicios.php'],
            ['label' => 'SOBRE NOSOTROS', 'link' => '/renting-sobre-nosotros.php'],
            ['label' => 'CONTACTOS', 'link' => '/renting-contactos.php']
        ],
        'activeClass' => 'active-renting',
        'heroTitle' => 'Tu auto a largo plazo, sin preocupaciones',
        'heroSubtitle' => 'Todo incluido en una sola cuota mensual: mantenimiento, seguros y más',
        'ctaText' => ''
    ],
    'taller' => [
        'key' => 'taller',
        'label' => 'TALLER',
        'slug' => 'taller.php',
        'color' => '#918eb8', // Lila / gris
        'logo_title' => 'Automarket',
        'logo_subtitle' => 'Taller Autorizado',
        'menu' => [
            ['label' => 'SUCURSALES', 'link' => '/taller-sucursales.php'],
            ['label' => 'SOBRE NOSOTROS', 'link' => '/taller-sobre-nosotros.php'],
            ['label' => 'CONTACTOS', 'link' => '/contactos.php?unit=taller']
        ],
        'activeClass' => 'active-taller',
        'heroTitle' => 'Cuidamos tu vehículo como tú',
        'heroSubtitle' => 'Mantenimiento preventivo, correctivo y mecánicos certificados',
        'ctaText' => ''
    ]
];
