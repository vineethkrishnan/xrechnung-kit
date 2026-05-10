<?php

/**
 * Plugin Name:       xrechnung-kit
 * Plugin URI:        https://xrechnung-kit.vineethnk.in/frameworks/wordpress
 * Description:       Generate KoSIT-strict valid XRechnung 3.0 / EN 16931 invoices from WordPress / WooCommerce orders. Independent open source library; not affiliated with KoSIT or any German government agency.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Vineeth N K
 * Author URI:        https://vineethnk.in/
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       xrechnung-kit
 * Domain Path:       /languages
 *
 * @package           XrechnungKitWordpress
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('XRECHNUNG_KIT_PLUGIN_VERSION', '0.1.0');
define('XRECHNUNG_KIT_PLUGIN_FILE', __FILE__);
define('XRECHNUNG_KIT_PLUGIN_DIR', plugin_dir_path(__FILE__));

$autoload = XRECHNUNG_KIT_PLUGIN_DIR . 'vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
}

if (class_exists(\Vineethkrishnan\XrechnungKitWordpress\Bootstrap::class)) {
    \Vineethkrishnan\XrechnungKitWordpress\Bootstrap::init();
}
