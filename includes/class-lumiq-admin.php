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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_lumiq_validate_key', array($this, 'ajax_validate_key'));
        add_action('wp_ajax_lumiq_get_teams', array($this, 'ajax_get_teams'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Carregar scripts e estilos do admin
     */
    public function enqueue_admin_scripts($hook) {
        // Só carregar na página do plugin
        if ($hook != 'toplevel_page_lumiq-whatsapp') {
            return;
        }
        
        // jQuery (garantir que está carregado)
        wp_enqueue_script('jquery');
        
        // CSS do admin
        wp_enqueue_style(
            'lumiq-admin-css',
            LUMIQ_PLUGIN_URL . 'assets/admin.css',
            array(),
            LUMIQ_VERSION
        );
        
        // JS do admin
        wp_enqueue_script(
            'lumiq-admin-js',
            LUMIQ_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            time(),
            true
        );
        
        // Passar dados para o JavaScript
        wp_localize_script('lumiq-admin-js', 'lumiqAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lumiq_admin_nonce')
        ));
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
        register_setting('lumiq_settings', 'lumiq_team_name', array(
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
        register_setting('lumiq_settings', 'lumiq_button_style', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'circle'
        ));
        register_setting('lumiq_settings', 'lumiq_button_animation', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'pulse'
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
     * Avisos do admin
     */
    public function admin_notices() {
        // Verificar se tem API Key configurada
        $api_key = get_option('lumiq_api_key');
        $current_screen = get_current_screen();
        
        // Só mostrar aviso nas páginas do plugin
        if ($current_screen && strpos($current_screen->id, 'lumiq') === false) {
            return;
        }
        
        if (empty($api_key)) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong>LUMIQ WhatsApp:</strong> 
                    Configure sua API Key para começar a capturar leads.
                    <a href="https://lumiq-smoky.vercel.app/dashboard/wordpress" target="_blank">Gerar chave →</a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Página de configurações
     */
    public function settings_page() {
        // Processar formulário
        if (isset($_POST['lumiq_save_settings'])) {
            check_admin_referer('lumiq_settings_nonce');
            
            // As configurações já são salvas automaticamente pelo WordPress
            // Só precisamos mostrar a mensagem de sucesso
            echo '<div class="notice notice-success is-dismissible"><p>Configurações salvas com sucesso!</p></div>';
        }
        
        $api_key = get_option('lumiq_api_key');
        $team_id = get_option('lumiq_team_id');
        $team_name = get_option('lumiq_team_name');
        $capture_type = get_option('lumiq_capture_type', 'form');
        $button_position = get_option('lumiq_button_position', 'right');
        $button_color = get_option('lumiq_button_color', '#25D366');
        $button_text = get_option('lumiq_button_text', 'Fale Conosco');
        $button_size = get_option('lumiq_button_size', 'medium');
        $button_style = get_option('lumiq_button_style', 'circle');
        $button_animation = get_option('lumiq_button_animation', 'pulse');
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
                                            <button type="button" id="lumiq_validate_key" class="button">
                                                Validar
                                            </button>
                                            <button type="button" id="lumiq_toggle_key" class="button">
                                                <span class="dashicons dashicons-visibility"></span>
                                            </button>
                                        </div>
                                        <p class="description">
                                            Cole sua chave API gerada no painel LUMIQ. 
                                            <a href="https://lumiq-smoky.vercel.app/dashboard/wordpress" target="_blank">Gerar chave →</a>
                                        </p>
                                        <div id="lumiq_validate_status"></div>
                                    </td>
                                </tr>
                                
                                <tr id="lumiq-team-row" <?php echo empty($api_key) ? 'style="display:none;"' : ''; ?>>
                                    <th scope="row">
                                        <label for="lumiq_team_id">Equipe</label>
                                    </th>
                                    <td>
                                        <select id="lumiq_team_id" name="lumiq_team_id" class="regular-text">
                                            <option value="">Selecione uma equipe...</option>
                                            <?php if ($team_id && $team_name): ?>
                                            <option value="<?php echo esc_attr($team_id); ?>" selected><?php echo esc_html($team_name); ?></option>
                                            <?php endif; ?>
                                        </select>
                                        <input type="hidden" id="lumiq_team_name" name="lumiq_team_name" value="<?php echo esc_attr($team_name); ?>">
                                        <button type="button" id="lumiq_load_teams" class="button" style="margin-left: 10px;">
                                            Carregar Equipes
                                        </button>
                                        <span id="lumiq_teams_loading" style="display: none; margin-left: 10px;">
                                            <span class="spinner is-active" style="float: none; margin: 0;"></span>
                                        </span>
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
                                        <label>Estilo do Botão</label>
                                    </th>
                                    <td>
                                        <div class="lumiq-button-styles">
                                            <label class="lumiq-style-option">
                                                <input type="radio" name="lumiq_button_style" value="circle" <?php checked($button_style, 'circle'); ?>>
                                                <div class="lumiq-style-preview lumiq-style-circle">
                                                    <svg viewBox="0 0 32 32" width="30" height="30">
                                                        <path fill="#25D366" d="M16 0C7.163 0 0 7.163 0 16c0 2.825.737 5.477 2.024 7.777L.08 30.105l6.528-1.904A15.923 15.923 0 0016 32c8.837 0 16-7.163 16-16S24.837 0 16 0z"/>
                                                        <path fill="white" d="M22.838 18.638c-.375-.187-2.225-1.1-2.575-1.225-.35-.125-.6-.187-.85.187-.25.375-.975 1.225-1.2 1.475-.225.25-.45.275-.825.1-.375-.187-1.587-.588-3.025-1.875-1.125-1-1.875-2.225-2.1-2.6-.225-.375-.025-.575.162-.762.163-.163.375-.425.562-.638.188-.212.25-.375.375-.625s.063-.475-.037-.663c-.1-.187-.85-2.05-1.163-2.8-.312-.75-.625-.65-.85-.65-.225 0-.475-.025-.725-.025s-.675.1-.975.475-.975.95-.975 2.325.975 2.7 1.113 2.888c.137.187 1.975 3.012 4.787 4.225.663.287 1.188.462 1.588.587.675.213 1.288.187 1.775.113.538-.088 1.65-.675 1.888-1.325.237-.65.237-1.2.162-1.325-.075-.125-.275-.2-.625-.375z"/>
                                                    </svg>
                                                </div>
                                                <span>Círculo</span>
                                            </label>
                                            
                                            <label class="lumiq-style-option">
                                                <input type="radio" name="lumiq_button_style" value="rounded" <?php checked($button_style, 'rounded'); ?>>
                                                <div class="lumiq-style-preview lumiq-style-rounded">
                                                    <svg viewBox="0 0 32 32" width="30" height="30">
                                                        <rect width="32" height="32" rx="8" fill="#25D366"/>
                                                        <path fill="white" d="M22.838 18.638c-.375-.187-2.225-1.1-2.575-1.225-.35-.125-.6-.187-.85.187-.25.375-.975 1.225-1.2 1.475-.225.25-.45.275-.825.1-.375-.187-1.587-.588-3.025-1.875-1.125-1-1.875-2.225-2.1-2.6-.225-.375-.025-.575.162-.762.163-.163.375-.425.562-.638.188-.212.25-.375.375-.625s.063-.475-.037-.663c-.1-.187-.85-2.05-1.163-2.8-.312-.75-.625-.65-.85-.65-.225 0-.475-.025-.725-.025s-.675.1-.975.475-.975.95-.975 2.325.975 2.7 1.113 2.888c.137.187 1.975 3.012 4.787 4.225.663.287 1.188.462 1.588.587.675.213 1.288.187 1.775.113.538-.088 1.65-.675 1.888-1.325.237-.65.237-1.2.162-1.325-.075-.125-.275-.2-.625-.375z"/>
                                                    </svg>
                                                </div>
                                                <span>Arredondado</span>
                                            </label>
                                            
                                            <label class="lumiq-style-option">
                                                <input type="radio" name="lumiq_button_style" value="square" <?php checked($button_style, 'square'); ?>>
                                                <div class="lumiq-style-preview lumiq-style-square">
                                                    <svg viewBox="0 0 32 32" width="30" height="30">
                                                        <rect width="32" height="32" fill="#25D366"/>
                                                        <path fill="white" d="M22.838 18.638c-.375-.187-2.225-1.1-2.575-1.225-.35-.125-.6-.187-.85.187-.25.375-.975 1.225-1.2 1.475-.225.25-.45.275-.825.1-.375-.187-1.587-.588-3.025-1.875-1.125-1-1.875-2.225-2.1-2.6-.225-.375-.025-.575.162-.762.163-.163.375-.425.562-.638.188-.212.25-.375.375-.625s.063-.475-.037-.663c-.1-.187-.85-2.05-1.163-2.8-.312-.75-.625-.65-.85-.65-.225 0-.475-.025-.725-.025s-.675.1-.975.475-.975.95-.975 2.325.975 2.7 1.113 2.888c.137.187 1.975 3.012 4.787 4.225.663.287 1.188.462 1.588.587.675.213 1.288.187 1.775.113.538-.088 1.65-.675 1.888-1.325.237-.65.237-1.2.162-1.325-.075-.125-.275-.2-.625-.375z"/>
                                                    </svg>
                                                </div>
                                                <span>Quadrado</span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="lumiq_button_animation">Animação</label>
                                    </th>
                                    <td>
                                        <select id="lumiq_button_animation" name="lumiq_button_animation">
                                            <option value="none" <?php selected($button_animation, 'none'); ?>>Sem animação</option>
                                            <option value="pulse" <?php selected($button_animation, 'pulse'); ?>>Pulsar</option>
                                            <option value="shake" <?php selected($button_animation, 'shake'); ?>>Balançar</option>
                                            <option value="bounce" <?php selected($button_animation, 'bounce'); ?>>Pular</option>
                                        </select>
                                    </td>
                                </tr>
                                
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
                        
                        <p class="submit">
                            <button type="submit" name="lumiq_save_settings" class="button button-primary button-large">
                                Salvar Configurações
                            </button>
                        </p>
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
                                    <div id="lumiq-preview-button" class="lumiq-widget-button" 
                                         data-size="<?php echo esc_attr($button_size); ?>"
                                         data-style="<?php echo esc_attr($button_style); ?>"
                                         data-animation="<?php echo esc_attr($button_animation); ?>"
                                         style="--lumiq-color: <?php echo esc_attr($button_color); ?>; position: absolute; <?php echo $button_position === 'left' ? 'left' : 'right'; ?>: 20px; bottom: 20px;">
                                        <svg viewBox="0 0 32 32" class="lumiq-widget-icon">
                                            <path fill="currentColor" d="M16 0C7.163 0 0 7.163 0 16c0 2.825.737 5.477 2.024 7.777L.08 30.105l6.528-1.904A15.923 15.923 0 0016 32c8.837 0 16-7.163 16-16S24.837 0 16 0zm0 29.333c-2.547 0-4.955-.717-7.011-1.952l-.501-.296-5.189 1.515 1.54-5.024-.325-.52A13.276 13.276 0 012.667 16c0-7.363 5.971-13.333 13.333-13.333S29.333 8.637 29.333 16 23.363 29.333 16 29.333z"/>
                                            <path fill="currentColor" d="M22.838 18.638c-.375-.187-2.225-1.1-2.575-1.225-.35-.125-.6-.187-.85.187-.25.375-.975 1.225-1.2 1.475-.225.25-.45.275-.825.1-.375-.187-1.587-.588-3.025-1.875-1.125-1-1.875-2.225-2.1-2.6-.225-.375-.025-.575.162-.762.163-.163.375-.425.562-.638.188-.212.25-.375.375-.625s.063-.475-.037-.663c-.1-.187-.85-2.05-1.163-2.8-.312-.75-.625-.65-.85-.65-.225 0-.475-.025-.725-.025s-.675.1-.975.475-.975.95-.975 2.325.975 2.7 1.113 2.888c.137.187 1.975 3.012 4.787 4.225.663.287 1.188.462 1.588.587.675.213 1.288.187 1.775.113.538-.088 1.65-.675 1.888-1.325.237-.65.237-1.2.162-1.325-.075-.125-.275-.2-.625-.375z"/>
                                        </svg>
                                    </div>
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
                            <li><a href="https://lumiq-smoky.vercel.app/dashboard/wordpress" target="_blank">🔑 Gerar API Key</a></li>
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
                            <a href="https://lumiq-smoky.vercel.app/dashboard" target="_blank">Ver relatório completo →</a>
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
