<?php
/**
 * Classe Admin do LUMIQ WhatsApp
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lumiq_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_lumiq_validate_key', array($this, 'validate_key_ajax'));
        add_action('wp_ajax_lumiq_load_teams', array($this, 'load_teams_ajax'));
    }
    
    /**
     * Adicionar menu no admin
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
     * Validar API Key via AJAX
     */
    public function validate_key_ajax() {
        check_ajax_referer('lumiq_admin_nonce', 'nonce');
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($api_key)) {
            wp_send_json_error('API Key é obrigatória');
            return;
        }
        
        $response = wp_remote_get(
            'https://lumiq-smoky.vercel.app/api/wordpress/validate-key?key=' . $api_key,
            array(
                'timeout' => 15,
                'headers' => array('Content-Type' => 'application/json')
            )
        );
        
        if (is_wp_error($response)) {
            wp_send_json_error('Erro ao conectar: ' . $response->get_error_message());
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['valid']) || !$data['valid']) {
            wp_send_json_error('API Key inválida');
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'API Key válida!',
            'teams' => $data['teams']
        ));
    }
    
    /**
     * Carregar equipes via AJAX
     */
    public function load_teams_ajax() {
        check_ajax_referer('lumiq_admin_nonce', 'nonce');
        
        $api_key = get_option('lumiq_api_key');
        
        if (empty($api_key)) {
            wp_send_json_error('Configure a API Key primeiro');
            return;
        }
        
        $response = wp_remote_get(
            'https://lumiq-smoky.vercel.app/api/wordpress/validate-key?key=' . $api_key,
            array(
                'timeout' => 15,
                'headers' => array('Content-Type' => 'application/json')
            )
        );
        
        if (is_wp_error($response)) {
            wp_send_json_error('Erro ao conectar: ' . $response->get_error_message());
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['valid']) || !$data['valid']) {
            wp_send_json_error('API Key inválida');
            return;
        }
        
        if (empty($data['teams'])) {
            wp_send_json_error('Nenhuma equipe encontrada. Crie uma equipe no dashboard LUMIQ primeiro.');
            return;
        }
        
        wp_send_json_success(array('teams' => $data['teams']));
    }
    
    /**
     * Página de configurações
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Salvar configurações
        if (isset($_POST['lumiq_save_settings'])) {
            check_admin_referer('lumiq_settings_nonce');
            
            update_option('lumiq_api_key', sanitize_text_field($_POST['lumiq_api_key']));
            update_option('lumiq_team_id', sanitize_text_field($_POST['lumiq_team_id']));
            update_option('lumiq_capture_type', sanitize_text_field($_POST['lumiq_capture_type']));
            update_option('lumiq_button_position', sanitize_text_field($_POST['lumiq_button_position']));
            update_option('lumiq_button_color', sanitize_text_field($_POST['lumiq_button_color']));
            update_option('lumiq_button_text', sanitize_text_field($_POST['lumiq_button_text']));
            update_option('lumiq_button_size', sanitize_text_field($_POST['lumiq_button_size']));
            update_option('lumiq_enabled', isset($_POST['lumiq_enabled']) ? '1' : '0');
            
            echo '<div class="notice notice-success"><p>Configurações salvas com sucesso!</p></div>';
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
                    <!-- 1. API Key -->
                    <tr>
                        <th scope="row"><label for="lumiq_api_key">API Key *</label></th>
                        <td>
                            <input type="text" 
                                   id="lumiq_api_key" 
                                   name="lumiq_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" 
                                   class="regular-text" 
                                   placeholder="lumiq_live_XXXXXXXXXXXX">
                            <button type="button" id="validate-key-btn" class="button">Validar Chave</button>
                            <p class="description">
                                Cole sua chave API gerada no painel LUMIQ. 
                                <a href="https://lumiq-smoky.vercel.app/dashboard/wordpress" target="_blank">Gerar chave →</a>
                            </p>
                            <div id="key-validation-result"></div>
                        </td>
                    </tr>
                    
                    <!-- 2. Equipe -->
                    <tr>
                        <th scope="row"><label for="lumiq_team_id">Equipe *</label></th>
                        <td>
                            <select name="lumiq_team_id" id="lumiq_team_id" class="regular-text">
                                <option value="">Selecione uma equipe...</option>
                            </select>
                            <button type="button" id="load-teams-btn" class="button">Carregar Equipes</button>
                            <p class="description">Os leads capturados serão distribuídos para vendedores desta equipe</p>
                        </td>
                    </tr>
                    
                    <!-- 3. Tipo de Captura -->
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
                    
                    <!-- 4. Posição do Botão -->
                    <tr>
                        <th scope="row">Posição do Botão</th>
                        <td>
                            <select name="lumiq_button_position" class="regular-text">
                                <option value="left" <?php selected($button_position, 'left'); ?>>Canto Inferior Esquerdo</option>
                                <option value="right" <?php selected($button_position, 'right'); ?>>Canto Inferior Direito</option>
                            </select>
                        </td>
                    </tr>
                    
                    <!-- 5. Cor do Botão -->
                    <tr>
                        <th scope="row">Cor do Botão</th>
                        <td>
                            <input type="color" name="lumiq_button_color" value="<?php echo esc_attr($button_color); ?>">
                            <p class="description">Padrão: #25D366 (verde WhatsApp)</p>
                        </td>
                    </tr>
                    
                    <!-- 6. Tamanho do Botão -->
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
                    
                    <!-- 7. Ativar Plugin -->
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
            
            <!-- Preview -->
            <div class="lumiq-preview">
                <h2>Preview do Botão</h2>
                <div class="preview-container">
                    <div id="lumiq-preview-button" class="lumiq-button-preview" style="background-color: <?php echo esc_attr($button_color); ?>;">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="white">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .preview-container {
                position: relative;
                height: 200px;
                background: #f0f0f0;
                border: 1px solid #ddd;
                border-radius: 8px;
            }
            .lumiq-button-preview {
                position: absolute;
                bottom: 20px;
                <?php echo $button_position === 'left' ? 'left: 20px;' : 'right: 20px;'; ?>
                width: <?php echo $button_size === 'small' ? '50px' : ($button_size === 'large' ? '70px' : '60px'); ?>;
                height: <?php echo $button_size === 'small' ? '50px' : ($button_size === 'large' ? '70px' : '60px'); ?>;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            #key-validation-result {
                margin-top: 10px;
                padding: 10px;
                border-radius: 4px;
            }
            #key-validation-result.success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            #key-validation-result.error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
        </style>
        <?php
    }
}
