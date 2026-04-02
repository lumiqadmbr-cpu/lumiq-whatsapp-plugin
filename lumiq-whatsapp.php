<?php
/**
 * Plugin Name: LUMIQ WhatsApp
 * Plugin URI: https://lumiq.io
 * Description: Distribua leads automaticamente para sua equipe de vendas via WhatsApp
 * Version: 1.0.1
 * Author: LUMIQ
 * Author URI: https://lumiq.io
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lumiq-whatsapp
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Constantes do plugin
define('LUMIQ_VERSION', '1.0.1');
define('LUMIQ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LUMIQ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LUMIQ_PLUGIN_BASENAME', plugin_basename(__FILE__));

// URL da API LUMIQ - CORRIGIDA!
if (!defined('LUMIQ_API_URL')) {
    define('LUMIQ_API_URL', 'https://lumiq-smoky.vercel.app/api/wordpress');
}

// Carregar classes
require_once LUMIQ_PLUGIN_DIR . 'includes/class-lumiq-api.php';
require_once LUMIQ_PLUGIN_DIR . 'includes/class-lumiq-admin.php';
require_once LUMIQ_PLUGIN_DIR . 'includes/class-lumiq-widget.php';

/**
 * Classe principal do plugin
 */
class Lumiq_WhatsApp {
    
    private static $instance = null;
    public $api;
    public $admin;
    public $widget;
    
    /**
     * Singleton pattern
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Inicializar plugin
     */
    private function init() {
        // Inicializar componentes
        $this->api = new Lumiq_API();
        $this->admin = new Lumiq_Admin();
        $this->widget = new Lumiq_Widget();
        
        // Hooks de ativação/desativação
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Carregar scripts e estilos
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        
        // Links de ação na página de plugins
        add_filter('plugin_action_links_' . LUMIQ_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
    }
    
    /**
     * Ativação do plugin
     */
    public function activate() {
        // Criar opções padrão
        add_option('lumiq_api_key', '');
        add_option('lumiq_team_id', '');
        add_option('lumiq_capture_type', 'form');
        add_option('lumiq_button_position', 'right');
        add_option('lumiq_button_color', '#25D366');
        add_option('lumiq_button_text', 'Fale Conosco');
        add_option('lumiq_button_size', 'medium');
        add_option('lumiq_enabled', '0');
        add_option('lumiq_version', LUMIQ_VERSION);
        
        // Limpar cache
        flush_rewrite_rules();
    }
    
    /**
     * Desativação do plugin
     */
    public function deactivate() {
        // Limpar cache
        flush_rewrite_rules();
    }
    
    /**
     * Carregar scripts do admin
     */
    public function admin_scripts($hook) {
        // Carregar apenas na página do plugin
        if ('toplevel_page_lumiq-whatsapp' !== $hook) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'lumiq-admin-css',
            LUMIQ_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            LUMIQ_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'lumiq-admin-js',
            LUMIQ_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            LUMIQ_VERSION,
            true
        );
        
        // Passar dados para JavaScript
        wp_localize_script('lumiq-admin-js', 'lumiqAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lumiq_admin_nonce'),
            'apiUrl' => LUMIQ_API_URL,
            'version' => LUMIQ_VERSION
        ));
    }
    
    /**
     * Carregar scripts do frontend
     */
    public function frontend_scripts() {
        // Verificar se plugin está habilitado
        if (!get_option('lumiq_enabled')) {
            return;
        }
        
        // Verificar se tem API Key configurada
        if (empty(get_option('lumiq_api_key'))) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'lumiq-frontend-css',
            LUMIQ_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            LUMIQ_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'lumiq-frontend-js',
            LUMIQ_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            LUMIQ_VERSION,
            true
        );
        
        // Passar configurações para JavaScript
        wp_localize_script('lumiq-frontend-js', 'lumiqConfig', array(
            'apiUrl' => LUMIQ_API_URL,
            'apiKey' => get_option('lumiq_api_key'),
            'teamId' => get_option('lumiq_team_id'),
            'captureType' => get_option('lumiq_capture_type'),
            'buttonPosition' => get_option('lumiq_button_position'),
            'buttonColor' => get_option('lumiq_button_color'),
            'buttonText' => get_option('lumiq_button_text'),
            'buttonSize' => get_option('lumiq_button_size', 'medium'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lumiq_frontend_nonce')
        ));
    }
    
    /**
     * Adicionar links de ação na página de plugins
     */
    public function plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=lumiq-whatsapp') . '">Configurações</a>';
        $docs_link = '<a href="https://docs.lumiq.io" target="_blank">Documentação</a>';
        
        array_unshift($links, $settings_link);
        array_push($links, $docs_link);
        
        return $links;
    }
}

/**
 * Inicializar plugin
 */
function lumiq_whatsapp_init() {
    return Lumiq_WhatsApp::get_instance();
}

// Iniciar após WordPress carregar
add_action('plugins_loaded', 'lumiq_whatsapp_init');

/**
 * Verificar se função existe (compatibilidade)
 */
if (!function_exists('lumiq_whatsapp')) {
    function lumiq_whatsapp() {
        return Lumiq_WhatsApp::get_instance();
    }
}
