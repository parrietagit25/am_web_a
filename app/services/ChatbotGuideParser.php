<?php
/**
 * Parsers para respuestas del usuario en flujos guiados del chatbot.
 */

class ChatbotGuideParser {
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
        $t = trim($text);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $t, $m)) {
            return self::validDate((int) $m[1], (int) $m[2], (int) $m[3]) ? $t : null;
        }
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $t, $m)) {
            $d = (int) $m[1];
            $mo = (int) $m[2];
            $y = (int) $m[3];
            return self::validDate($y, $mo, $d) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : null;
        }
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2})$/', $t, $m)) {
            $y = 2000 + (int) $m[3];
            $mo = (int) $m[2];
            $d = (int) $m[1];
            return self::validDate($y, $mo, $d) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : null;
        }
        return null;
    }

    public static function parseTime(string $text, string $default = '10:00'): string {
        $t = trim($text);
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $t, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            if ($h >= 0 && $h <= 23 && $min >= 0 && $min <= 59) {
                return sprintf('%02d:%02d', $h, $min);
            }
        }
        if (preg_match('/^(\d{1,2})\s*h(?:oras?)?/i', $t, $m)) {
            $h = (int) $m[1];
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
            if ($short !== '' && strpos($t, $short) !== false) {
                return $b['code'] ?? null;
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
