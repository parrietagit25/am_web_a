<?php
/**
 * Parsers para respuestas del usuario en flujos guiados del chatbot.
 */

class ChatbotGuideParser {
    /** @var array<string, int> */
    private static array $monthsEs = [
        'enero' => 1, 'ene' => 1,
        'febrero' => 2, 'feb' => 2,
        'marzo' => 3, 'mar' => 3,
        'abril' => 4, 'abr' => 4,
        'mayo' => 5, 'may' => 5,
        'junio' => 6, 'jun' => 6,
        'julio' => 7, 'jul' => 7,
        'agosto' => 8, 'ago' => 8,
        'septiembre' => 9, 'sep' => 9, 'sept' => 9, 'setiembre' => 9,
        'octubre' => 10, 'oct' => 10,
        'noviembre' => 11, 'nov' => 11,
        'diciembre' => 12, 'dic' => 12,
    ];

    /** @var array<string, int> */
    private static array $monthsEn = [
        'january' => 1, 'jan' => 1,
        'february' => 2, 'feb' => 2,
        'march' => 3, 'mar' => 3,
        'april' => 4, 'apr' => 4,
        'may' => 5,
        'june' => 6, 'jun' => 6,
        'july' => 7, 'jul' => 7,
        'august' => 8, 'aug' => 8,
        'september' => 9, 'sep' => 9, 'sept' => 9,
        'october' => 10, 'oct' => 10,
        'november' => 11, 'nov' => 11,
        'december' => 12, 'dec' => 12,
    ];

    public static function parseYesNo(string $text): ?bool {
        $t = mb_strtolower(trim($text));
        if (preg_match('/^(s[ií]|yes|y|claro|correcto|afirmativo|ok|vale|de acuerdo)\b/u', $t)) {
            return true;
        }
        if (preg_match('/^(no|n|negativo|cancelar)\b/u', $t)) {
            return false;
        }
        return null;
    }

    public static function isCancel(string $text): bool {
        return (bool) preg_match('/\b(cancelar|cancel|salir|abortar|detener|parar)\b/ui', $text);
    }

    public static function parseEmail(string $text): ?string {
        if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $text, $m)) {
            $e = filter_var($m[0], FILTER_VALIDATE_EMAIL);
            return $e ?: null;
        }
        return null;
    }

    public static function parsePhone(string $text): string {
        return trim(preg_replace('/[^\d+\-\s()]/', '', $text));
    }

    public static function parseDate(string $text): ?string {
        $raw = trim($text);
        if ($raw === '') {
            return null;
        }

        $relative = self::parseRelativeDate($raw);
        if ($relative !== null) {
            return $relative;
        }

        $normalized = self::normalizeDateText($raw);

        $fromNamed = self::parseNamedMonthDate($normalized);
        if ($fromNamed !== null) {
            return $fromNamed;
        }

        $fromNamed = self::parseNamedMonthDate(self::foldAccents(mb_strtolower($raw)));
        if ($fromNamed !== null) {
            return $fromNamed;
        }

        $compact = preg_replace('/[^\d]/', '', $raw);
        if (strlen($compact) === 8) {
            $y = (int) substr($compact, 0, 4);
            $mo = (int) substr($compact, 4, 2);
            $d = (int) substr($compact, 6, 2);
            if (self::validDate($y, $mo, $d)) {
                return sprintf('%04d-%02d-%02d', $y, $mo, $d);
            }
            $d = (int) substr($compact, 0, 2);
            $mo = (int) substr($compact, 2, 2);
            $y = (int) substr($compact, 4, 4);
            if (self::validDate($y, $mo, $d)) {
                return sprintf('%04d-%02d-%02d', $y, $mo, $d);
            }
        }

        if (preg_match('/^(\d{4})[\s.\-\/]+(\d{1,2})[\s.\-\/]+(\d{1,2})$/', $normalized, $m)) {
            return self::formatIfValid((int) $m[1], (int) $m[2], (int) $m[3]);
        }

        if (preg_match('/^(\d{1,2})[\s.\-\/]+(\d{1,2})[\s.\-\/]+(\d{4})$/', $normalized, $m)) {
            return self::formatIfValid((int) $m[3], (int) $m[2], (int) $m[1]);
        }

        if (preg_match('/^(\d{1,2})[\s.\-\/]+(\d{1,2})[\s.\-\/]+(\d{2})$/', $normalized, $m)) {
            return self::formatIfValid(2000 + (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        // Voz: "3005 del 2026" → 30/05/2026
        if (preg_match('/(\d{1,2})(\d{2})\s*(?:del\s+)?(\d{4})/u', mb_strtolower($raw), $m)) {
            $result = self::formatIfValid((int) $m[3], (int) $m[2], (int) $m[1]);
            if ($result !== null) {
                return $result;
            }
        }

        // Solo dígitos separados: "2026 05 30"
        if (preg_match('/(\d{4})\s+(\d{1,2})\s+(\d{1,2})/', $normalized, $m)) {
            return self::formatIfValid((int) $m[1], (int) $m[2], (int) $m[3]);
        }

        return null;
    }

    private static function parseRelativeDate(string $text): ?string {
        $t = self::foldAccents(mb_strtolower(trim($text)));
        // "de la mañana" = hora, no "mañana" como día
        if (preg_match('/\b(?:de|por|en)\s+la\s+(?:manana|tarde|noche)\b/u', $t)) {
            return null;
        }
        if (preg_match('/\b(pasado\s+manana)\b/u', $t)) {
            return date('Y-m-d', strtotime('+2 days'));
        }
        if (preg_match('/\b(manana|tomorrow)\b/u', $t)) {
            return date('Y-m-d', strtotime('+1 day'));
        }
        if (preg_match('/\b(hoy|today)\b/u', $t)) {
            return date('Y-m-d');
        }
        return null;
    }

    private static function foldAccents(string $text): string {
        return strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'ñ' => 'n',
        ]);
    }

    private static function normalizeDateText(string $text): string {
        $t = self::foldAccents(mb_strtolower(trim($text)));
        $t = str_replace([',', '—'], ' ', $t);
        $t = preg_replace('/\b(el|la|los|las|día|dia|del|de|este|mes|año|ano|year|para|retirar|devolver|quiero|me|al|en|un|una|vehículo|vehiculo|auto|fecha|sería|seria)\b/u', ' ', $t);
        $t = preg_replace('/\s+/', ' ', trim($t));
        return preg_replace('/\s*([.\-\/])\s*/', '$1', $t);
    }

    private static function parseNamedMonthDate(string $text): ?string {
        $t = trim($text);
        if ($t === '') {
            return null;
        }

        $months = array_merge(self::$monthsEs, self::$monthsEn);
        $monthPattern = implode('|', array_map('preg_quote', array_keys($months)));

        // 30 de mayo de 2026 / 30 mayo 2026 / día 30 mayo del 2026
        if (preg_match('/(\d{1,2})\s+(?:de\s+)?(?:' . $monthPattern . ')\s+(?:de\s+|del\s+)?(\d{4})/u', $t, $m)) {
            $mo = self::monthFromToken($m[2], $months);
            if ($mo !== null) {
                return self::formatIfValid((int) $m[3], $mo, (int) $m[1]);
            }
        }

        // mayo 30 2026 / mayo 30 de 2026
        if (preg_match('/(?:' . $monthPattern . ')\s+(\d{1,2})(?:\s+(?:de\s+|del\s+)?(\d{4}))?/u', $t, $m)) {
            $monthToken = preg_replace('/\s+\d.*/', '', $t);
            $monthToken = preg_replace('/^.*?\b(' . $monthPattern . ')\b/u', '$1', $t);
            $mo = self::monthFromToken($monthToken, $months);
            if ($mo !== null) {
                $y = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : (int) date('Y');
                return self::formatIfValid($y, $mo, (int) $m[1]);
            }
        }

        // 30 de mayo (sin año)
        if (preg_match('/(\d{1,2})\s+(?:de\s+)?(' . $monthPattern . ')(?:\s|$)/u', $t, $m)) {
            $mo = self::monthFromToken($m[2], $months);
            if ($mo !== null) {
                $y = (int) date('Y');
                $candidate = self::formatIfValid($y, $mo, (int) $m[1]);
                if ($candidate !== null && $candidate < date('Y-m-d')) {
                    $candidate = self::formatIfValid($y + 1, $mo, (int) $m[1]);
                }
                return $candidate;
            }
        }

        return null;
    }

    /** @param array<string, int> $months */
    private static function monthFromToken(string $token, array $months): ?int {
        $token = mb_strtolower(trim($token));
        return $months[$token] ?? null;
    }

    private static function formatIfValid(int $y, int $m, int $d): ?string {
        if (!self::validDate($y, $m, $d)) {
            return null;
        }
        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    }

    public static function parseTime(string $text, string $default = '10:00'): string {
        $t = trim($text);
        if (preg_match('/(\d{1,2}):(\d{2})/', $t, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            if ($h >= 0 && $h <= 23 && $min >= 0 && $min <= 59) {
                return sprintf('%02d:%02d', $h, $min);
            }
        }
        if (preg_match('/(\d{1,2})\s*h(?:oras?)?/iu', $t, $m)) {
            $h = (int) $m[1];
            if ($h >= 0 && $h <= 23) {
                return sprintf('%02d:00', $h);
            }
        }
        if (preg_match('/\b(?:a\s+las|las)\s+(\d{1,2})\b/iu', $t, $m)) {
            $h = (int) $m[1];
            if ($h >= 1 && $h <= 12 && preg_match('/\b(pm|p\.?\s*m\.?|tarde|noche)\b/iu', $t)) {
                $h = $h === 12 ? 12 : $h + 12;
            }
            if ($h >= 0 && $h <= 23) {
                return sprintf('%02d:00', $h);
            }
        }
        return $default;
    }

    public static function parseAge(string $text): ?string {
        if (preg_match('/\b(23|25)\b/', $text, $m)) {
            return $m[1];
        }
        return null;
    }

    public static function parseChoiceNumber(string $text, int $max): ?int {
        if (preg_match('/^\s*(\d+)\s*$/', trim($text), $m)) {
            $n = (int) $m[1];
            return ($n >= 1 && $n <= $max) ? $n : null;
        }
        return null;
    }

    /** @param array<int, array{code: string, name: string, shortName?: string}> $branches */
    public static function matchBranch(string $text, array $branches): ?string {
        $t = mb_strtolower(trim($text));
        $t = preg_replace('/^(de|en|por|desde|la|el)\s+/u', '', $t);
        if (preg_match('/\b([A-Z]{2,5})\b/i', $text, $m)) {
            $code = strtoupper($m[1]);
            foreach ($branches as $b) {
                if (($b['code'] ?? '') === $code) {
                    return $code;
                }
            }
        }
        foreach ($branches as $b) {
            $name = mb_strtolower($b['name'] ?? '');
            $short = mb_strtolower($b['shortName'] ?? '');
            if ($name !== '' && (strpos($t, $name) !== false || strpos($name, $t) !== false)) {
                return $b['code'] ?? null;
            }
            if ($short !== '' && (strpos($t, $short) !== false || strpos($short, $t) !== false)) {
                return $b['code'] ?? null;
            }
            foreach (preg_split('/\s+/', $short) as $word) {
                if (mb_strlen($word) >= 4 && strpos($t, $word) !== false) {
                    return $b['code'] ?? null;
                }
            }
        }
        return null;
    }

    /** @param string[] $options */
    public static function matchOption(string $text, array $options): ?string {
        $t = mb_strtolower(trim($text));
        foreach ($options as $opt) {
            $o = mb_strtolower($opt);
            if ($t === $o || strpos($t, $o) !== false || strpos($o, $t) !== false) {
                return $opt;
            }
        }
        if (preg_match('/^\s*(\d+)\s*$/', $t, $m)) {
            $i = (int) $m[1] - 1;
            if (isset($options[$i])) {
                return $options[$i];
            }
        }
        return null;
    }

    public static function splitFullName(string $text): array {
        $parts = preg_split('/\s+/', trim($text)) ?: [];
        if (count($parts) <= 1) {
            return ['first' => trim($text), 'last' => ''];
        }
        $first = array_shift($parts);
        return ['first' => $first, 'last' => implode(' ', $parts)];
    }

    private static function validDate(int $y, int $m, int $d): bool {
        return checkdate($m, $d, $y);
    }
}
