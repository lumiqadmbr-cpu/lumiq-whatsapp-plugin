<?php
/**
 * Executado quando o plugin é desinstalado
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Deletar opções
delete_option('lumiq_api_key');
delete_option('lumiq_team_id');
delete_option('lumiq_capture_type');
delete_option('lumiq_button_position');
delete_option('lumiq_button_color');
delete_option('lumiq_button_text');
delete_option('lumiq_button_size');
delete_option('lumiq_enabled');
delete_option('lumiq_version');

// Limpar cache
wp_cache_flush();
