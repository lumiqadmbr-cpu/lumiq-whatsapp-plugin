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
            80
        );
        
        add_submenu_page(
            'lumiq-whatsapp',
            'Configurações',
            'Configurações',
            'manage_options',
            'lumiq-whatsapp'
        );
        
        add_submenu_page(
            'lumiq-whatsapp',
            'Documentação',
            'Documentação',
            'manage_options',
            'lumiq-docs',
            array($this, 'docs_page')
        );
    }
    
    /**
     * Registrar configurações
     */
    public function register_settings() {
        register_setting('lumiq_settings', 'lumiq_api_key', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('lumiq_settings', 'lumiq_team_id', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('lumiq_settings', 'lumiq_capture_type', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'form'
        ));
        register_setting('lumiq_settings', 'lumiq_button_position', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'right'
        ));
        register_setting('lumiq_settings', 'lumiq_button_color', array(
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#25D366'
        ));
        register_setting('lumiq_settings', 'lumiq_button_text', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Fale Conosco'
        ));
        register_setting('lumiq_settings', 'lumiq_button_size', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'medium'
        ));
        register_setting('lumiq_settings', 'lumiq_enabled', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => '0'
        ));
    }
    
    /**
     * Sanitizar checkbox
     */
    public function sanitize_checkbox($value) {
        return $value ? '1' : '0';
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
            // Salvar API Key
            update_option('lumiq_api_key', $api_key);
            
            wp_send_json_success(array(
                'message' => 'Chave validada com sucesso!',
                'user_name' => $result['user_name'] ?? '',
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
        if (!get_option('lumiq_api_key')) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong>LUMIQ WhatsApp:</strong> 
                    Configure sua API Key para começar a capturar leads! 
                    <a href="<?php echo admin_url('admin.php?page=lumiq-whatsapp'); ?>">Configurar agora</a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Página de configurações
     */
    public function settings_page() {
        $api_key = get_option('lumiq_api_key');
        $team_id = get_option('lumiq_team_id');
        $capture_type = get_option('lumiq_capture_type', 'form');
        $button_position = get_option('lumiq_button_position', 'right');
        $button_color = get_option('lumiq_button_color', '#25D366');
        $button_text = get_option('lumiq_button_text', 'Fale Conosco');
        $button_size = get_option('lumiq_button_size', 'medium');
        $enabled = get_option('lumiq_enabled', '0');
        
        ?>
        <div class="wrap lumiq-admin-wrap">
            <div class="lumiq-header">
                <h1>
                    <span class="dashicons dashicons-whatsapp" style="color: #25D366;"></span>
                    LUMIQ WhatsApp - Configurações
                </h1>
                <p class="description">Configure o plugin para começar a capturar e distribuir leads via WhatsApp</p>
            </div>
            
            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Configurações salvas com sucesso!</p>
                </div>
            <?php endif; ?>
            
            <div class="lumiq-content">
                <div class="lumiq-main">
                    <form method="post" action="options.php" id="lumiq-settings-form">
                        <?php settings_fields('lumiq_settings'); ?>
                        
                        <!-- Conexão -->
                        <div class="lumiq-card">
                            <h2>1. Conexão com LUMIQ</h2>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="lumiq_api_key">API Key *</label>
                                    </th>
                                    <td>
                                        <div class="lumiq-input-group">
                                            <input type="password" 
                                                   id="lumiq_api_key" 
                                                   name="lumiq_api_key" 
                                                   value="<?php echo esc_attr($api_key); ?>" 
                                                   class="regular-text lumiq-api-key"
                                                   placeholder="lumiq_live_XXXXXXXXXXXX">
                                            <button type="button" id="lumiq-validate-key" class="button">
                                                Validar Chave
                                            </button>
                                            <button type="button" id="lumiq-toggle-key" class="button">
                                                <span class="dashicons dashicons-visibility"></span>
                                            </button>
                                        </div>
                                        <p class="description">
                                            Cole sua chave API gerada no painel LUMIQ. 
                                            <a href="https://lumiq.io/dashboard/wordpress" target="_blank">Gerar chave →</a>
                                        </p>
                                        <div id="lumiq-key-status"></div>
                                    </td>
                                </tr>
                                
                                <tr id="lumiq-team-row" <?php echo empty($api_key) ? 'style="display:none;"' : ''; ?>>
                                    <th scope="row">
                                        <label for="lumiq_team_id">Equipe</label>
                                    </th>
                                    <td>
                                        <select id="lumiq_team_id" name="lumiq_team_id" class="regular-text">
                                            <option value="">Carregando equipes...</option>
                                        </select>
                                        <p class="description">Escolha qual equipe receberá os leads deste site</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Tipo de Captura -->
                        <div class="lumiq-card">
                            <h2>2. Tipo de Captura</h2>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Como capturar leads?</th>
                                    <td>
                                        <fieldset>
                                            <label>
                                                <input type="radio" 
                                                       name="lumiq_capture_type" 
                                                       value="form" 
                                                       <?php checked($capture_type, 'form'); ?>>
                                                <strong>Formulário</strong> - Lead preenche nome, telefone e mensagem
                                            </label><br><br>
                                            <label>
                                                <input type="radio" 
                                                       name="lumiq_capture_type" 
                                                       value="direct" 
                                                       <?php checked($capture_type, 'direct'); ?>>
                                                <strong>Direto</strong> - Vai direto para o WhatsApp sem formulário
                                            </label>
                                        </fieldset>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Personalização -->
                        <div class="lumiq-card">
                            <h2>3. Personalização do Botão</h2>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="lumiq_button_position">Posição</label>
                                    </th>
                                    <td>
                                        <select id="lumiq_button_position" name="lumiq_button_position">
                                            <option value="right" <?php selected($button_position, 'right'); ?>>
                                                Canto Inferior Direito
                                            </option>
                                            <option value="left" <?php selected($button_position, 'left'); ?>>
                                                Canto Inferior Esquerdo
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="lumiq_button_color">Cor</label>
                                    </th>
                                    <td>
                                        <input type="color" 
                                               id="lumiq_button_color" 
                                               name="lumiq_button_color" 
                                               value="<?php echo esc_attr($button_color); ?>">
                                        <p class="description">Padrão: #25D366 (verde WhatsApp)</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="lumiq_button_text">Texto</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="lumiq_button_text" 
                                               name="lumiq_button_text" 
                                               value="<?php echo esc_attr($button_text); ?>" 
                                               class="regular-text"
                                               maxlength="30">
                                        <p class="description">Máximo 30 caracteres</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="lumiq_button_size">Tamanho</label>
                                    </th>
                                    <td>
                                        <select id="lumiq_button_size" name="lumiq_button_size">
                                            <option value="small" <?php selected($button_size, 'small'); ?>>Pequeno</option>
                                            <option value="medium" <?php selected($button_size, 'medium'); ?>>Médio</option>
                                            <option value="large" <?php selected($button_size, 'large'); ?>>Grande</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Ativação -->
                        <div class="lumiq-card">
                            <h2>4. Ativar Plugin</h2>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Status</th>
                                    <td>
                                        <label class="lumiq-toggle">
                                            <input type="checkbox" 
                                                   name="lumiq_enabled" 
                                                   value="1" 
                                                   <?php checked($enabled, '1'); ?>>
                                            <span class="lumiq-toggle-slider"></span>
                                            <span class="lumiq-toggle-label">
                                                <strong>Habilitar botão WhatsApp no site</strong>
                                            </span>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <?php submit_button('Salvar Configurações', 'primary large'); ?>
                    </form>
                </div>
                
                <!-- Sidebar -->
                <div class="lumiq-sidebar">
                    <!-- Preview -->
                    <div class="lumiq-card">
                        <h3>Preview do Botão</h3>
                        <div id="lumiq-preview" class="lumiq-preview">
                            <div class="lumiq-preview-phone">
                                <div class="lumiq-preview-screen">
                                    <div id="lumiq-preview-button"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ajuda -->
                    <div class="lumiq-card">
                        <h3>Precisa de Ajuda?</h3>
                        <ul class="lumiq-help-links">
                            <li><a href="https://docs.lumiq.io" target="_blank">📚 Documentação</a></li>
                            <li><a href="https://lumiq.io/suporte" target="_blank">💬 Suporte</a></li>
                            <li><a href="https://lumiq.io/dashboard/wordpress" target="_blank">🔑 Gerar API Key</a></li>
                        </ul>
                    </div>
                    
                    <!-- Stats -->
                    <div class="lumiq-card">
                        <h3>Estatísticas</h3>
                        <div class="lumiq-stats">
                            <div class="lumiq-stat">
                                <div class="lumiq-stat-value">-</div>
                                <div class="lumiq-stat-label">Cliques Hoje</div>
                            </div>
                            <div class="lumiq-stat">
                                <div class="lumiq-stat-value">-</div>
                                <div class="lumiq-stat-label">Leads Hoje</div>
                            </div>
                        </div>
                        <p class="description">
                            <a href="https://lumiq.io/dashboard" target="_blank">Ver relatório completo →</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Página de documentação
     */
    public function docs_page() {
        ?>
        <div class="wrap">
            <h1>Documentação LUMIQ WhatsApp</h1>
            <p>Acesse a documentação completa em: 
                <a href="https://docs.lumiq.io" target="_blank">https://docs.lumiq.io</a>
            </p>
        </div>
        <?php
    }
}
