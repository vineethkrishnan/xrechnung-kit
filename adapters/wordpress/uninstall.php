<?php

/**
 * Fired when the plugin is deleted via the WordPress admin.
 *
 * Uninstall removes only plugin-owned options. Generated XRechnung
 * artefacts in wp-content/uploads/ are intentionally preserved -
 * they are records.
 */

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('xrechnung_kit_settings');
delete_site_option('xrechnung_kit_settings');
