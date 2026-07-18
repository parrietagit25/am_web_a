<?php
/**
 * Validación y normalización de fecha de nacimiento del conductor RAC.
 * No aplica reglas de edad mínima/máxima ni recalcula tarifas.
 */
declare(strict_types=1);

class RacBirthDateService
{
    public const INTERNAL_FORMAT = 'Y-m-d';

    private const TIMEZONE = 'America/Panama';
    private const MIN_YEAR = 1900;
    private const MAX_LENGTH = 32;

    /**
     * @param mixed $value
     */
    public static function normalize($value): ?string
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '' || strlen($raw) > self::MAX_LENGTH) {
            return null;
        }

        if ($raw !== strip_tags($raw) || preg_match('/[\x00-\x1F\x7F<>"\']/', $raw)) {
            return null;
        }

        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
            return null;
        }

        $year = (int) $m[1];
        $month = (int) $m[2];
        $day = (int) $m[3];

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        if ($year < self::MIN_YEAR) {
            return null;
        }

        $normalized = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $tz = new DateTimeZone(self::TIMEZONE);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $normalized, $tz);
        if ($date === false || $date->format(self::INTERNAL_FORMAT) !== $normalized) {
            return null;
        }

        $today = new DateTimeImmutable('today', $tz);
        if ($date >= $today) {
            return null;
        }

        return $normalized;
    }

    /**
     * @param mixed $value
     */
    public static function validationError($value): ?string
    {
        if (self::normalize($value) !== null) {
            return null;
        }

        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return 'La fecha de nacimiento es obligatoria.';
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return 'La fecha de nacimiento es obligatoria.';
        }

        return 'Ingrese una fecha de nacimiento válida.';
    }

    /**
     * @param mixed $value
     */
    public static function requireValid($value): string
    {
        $normalized = self::normalize($value);
        if ($normalized === null) {
            throw new InvalidArgumentException(self::validationError($value) ?? 'Fecha de nacimiento inválida.');
        }

        return $normalized;
    }
}
