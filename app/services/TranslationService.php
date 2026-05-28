<?php
/**
 * Translation / i18n service
 */

class TranslationService {
    private static $instance = null;
    private $lang = 'es';
    private $strings = [];
    private $defaults = ['es' => [], 'en' => []];

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->defaults['es'] = require __DIR__ . '/../lang/defaults_es.php';
        $this->defaults['en'] = require __DIR__ . '/../lang/defaults_en.php';
        $this->resolveLanguage();
        $this->loadStrings();
    }

    private function resolveLanguage(): void {
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en'], true)) {
            $this->lang = $_GET['lang'];
            $_SESSION['lang'] = $this->lang;
            setcookie('am_lang', $this->lang, time() + 365 * 24 * 3600, '/');
        } elseif (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], ['es', 'en'], true)) {
            $this->lang = $_SESSION['lang'];
        } elseif (!empty($_COOKIE['am_lang']) && in_array($_COOKIE['am_lang'], ['es', 'en'], true)) {
            $this->lang = $_COOKIE['am_lang'];
            $_SESSION['lang'] = $this->lang;
        }
    }

    private function loadStrings(): void {
        $base = $this->defaults[$this->lang] ?? $this->defaults['es'];
        $this->strings = $base;

        if (class_exists('ContentService')) {
            $contentService = new ContentService();
            $custom = $contentService->get('translations.' . $this->lang, []);
            if (is_array($custom)) {
                foreach ($custom as $key => $value) {
                    if ($key !== '' && $value !== '') {
                        $this->strings[$key] = $value;
                    }
                }
            }
        }
    }

    public function getLang(): string {
        return $this->lang;
    }

    public function translate(string $key, ?string $fallback = null): string {
        if (isset($this->strings[$key]) && $this->strings[$key] !== '') {
            return $this->strings[$key];
        }
        if ($fallback !== null) {
            return $fallback;
        }
        $esDefault = $this->defaults['es'][$key] ?? null;
        if ($esDefault !== null && $this->lang === 'es') {
            return $esDefault;
        }
        return $key;
    }

    public function getAllKeys(): array {
        $keys = array_unique(array_merge(
            array_keys($this->defaults['es']),
            array_keys($this->defaults['en'])
        ));
        sort($keys);
        return $keys;
    }

    public function getDictionaryForAdmin(): array {
        $result = [];
        foreach ($this->getAllKeys() as $key) {
            $result[$key] = [
                'es' => $this->defaults['es'][$key] ?? '',
                'en' => $this->defaults['en'][$key] ?? '',
            ];
        }
        if (class_exists('ContentService')) {
            $contentService = new ContentService();
            $siteData = $contentService->getAll();
            foreach (['es', 'en'] as $lang) {
                $custom = $siteData['translations'][$lang] ?? [];
                if (!is_array($custom)) {
                    continue;
                }
                foreach ($custom as $key => $value) {
                    if (!isset($result[$key])) {
                        $result[$key] = ['es' => '', 'en' => ''];
                    }
                    if ($value !== '') {
                        $result[$key][$lang] = $value;
                    }
                }
            }
        }
        return $result;
    }

    /** Map menu label from config to translation key */
    public static function menuKeyForLabel(string $label): ?string {
        $map = [
            'ALQUILERES' => 'menu.alquileres',
            'SUCURSALES' => 'menu.sucursales',
            'PAGA TU RESERVA' => 'menu.paga_reserva',
            'FINANCIAMIENTO' => 'menu.financiamiento',
            'INVENTARIO' => 'menu.inventario',
            'NUESTRO EQUIPO' => 'menu.nuestro_equipo',
            'CONTACTOS Y SUCURSALES' => 'menu.contactos_sucursales',
            'NUESTRA FLOTA' => 'menu.nuestra_flota',
            'CONTACTOS' => 'menu.contactos',
            'NUESTROS SERVICIOS' => 'menu.nuestros_servicios',
            'SOBRE NOSOTROS' => 'menu.sobre_nosotros',
        ];
        return $map[strtoupper(trim($label))] ?? null;
    }

    public static function unitKeyFor(string $unitKey): string {
        return 'unit.' . $unitKey;
    }
}
