<?php

declare(strict_types=1);

namespace XrechnungKit\KositBundle;

/**
 * Locates the bundled KoSIT validator JAR and scenarios on disk.
 *
 * The bundle ships the validator JAR and the pinned scenarios under
 * data/. The CLI (vineethkrishnan/xrechnung-kit-cli) calls into here to
 * resolve absolute paths it then hands to java -jar.
 *
 * The bundle's data/ directory is intentionally empty in this repository;
 * the JAR (~50 MB) and scenarios (~200 MB) are fetched once at install
 * time per the bundle's README. A future composer install hook may
 * automate the fetch.
 */
final class Bundle
{
    /**
     * Absolute path to the bundle root (the directory holding composer.json).
     */
    public static function path(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * Absolute path to the validator JAR. Throws RuntimeException when the
     * JAR has not yet been fetched into data/; the message points at the
     * bundle README's setup instructions.
     */
    public static function validatorJarPath(): string
    {
        $path = self::path() . '/data/validator.jar';
        if (!is_file($path)) {
            throw new \RuntimeException(
                'KoSIT validator JAR not found at ' . $path . '. '
                . 'Fetch it once per the README: '
                . self::path() . '/README.md'
            );
        }
        return $path;
    }

    /**
     * Absolute path to the pinned scenarios manifest.
     */
    public static function scenariosPath(): string
    {
        $path = self::path() . '/data/scenarios/scenarios.xml';
        if (!is_file($path)) {
            throw new \RuntimeException(
                'KoSIT scenarios manifest not found at ' . $path . '. '
                . 'Fetch it once per the README: '
                . self::path() . '/README.md'
            );
        }
        return $path;
    }
}
