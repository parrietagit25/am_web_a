<?php
/**
 * Dynamic Content Management Service
 * Backed by site_data.json for database-less content updates.
 */

class ContentService {
    private $filePath;

    /** @var string Último error de saveAll() para mostrar en admin */
    private static string $lastSaveError = '';

    public function __construct() {
        $this->filePath = __DIR__ . '/../storage/site_data.json';
        $this->ensureMigration();
    }

    /**
     * Self-healing migration to upgrade JSON format to support thumbnails/rich content
     */
    private function ensureMigration() {
        if (file_exists($this->filePath)) {
            $jsonRaw = file_get_contents($this->filePath);
            $data = json_decode($jsonRaw, true);
            if ($data) {
                $modified = false;
                
                // news layout migration check
                if (isset($data['homepage']['noticias'][0]) && !array_key_exists('thumbnail', $data['homepage']['noticias'][0])) {
                    $defaultData = $this->getDefaultSiteData();
                    $data['homepage']['noticias'] = $defaultData['homepage']['noticias'];
                    $modified = true;
                }
                
                // fleet carousel settings migration check
                if (isset($data['homepage']) && !isset($data['homepage']['fleet_carousel'])) {
                    $defaultData = $this->getDefaultSiteData();
                    $data['homepage']['fleet_carousel'] = $defaultData['homepage']['fleet_carousel'];
                    $modified = true;
                }

                // fleet vehicles migration check
                if (isset($data['homepage']) && !isset($data['homepage']['vehicles'])) {
                    $defaultData = $this->getDefaultSiteData();
                    $data['homepage']['vehicles'] = $defaultData['homepage']['vehicles'];
                    $modified = true;
                }

                // sucursales migration check
                if (isset($data['homepage']) && !isset($data['homepage']['sucursales'])) {
                    $defaultData = $this->getDefaultSiteData();
                    $data['homepage']['sucursales'] = $defaultData['homepage']['sucursales'];
                    $modified = true;
                }

                require_once __DIR__ . '/../includes/business-units-registry.php';
                if (am_ensure_business_units_sort_order($data)) {
                    $modified = true;
                }
                if (isset($data['global']['business_units']) && is_array($data['global']['business_units'])) {
                    if (am_normalize_all_custom_unit_menus($data['global']['business_units'])) {
                        $modified = true;
                    }
                }
                
                if ($modified) {
                    $this->saveAll($data);
                }
            }
        }
    }

    /**
     * Get all content data
     * 
     * @return array
     */
    public function getAll() {
        if (!file_exists($this->filePath)) {
            $defaultData = $this->getDefaultSiteData();
            $this->saveAll($defaultData);
            return $defaultData;
        }

        $jsonRaw = file_get_contents($this->filePath);
        $data = json_decode($jsonRaw, true);
        
        if (!$data) {
            return $this->getDefaultSiteData();
        }

        return $data;
    }

    /**
     * Save all content data to JSON file
     * 
     * @param array $data
     * @return bool
     */
    public static function getLastSaveError(): string {
        return self::$lastSaveError;
    }

    public function saveAll(array $data) {
        self::$lastSaveError = '';
        $dir = dirname($this->filePath);

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                self::$lastSaveError = 'No se pudo crear la carpeta storage/.';
                am_log('ContentService::saveAll — ' . self::$lastSaveError, 'ERROR');
                return false;
            }
        }

        if (!is_writable($dir)) {
            @chmod($dir, 0775);
        }
        if (!is_writable($dir)) {
            self::$lastSaveError = 'La carpeta storage/ no es escribible por PHP (revisar permisos en el servidor).';
            am_log('ContentService::saveAll — ' . self::$lastSaveError, 'ERROR');
            return false;
        }

        if (file_exists($this->filePath) && !is_writable($this->filePath)) {
            @chmod($this->filePath, 0664);
        }

        $encodeFlags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $encodeFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $jsonRaw = json_encode($data, $encodeFlags);
        if ($jsonRaw === false) {
            self::$lastSaveError = 'No se pudo serializar los datos: ' . json_last_error_msg();
            am_log('ContentService::saveAll — json_encode falló: ' . json_last_error_msg(), 'ERROR');
            return false;
        }

        $tmpFile = $this->filePath . '.tmp.' . getmypid();
        $written = @file_put_contents($tmpFile, $jsonRaw, LOCK_EX);
        if ($written === false) {
            self::$lastSaveError = 'No se pudo escribir site_data.json (permisos). Ejecute en el servidor: chown www-data:www-data storage/site_data.json && chmod 664 storage/site_data.json';
            am_log('ContentService::saveAll — ' . self::$lastSaveError, 'ERROR');
            @unlink($tmpFile);
            return false;
        }

        if (!@rename($tmpFile, $this->filePath)) {
            @unlink($tmpFile);
            self::$lastSaveError = 'No se pudo actualizar site_data.json. Verifique que el usuario PHP (www-data) sea dueño del archivo.';
            am_log('ContentService::saveAll — rename falló en ' . $this->filePath, 'ERROR');
            return false;
        }

        @chmod($this->filePath, 0664);
        return true;
    }

    /**
     * Añade un mensaje de contacto Seminuevos con bloqueo de archivo (evita pérdidas por escrituras concurrentes).
     *
     * @param array<string, mixed> $message
     */
    public function appendSeminuevosContactMessage(array $message): bool
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!file_exists($this->filePath)) {
            $this->saveAll($this->getAll());
        }

        $fp = @fopen($this->filePath, 'c+');
        if ($fp === false) {
            am_log('appendSeminuevosContactMessage — no se pudo abrir site_data.json', 'ERROR');
            return false;
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            am_log('appendSeminuevosContactMessage — flock falló', 'ERROR');
            return false;
        }

        $size = filesize($this->filePath);
        $raw = $size > 0 ? fread($fp, $size) : '';
        $data = json_decode($raw ?: '{}', true);
        if (!is_array($data)) {
            flock($fp, LOCK_UN);
            fclose($fp);
            am_log('appendSeminuevosContactMessage — site_data.json con JSON inválido', 'ERROR');
            return false;
        }
        if (!isset($data['seminuevos']) || !is_array($data['seminuevos'])) {
            $data['seminuevos'] = [];
        }
        if (!isset($data['seminuevos']['contact_messages']) || !is_array($data['seminuevos']['contact_messages'])) {
            $data['seminuevos']['contact_messages'] = [];
        }

        $data['seminuevos']['contact_messages'][] = $message;

        $jsonRaw = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonRaw === false) {
            flock($fp, LOCK_UN);
            fclose($fp);
            return false;
        }

        rewind($fp);
        ftruncate($fp, 0);
        $ok = fwrite($fp, $jsonRaw) !== false;
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        if (!$ok) {
            am_log('appendSeminuevosContactMessage — fwrite falló', 'ERROR');
        }

        return $ok;
    }

    /**
     * Añade un mensaje de contacto Leasing Operativo (leasing.contact.messages).
     *
     * @param array<string, mixed> $message
     */
    public function appendLeasingContactMessage(array $message): bool
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!file_exists($this->filePath)) {
            $this->saveAll($this->getAll());
        }

        $fp = @fopen($this->filePath, 'c+');
        if ($fp === false) {
            am_log('appendLeasingContactMessage — no se pudo abrir site_data.json', 'ERROR');
            return false;
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return false;
        }

        $size = filesize($this->filePath);
        $raw = $size > 0 ? fread($fp, $size) : '';
        $data = json_decode($raw ?: '{}', true);
        if (!is_array($data)) {
            flock($fp, LOCK_UN);
            fclose($fp);
            am_log('appendLeasingContactMessage — JSON inválido', 'ERROR');
            return false;
        }
        if (!isset($data['leasing']) || !is_array($data['leasing'])) {
            $data['leasing'] = [];
        }
        if (!isset($data['leasing']['contact']) || !is_array($data['leasing']['contact'])) {
            $data['leasing']['contact'] = ['messages' => []];
        }
        if (!isset($data['leasing']['contact']['messages']) || !is_array($data['leasing']['contact']['messages'])) {
            $data['leasing']['contact']['messages'] = [];
        }

        $data['leasing']['contact']['messages'][] = $message;

        $jsonRaw = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonRaw === false) {
            flock($fp, LOCK_UN);
            fclose($fp);
            return false;
        }

        rewind($fp);
        ftruncate($fp, 0);
        $ok = fwrite($fp, $jsonRaw) !== false;
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        if (!$ok) {
            am_log('appendLeasingContactMessage — fwrite falló', 'ERROR');
        }

        return $ok;
    }

    /**
     * Añade un mensaje de contacto Renting (renting.contact.messages).
     *
     * @param array<string, mixed> $message
     */
    public function appendRentingContactMessage(array $message): bool
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!file_exists($this->filePath)) {
            $this->saveAll($this->getAll());
        }

        $fp = @fopen($this->filePath, 'c+');
        if ($fp === false) {
            am_log('appendRentingContactMessage — no se pudo abrir site_data.json', 'ERROR');
            return false;
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return false;
        }

        $size = filesize($this->filePath);
        $raw = $size > 0 ? fread($fp, $size) : '';
        $data = json_decode($raw ?: '{}', true);
        if (!is_array($data)) {
            flock($fp, LOCK_UN);
            fclose($fp);
            am_log('appendRentingContactMessage — JSON inválido', 'ERROR');
            return false;
        }
        if (!isset($data['renting']) || !is_array($data['renting'])) {
            $data['renting'] = [];
        }
        if (!isset($data['renting']['contact']) || !is_array($data['renting']['contact'])) {
            $data['renting']['contact'] = ['messages' => []];
        }
        if (!isset($data['renting']['contact']['messages']) || !is_array($data['renting']['contact']['messages'])) {
            $data['renting']['contact']['messages'] = [];
        }

        $data['renting']['contact']['messages'][] = $message;

        $jsonRaw = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonRaw === false) {
            flock($fp, LOCK_UN);
            fclose($fp);
            return false;
        }

        rewind($fp);
        ftruncate($fp, 0);
        $ok = fwrite($fp, $jsonRaw) !== false;
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        if (!$ok) {
            am_log('appendRentingContactMessage — fwrite falló', 'ERROR');
        }

        return $ok;
    }

    /**
     * Retrieve deep values using dot notation
     * 
     * @param string $key e.g., 'homepage.hero.title'
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null) {
        $data = $this->getAll();
        $parts = explode('.', $key);
        
        foreach ($parts as $part) {
            if (is_array($data) && array_key_exists($part, $data)) {
                $data = $data[$part];
            } else {
                return $default;
            }
        }

        return $data;
    }

    /**
     * Securely handle image uploads (returns target URL or false)
     * 
     * @param array $fileInfo $_FILES['field_name']
     * @param string $prefix
     * @return string|bool
     */
    public function uploadImage($fileInfo, $prefix = 'upload_', bool $strictExtension = false) {
        if (!isset($fileInfo) || $fileInfo['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        if (empty($fileInfo['tmp_name']) || !is_uploaded_file($fileInfo['tmp_name'])) {
            return false;
        }

        // Limit size to 12MB
        if (($fileInfo['size'] ?? 0) > 12 * 1024 * 1024) {
            return false;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $detectedMime = '';

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detectedMime = finfo_file($finfo, $fileInfo['tmp_name']) ?: '';
                finfo_close($finfo);
            }
        }

        if ($detectedMime === '' || !in_array($detectedMime, $allowedTypes, true)) {
            $imageInfo = @getimagesize($fileInfo['tmp_name']);
            $detectedMime = $imageInfo['mime'] ?? '';
        }

        if (!in_array($detectedMime, $allowedTypes, true)) {
            return false;
        }

        $uploadsDir = __DIR__ . '/../public/assets/img/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $extension = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if ($strictExtension && (empty($extension) || !in_array(strtolower($extension), $allowedExtensions, true))) {
            return false;
        }
        if (empty($extension) || !in_array(strtolower($extension), $allowedExtensions, true)) {
            $extMap = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp'
            ];
            $extension = $extMap[$detectedMime] ?? 'png';
        }

        $fileName = $prefix . uniqid() . '.' . strtolower($extension);
        $targetFile = $uploadsDir . '/' . $fileName;

        if (move_uploaded_file($fileInfo['tmp_name'], $targetFile)) {
            return '/assets/img/uploads/' . $fileName;
        }

        return false;
    }

    /**
     * Hardcoded default fallback website values
     * 
     * @return array
     */
    private function getDefaultSiteData() {
        // Load original business units configuration
        $businessUnits = require __DIR__ . '/../config/business-units.php';

        return [
            'global' => [
                'whatsapp_number' => '5072792700',
                'whatsapp_label' => '¿En qué podemos ayudarte?',
                'whatsapp_vehicle_prefix' => 'Hola, estoy interesado en el',
                'phone_display' => '(507) 279-2700',
                'toll_free' => '1-866-700-9904',
                'email' => 'info@automarket.com.pa',
                'address' => 'Vía España, Edificio Automarket, Ciudad de Panamá',
                'footer_copyright' => 'Automarket. Todos los derechos reservados.',
                'business_units' => $businessUnits
            ],
            'homepage' => [
                'hero' => [
                    'title' => 'Te acompañamos a tu destino',
                    'subtitle' => 'Reserva tu vehículo en línea en segundos, con la flota más segura del país.',
                    'image_url' => 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?q=80&w=1920&auto=format&fit=crop'
                ],
                'featured' => [
                    'active' => true,
                    'badge' => 'Recomendado',
                    'title' => 'Feria de David 2026',
                    'heading' => 'Feria Internacional de David 2026: tradición, desarrollo y crecimiento en Chiriquí',
                    'description' => 'La Feria Internacional de David 2026 es uno de los eventos comerciales, agroindustriales y culturales más importantes de Panamá. En su 69.ª edición, se celebrará del 12 al 22 de marzo de 2026 en la ciudad de David, provincia de Chiriquí, consolidándose como el principal punto de encuentro para empresarios, productores, inversionistas y familias de todo el país.',
                    'button_text' => 'Ver mas: Feria de David 2026',
                    'button_link' => '/blog.php',
                    'image_url' => '/assets/img/feria_david.webp'
                ],
                'faqs' => [],
                'social_links' => [],
                'contact' => [
                    'phone_display' => '',
                    'whatsapp_number' => '',
                    'email' => '',
                ],
                'show_payment_methods' => true,
                'noticias' => [
                    [
                        'id' => 1,
                        'date' => '25 Mayo, 2026',
                        'title' => 'Automarket Asistencia AMAS',
                        'desc' => 'Asistencia para viajeros',
                        'link_text' => 'Ver Más: Asistencia AMAS',
                        'thumbnail' => '/assets/img/uploads/amas.png',
                        'banner' => '/assets/img/uploads/amas.png',
                        'subheading' => 'Paquete de Asistencia al Viajero',
                        'description' => 'El **Paquete de Asistencia al Viajero AMAS** le brinda protección integral durante el período de alquiler de su vehículo, con cobertura de hasta 20 días consecutivos. Este servicio garantiza respaldo en situaciones médicas y de emergencia ocurridas fuera del vehículo, sin deducibles y con atención disponible las 24 horas, los 7 días de la semana.',
                        'content' => "La cobertura incluye:\n\n• **Gastos médicos** por accidentes o enfermedades ocurridos dentro y fuera del vehículo.\n• **Traslado médico** de emergencia en ambulancia.\n• **Cobertura odontológica** de urgencia.\n• **Asistencia legal** telefónica y representación preliminar."
                    ],
                    [
                        'id' => 2,
                        'date' => '18 Mayo, 2026',
                        'title' => '¡Cuando vuelas con Fly Trip tu alquiler tiene beneficios!',
                        'desc' => '¡Cuando vuelas con Fly Trip tu alquiler tiene beneficios!',
                        'link_text' => 'Ver Más: Beneficios Fly Trip',
                        'thumbnail' => '/assets/img/uploads/flytrip.png',
                        'banner' => '/assets/img/uploads/flytrip.png',
                        'subheading' => 'Beneficios Exclusivos con Fly Trip',
                        'description' => 'Alquila tu vehículo con Automarket y obtén descuentos especiales y beneficios adicionales al presentar tu pase de abordar o reservación activa de Fly Trip. Disfruta de la mejor experiencia de movilidad conectada.',
                        'content' => "Detalles del beneficio:\n\n• **10% de descuento** en la tarifa base de alquiler diaria.\n• **Conductor adicional** gratuito durante toda tu renta.\n• **Upgrades** de categoría sujetos a disponibilidad en sucursal.\n• **Millas o puntos** de viajero acumulables en tu cuenta asociada."
                    ],
                    [
                        'id' => 3,
                        'date' => '10 Mayo, 2026',
                        'title' => 'HolaFly',
                        'desc' => 'Alquila con Automarket y mantente comunicado',
                        'link_text' => 'Ver Más: HolaFly',
                        'thumbnail' => '/assets/img/uploads/holafly.png',
                        'banner' => '/assets/img/uploads/holafly.png',
                        'subheading' => 'Disfruta de datos ilimitados en tus viajes',
                        'description' => 'Mantente siempre conectado con tus seres queridos y apps de navegación favoritas. En alianza con HolaFly, te ofrecemos un descuento del 5% en la compra de tu tarjeta eSIM de datos ilimitados para usar en tus viajes fuera de Panamá.',
                        'content' => "Cómo redimir tu código:\n\n1. Ingresa a la web de HolaFly desde el enlace promocional.\n2. Selecciona tu destino y plan de datos eSIM.\n3. Aplica el código de descuento: **AMRAC** antes de finalizar el pago.\n4. Recibe tu código QR de activación por correo electrónico de forma instantánea."
                    ]
                ],
                'opiniones' => [
                    [
                        'id' => 1,
                        'name' => 'Eduardo Alvarado',
                        'sucursal' => 'Sucursal: Tocumen (PTY)',
                        'stars' => 5,
                        'avatar' => 'EA',
                        'text' => '"Excelente servicio desde la entrega en el aeropuerto. El auto estaba impecable y la devolución fue sumamente rápida. Definitivamente volveré a reservar con ellos."'
                    ],
                    [
                        'id' => 2,
                        'name' => 'María Camila Ortíz',
                        'sucursal' => 'Sucursal: Vía España',
                        'stars' => 5,
                        'avatar' => 'MC',
                        'text' => '"Precios justos, sin cobros ocultos a la entrega. Alquilamos un SUV para ir a Coronado en familia y estuvo perfecto todo el viaje. Recomendados al 100%."'
                    ],
                    [
                        'id' => 3,
                        'name' => 'Roberto Gómez',
                        'sucursal' => 'Sucursal: David, Chiriquí',
                        'stars' => 5,
                        'avatar' => 'RG',
                        'text' => '"El Pick-Up que rentamos estaba en perfectas condiciones mecánicas. Excelente para recorrer las fincas de Boquete. Atención muy amable de su personal en David."'
                    ]
                ],
                'fleet_carousel' => [
                    'autoplay' => true,
                    'direction' => 'right',
                    'interval' => 3000,
                    'items' => [
                        [
                            'id' => 1,
                            'category' => 'Sedanes',
                            'label' => 'Sedanes',
                            'image_url' => '/assets/img/carrusel/sedan.webp',
                            'sort_order' => 10,
                        ],
                        [
                            'id' => 2,
                            'category' => 'SUV',
                            'label' => 'SUV',
                            'image_url' => '/assets/img/carrusel/suv.webp',
                            'sort_order' => 20,
                        ],
                        [
                            'id' => 3,
                            'category' => 'SUV Mini',
                            'label' => 'SUV compacto',
                            'image_url' => '/assets/img/carrusel/suvmini.webp',
                            'sort_order' => 30,
                        ],
                        [
                            'id' => 4,
                            'category' => 'Familiares',
                            'label' => 'Familiares',
                            'image_url' => '/assets/img/carrusel/familiares.webp',
                            'sort_order' => 40,
                        ],
                        [
                            'id' => 5,
                            'category' => 'Comerciales',
                            'label' => 'Comerciales',
                            'image_url' => '/assets/img/carrusel/comerciales.webp',
                            'sort_order' => 50,
                        ],
                        [
                            'id' => 6,
                            'category' => 'Promociones',
                            'label' => 'Promociones',
                            'image_url' => '/assets/img/carrusel/promociones.webp',
                            'sort_order' => 60,
                        ]
                    ]
                ],
                'vehicles' => [
                    [
                        'id' => 1,
                        'name' => 'Kia Picante o similar',
                        'category' => 'Sedanes',
                        'image_url' => '/assets/img/uploads/kia_picante_default.png',
                        'doors' => '4 Puertas',
                        'passengers' => '5 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Manual',
                        'traction' => 'Tracción Delantera',
                        'windows' => true,
                        'license_type' => 'Licencia Tipo C',
                        'extras' => 'Ventanas Eléctricas'
                    ],
                    [
                        'id' => 2,
                        'name' => 'Hyundai Grand I-10 o similar #PROMO',
                        'category' => 'Promociones',
                        'image_url' => '/assets/img/uploads/kia_picante_default.png',
                        'doors' => '4 Puertas',
                        'passengers' => '5 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Automática',
                        'traction' => 'Tracción Delantera',
                        'windows' => true,
                        'license_type' => 'Licencia Tipo C',
                        'extras' => 'Ventanas Eléctricas'
                    ],
                    [
                        'id' => 3,
                        'name' => 'Hyundai Accent o similar #PROMO',
                        'category' => 'Promociones',
                        'image_url' => '/assets/img/uploads/hyundai_accent_default.png',
                        'doors' => '4 Puertas',
                        'passengers' => '5 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Automática',
                        'traction' => 'Tracción Delantera',
                        'windows' => true,
                        'license_type' => 'Licencia Tipo C',
                        'extras' => 'Ventanas Eléctricas'
                    ],
                    [
                        'id' => 4,
                        'name' => 'Geely GX3 Pro o similar',
                        'category' => 'SUV Mini',
                        'image_url' => '/assets/img/uploads/hyundai_accent_default.png',
                        'doors' => '4 Puertas',
                        'passengers' => '5 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Automática',
                        'traction' => 'Tracción Delantera',
                        'windows' => false,
                        'license_type' => 'Licencia Tipo C',
                        'extras' => 'MP3 Player'
                    ],
                    [
                        'id' => 5,
                        'name' => 'Hyundai Venue o similar #PROMO',
                        'category' => 'Promociones',
                        'image_url' => '/assets/img/uploads/toyota_fortuner_default.png',
                        'doors' => '5 Puertas',
                        'passengers' => '5 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Automática',
                        'traction' => 'Tracción Delantera',
                        'windows' => true,
                        'license_type' => 'Licencia Tipo C',
                        'extras' => 'MP3 Player'
                    ],
                    [
                        'id' => 6,
                        'name' => 'Hyundai Creta o similar',
                        'category' => 'SUV',
                        'image_url' => '/assets/img/uploads/toyota_fortuner_default.png',
                        'doors' => '5 Puertas',
                        'passengers' => '5 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Automática',
                        'traction' => 'Tracción Delantera',
                        'windows' => true,
                        'license_type' => 'Licencia Tipo C',
                        'extras' => ''
                    ],
                    [
                        'id' => 7,
                        'name' => 'Toyota Corolla Cross o similar',
                        'category' => 'SUV',
                        'image_url' => '/assets/img/uploads/toyota_fortuner_default.png',
                        'doors' => '5 Puertas',
                        'passengers' => '5 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Automática',
                        'traction' => 'Tracción Delantera',
                        'windows' => false,
                        'license_type' => 'Licencia Tipo C',
                        'extras' => 'Frenos ABS, MP3 Player'
                    ],
                    [
                        'id' => 8,
                        'name' => 'Hyundai Tucson o similar',
                        'category' => 'SUV',
                        'image_url' => '/assets/img/uploads/toyota_fortuner_default.png',
                        'doors' => '5 Puertas',
                        'passengers' => '5 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Automática',
                        'traction' => 'Tracción Delantera',
                        'windows' => true,
                        'license_type' => 'Licencia Tipo C',
                        'extras' => ''
                    ],
                    [
                        'id' => 9,
                        'name' => 'Toyota Rav-4 o similar',
                        'category' => 'SUV',
                        'image_url' => '/assets/img/uploads/toyota_fortuner_default.png',
                        'doors' => '5 Puertas',
                        'passengers' => '5 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Automática',
                        'traction' => 'Tracción Delantera',
                        'windows' => true,
                        'license_type' => 'Licencia Tipo C',
                        'extras' => ''
                    ],
                    [
                        'id' => 10,
                        'name' => 'Toyota Hilux o similar',
                        'category' => 'Comerciales',
                        'image_url' => '/assets/img/uploads/toyota_hilux_default.png',
                        'doors' => '4 Puertas',
                        'passengers' => '5 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Manual',
                        'traction' => 'Tracción en las cuatro ruedas',
                        'windows' => true,
                        'license_type' => 'Licencia Tipo C',
                        'extras' => ''
                    ],
                    [
                        'id' => 11,
                        'name' => 'Toyota Hilux o similar #Automatico',
                        'category' => 'Comerciales',
                        'image_url' => '/assets/img/uploads/toyota_hilux_default.png',
                        'doors' => '4 Puertas',
                        'passengers' => '5 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Automática',
                        'traction' => 'Tracción en las cuatro ruedas',
                        'windows' => true,
                        'license_type' => 'Licencia Tipo C',
                        'extras' => ''
                    ],
                    [
                        'id' => 12,
                        'name' => 'Toyota Fortuner o similar',
                        'category' => 'SUV',
                        'image_url' => '/assets/img/uploads/toyota_fortuner_default.png',
                        'doors' => '5 Puertas',
                        'passengers' => '7 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Automática',
                        'traction' => 'Tracción en las cuatro ruedas',
                        'windows' => true,
                        'license_type' => 'Licencia Tipo C',
                        'extras' => ''
                    ],
                    [
                        'id' => 13,
                        'name' => 'Gac GS8 o similar',
                        'category' => 'Familiares',
                        'image_url' => '/assets/img/uploads/toyota_fortuner_default.png',
                        'doors' => '5 Puertas',
                        'passengers' => '7 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Automática',
                        'traction' => 'Tracción Delantera',
                        'windows' => true,
                        'license_type' => 'Licencia Tipo C',
                        'extras' => '3 Grandes (Maletas), Power Steering, Frenos ABS, MP3 Player'
                    ],
                    [
                        'id' => 14,
                        'name' => 'Toyota Prado o similar',
                        'category' => 'SUV',
                        'image_url' => '/assets/img/uploads/toyota_fortuner_default.png',
                        'doors' => '5 Puertas',
                        'passengers' => '7 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Automática',
                        'traction' => 'Tracción en las cuatro ruedas',
                        'windows' => true,
                        'license_type' => 'Licencia Tipo C',
                        'extras' => ''
                    ],
                    [
                        'id' => 15,
                        'name' => 'Kia Carnival o similar',
                        'category' => 'Familiares',
                        'image_url' => '/assets/img/uploads/toyota_fortuner_default.png',
                        'doors' => '5 Puertas',
                        'passengers' => '7 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Automática',
                        'traction' => 'Ninguno',
                        'windows' => false,
                        'license_type' => 'Licencia Tipo C',
                        'extras' => 'Power Steering, MP3 Player'
                    ],
                    [
                        'id' => 16,
                        'name' => 'Hyundai Staria o similar',
                        'category' => 'Familiares',
                        'image_url' => '/assets/img/uploads/toyota_fortuner_default.png',
                        'doors' => '5 Puertas',
                        'passengers' => '11 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Automática',
                        'traction' => 'Ninguno',
                        'windows' => true,
                        'license_type' => 'Licencia Tipo D',
                        'extras' => 'MP3 Player'
                    ],
                    [
                        'id' => 17,
                        'name' => 'Toyota Hi Ace o similar',
                        'category' => 'Familiares',
                        'image_url' => '/assets/img/uploads/toyota_hilux_default.png',
                        'doors' => '4 Puertas',
                        'passengers' => '14 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Manual',
                        'traction' => 'Tracción Delantera',
                        'windows' => false,
                        'license_type' => 'Licencia Tipo D',
                        'extras' => 'MP3 Player'
                    ],
                    [
                        'id' => 18,
                        'name' => 'Toyota Coaster #Todoscaben',
                        'category' => 'Familiares',
                        'image_url' => '/assets/img/uploads/toyota_hilux_default.png',
                        'doors' => '2 Puertas',
                        'passengers' => 'Hasta 30 pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Manual',
                        'traction' => 'Ninguno',
                        'windows' => false,
                        'license_type' => 'Licencia Tipo E3',
                        'extras' => 'MP3 Player'
                    ],
                    [
                        'id' => 19,
                        'name' => 'Toyota Panel Hi Ace o similar',
                        'category' => 'Comerciales',
                        'image_url' => '/assets/img/uploads/toyota_hilux_default.png',
                        'doors' => '4 Puertas',
                        'passengers' => '3 Pasajeros',
                        'ac' => true,
                        'transmission' => 'Transmisión Manual',
                        'traction' => 'Tracción Delantera',
                        'windows' => false,
                        'license_type' => 'Licencia Tipo D',
                        'extras' => 'Power Steering, MP3 Player'
                    ]
                ],
                'sucursales' => [
                    [
                        'id' => 1,
                        'name' => 'Aeropuerto Internacional de Tocumen T1',
                        'location' => 'Avenida Domingo Diaz',
                        'address' => 'Aeropuerto Internacional de Tocumen T1',
                        'schedule' => 'Lunes a Domingo: 5:00am a 11:30pm',
                        'phone' => '5072366785',
                        'lat' => '9.066325',
                        'lng' => '-79.387593'
                    ],
                    [
                        'id' => 2,
                        'name' => 'Boquete',
                        'location' => 'Av. Central, Bajo Boquete',
                        'address' => 'Av. Central, Bajo Boquete',
                        'schedule' => 'Lunes a Sábado: 7:00am a 3:00pm',
                        'phone' => '5077201929',
                        'lat' => '8.780183',
                        'lng' => '-82.433292'
                    ],
                    [
                        'id' => 3,
                        'name' => 'Vía España (Torres Alba)',
                        'location' => 'Calle Torres Alba, El Cangrejo',
                        'address' => 'Hotel Torres Alba, lobby principal, El Cangrejo',
                        'schedule' => 'Lunes a Viernes: 8:00am a 5:00pm, Sábado: 8:00am a 1:00pm',
                        'phone' => '5072792700',
                        'lat' => '8.986518',
                        'lng' => '-79.528439'
                    ],
                    [
                        'id' => 4,
                        'name' => 'David (Aeropuerto Enrique Malek)',
                        'location' => 'Aeropuerto Internacional Enrique Malek, David',
                        'address' => 'Aeropuerto Enrique Malek, David, Chiriquí',
                        'schedule' => 'Lunes a Domingo: 7:00am a 9:00pm',
                        'phone' => '5077211475',
                        'lat' => '8.391782',
                        'lng' => '-82.434685'
                    ]
                ]
            ]
        ];
    }
}
