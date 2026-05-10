<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitWordpress;

/**
 * Plugin bootstrap. Wires WordPress hooks for admin settings, the
 * scheduled queue, and (when WooCommerce is present) the order-to-
 * MappingData mapper.
 *
 * Scaffolding only at this stage; the actual hook registrations and
 * the WooCommerce mapper are intentionally left out so the package
 * surface can settle before content is driven into it.
 */
final class Bootstrap
{
    public static function init(): void
    {
        if (!self::canBoot()) {
            return;
        }

        add_action('init', [self::class, 'onInit']);

        if (is_admin()) {
            add_action('admin_init', [self::class, 'onAdminInit']);
        }
    }

    public static function onInit(): void
    {
        load_plugin_textdomain(
            'xrechnung-kit',
            false,
            dirname(plugin_basename(XRECHNUNG_KIT_PLUGIN_FILE)) . '/languages',
        );
    }

    public static function onAdminInit(): void
    {
    }

    private static function canBoot(): bool
    {
        return version_compare(PHP_VERSION, '8.1.0', '>=')
            && class_exists(\XrechnungKit\XRechnungValidator::class);
    }
}
