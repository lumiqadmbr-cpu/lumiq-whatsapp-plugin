<?php
/**
 * Painel de administração do plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lumiq_Admin {
    
    private $api;
    
    public function __construct() {
        $this->api = new Lumiq_API();
        
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_lumiq_validate_key', array($this, 'ajax_validate_key'));
        add_action('wp_ajax_lumiq_get_teams', array($this, 'ajax_get_teams'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Adicionar menu no WordPress
     */
    public function add_menu() {
        add_menu_page(
            'LUMIQ WhatsApp',
            'LUMIQ WhatsApp',
            'manage_options',
            'lumiq-whatsapp',
            array($this, 'settings_page'),
            'dashicons-whatsapp',
            30
        );
    }
    
    /**
     * Registrar configurações
     */
    public function register_settings() {
        register_setting('lumiq_settings', 'lumiq_api_key');
        register_setting('lumiq_settings', 'lumiq_team_id');
        register_setting('lumiq_settings', 'lumiq_capture_type');
        register_setting('lumiq_settings', 'lumiq_button_position');
        register_setting('lumiq_settings', 'lumiq_button_color');
        register_setting('lumiq_settings', 'lumiq_button_text');
        register_setting('lumiq_settings', 'lumiq_button_size');
        register_setting('lumiq_settings', 'lumiq_enabled');
    }
    
    /**
     * AJAX: Validar API Key
     */
    public function ajax_validate_key() {
        check_ajax_referer('lumiq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada'));
        }
        
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API Key não pode estar vazia'));
        }
        
        $result = $this->api->validate_key($api_key);
        
        if ($result && isset($result['valid']) && $result['valid']) {
            wp_send_json_success(array(
                'message' => 'Chave validada com sucesso!',
                'teams' => $result['teams'] ?? array()
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message'] ?? 'Chave inválida ou expirada'
            ));
        }
    }
    
    /**
     * AJAX: Buscar equipes
     */
    public function ajax_get_teams() {
        check_ajax_referer('lumiq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada'));
        }
        
        $api_key = get_option('lumiq_api_key');
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'Configure a API Key primeiro'));
        }
        
        $teams = $this->api->get_teams($api_key);
        
        if ($teams) {
            wp_send_json_success(array('teams' => $teams));
        } else {
            wp_send_json_error(array('message' => 'Erro ao buscar equipes'));
        }
    }
    
    /**
     * Avisos no admin
     */
    public function admin_notices() {
        $current_screen = get_current_screen();
        if ($current_screen->id !== 'toplevel_page_lumiq-whatsapp') {
            return;
        }
        
        if (!get_option('lumiq_api_key')) {
            ?>
            <div class="notice notice-warning">
                <p><strong>LUMIQ WhatsApp:</strong> Configure sua API Key para começar a capturar leads!</p>
            </div>
            <?php
        }
    }
    
    /**
     * Página de configurações
     */
    public function settings_page() {
        if (isset($_POST['lumiq_save_settings'])) {
            check_admin_referer('lumiq_settings_nonce');
            
            update_option('lumiq_api_key', sanitize_text_field($_POST['lumiq_api_key'] ?? ''));
            update_option('lumiq_team_id', sanitize_text_field($_POST['lumiq_team_id'] ?? ''));
            update_option('lumiq_capture_type', sanitize_text_field($_POST['lumiq_capture_type'] ?? 'form'));
            update_option('lumiq_button_position', sanitize_text_field($_POST['lumiq_button_position'] ?? 'right'));
            update_option('lumiq_button_color', sanitize_hex_color($_POST['lumiq_button_color'] ?? '#25D366'));
            update_option('lumiq_button_text', sanitize_text_field($_POST['lumiq_button_text'] ?? 'Fale Conosco'));
            update_option('lumiq_button_size', sanitize_text_field($_POST['lumiq_button_size'] ?? 'medium'));
            update_option('lumiq_enabled', isset($_POST['lumiq_enabled']) ? '1' : '0');
            
            echo '<div class="notice notice-success is-dismissible"><p>Configurações salvas com sucesso!</p></div>';
        }
        
        $api_key = get_option('lumiq_api_key', '');
        $team_id = get_option('lumiq_team_id', '');
        $capture_type = get_option('lumiq_capture_type', 'form');
        $button_position = get_option('lumiq_button_position', 'right');
        $button_color = get_option('lumiq_button_color', '#25D366');
        $button_text = get_option('lumiq_button_text', 'Fale Conosco');
        $button_size = get_option('lumiq_button_size', 'medium');
        $enabled = get_option('lumiq_enabled', '0');
        
        ?>
        <div class="wrap">
            <h1>LUMIQ WhatsApp - Configurações</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('lumiq_settings_nonce'); ?>
                
                <table class="form-table">
                    <!-- API Key -->
                    <tr>
                        <th scope="row"><label for="lumiq_api_key">API Key *</label></th>
                        <td>
                            <input type="password" 
                                   id="lumiq_api_key" 
                                   name="lumiq_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" 
                                   class="regular-text">
                            <button type="button" id="lumiq-validate-key" class="button">Validar Chave</button>
                            <button type="button" id="lumiq-toggle-key" class="button">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <p class="description">
                                Cole sua chave API gerada no painel LUMIQ. 
                                <a href="https://lumiq-smoky.vercel.app/dashboard/wordpress" target="_blank">Gerar chave →</a>
                            </p>
                            <div id="lumiq-key-status"></div>
                        </td>
                    </tr>
                    
                    <!-- Equipe -->
                    <tr id="lumiq-team-row" <?php echo empty($api_key) ? 'style="display:none;"' : ''; ?>>
                        <th scope="row"><label for="lumiq_team_id">Equipe *</label></th>
                        <td>
                            <select id="lumiq_team_id" name="lumiq_team_id" class="regular-text">
                                <option value="">Selecione uma equipe...</option>
                                <?php if ($team_id): ?>
                                <option value="<?php echo esc_attr($team_id); ?>" selected>Equipe Selecionada</option>
                                <?php endif; ?>
                            </select>
                            <p class="description">Os leads capturados serão distribuídos para vendedores desta equipe</p>
                        </td>
                    </tr>
                    
                    <!-- Tipo de Captura -->
                    <tr>
                        <th scope="row">Tipo de Captura</th>
                        <td>
                            <label>
                                <input type="radio" name="lumiq_capture_type" value="form" <?php checked($capture_type, 'form'); ?>>
                                Formulário - Lead preenche nome, telefone e mensagem
                            </label><br>
                            <label>
                                <input type="radio" name="lumiq_capture_type" value="direct" <?php checked($capture_type, 'direct'); ?>>
                                Direto - Vai direto para o WhatsApp sem formulário
                            </label>
                        </td>
                    </tr>
                    
                    <!-- Posição -->
                    <tr>
                        <th scope="row">Posição do Botão</th>
                        <td>
                            <select name="lumiq_button_position" class="regular-text">
                                <option value="left" <?php selected($button_position, 'left'); ?>>Canto Inferior Esquerdo</option>
                                <option value="right" <?php selected($button_position, 'right'); ?>>Canto Inferior Direito</option>
                            </select>
                        </td>
                    </tr>
                    
                    <!-- Cor -->
                    <tr>
                        <th scope="row">Cor do Botão</th>
                        <td>
                            <input type="color" name="lumiq_button_color" value="<?php echo esc_attr($button_color); ?>">
                            <p class="description">Padrão: #25D366 (verde WhatsApp)</p>
                        </td>
                    </tr>
                    
                    <!-- Texto -->
                    <tr>
                        <th scope="row">Texto do Botão</th>
                        <td>
                            <input type="text" name="lumiq_button_text" value="<?php echo esc_attr($button_text); ?>" class="regular-text" maxlength="30">
                            <p class="description">Máximo 30 caracteres</p>
                        </td>
                    </tr>
                    
                    <!-- Tamanho -->
                    <tr>
                        <th scope="row">Tamanho do Botão</th>
                        <td>
                            <select name="lumiq_button_size" class="regular-text">
                                <option value="small" <?php selected($button_size, 'small'); ?>>Pequeno (50px)</option>
                                <option value="medium" <?php selected($button_size, 'medium'); ?>>Médio (60px)</option>
                                <option value="large" <?php selected($button_size, 'large'); ?>>Grande (70px)</option>
                            </select>
                        </td>
                    </tr>
                    
                    <!-- Ativar -->
                    <tr>
                        <th scope="row">Status</th>
                        <td>
                            <label>
                                <input type="checkbox" name="lumiq_enabled" value="1" <?php checked($enabled, '1'); ?>>
                                Habilitar botão WhatsApp no site
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="lumiq_save_settings" class="button button-primary">Salvar Configurações</button>
                </p>
            </form>
        </div>
        
        <style>
            #lumiq-key-status { margin-top: 10px; padding: 10px; border-radius: 4px; display: none; }
            #lumiq-key-status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; display: block; }
            #lumiq-key-status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; display: block; }
            #lumiq-key-status.loading { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; display: block; }
        </style>
        <?php
    }
}
