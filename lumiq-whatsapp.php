<?php
/**
 * Plugin Name: LUMIQ WhatsApp
 * Plugin URI: https://lumiq-smoky.vercel.app
 * Description: Distribui leads automaticamente para vendedores via WhatsApp usando LUMIQ
 * Version: 1.0.2
 * Author: LUMIQ
 * Author URI: https://lumiq-smoky.vercel.app
 * License: GPL v2 or later
 * Text Domain: lumiq-whatsapp
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constantes
define('LUMIQ_VERSION', '1.0.2');
define('LUMIQ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LUMIQ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LUMIQ_API_URL', 'https://lumiq-smoky.vercel.app/api/wordpress');

// Carregar classes
require_once LUMIQ_PLUGIN_DIR . 'includes/class-lumiq-api.php';
require_once LUMIQ_PLUGIN_DIR . 'includes/class-lumiq-admin.php';
require_once LUMIQ_PLUGIN_DIR . 'includes/class-lumiq-widget.php';

// Inicializar plugin
function lumiq_init() {
    new Lumiq_Admin();
    new Lumiq_Widget();
}
add_action('plugins_loaded', 'lumiq_init');
