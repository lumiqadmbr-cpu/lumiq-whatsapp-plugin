<?php
/**
 * Widget (botão flutuante) do frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lumiq_Widget {
    
    private $api;
    
    public function __construct() {
        $this->api = new Lumiq_API();

        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Renderizar widget no footer
        add_action('wp_footer', array($this, 'render_widget'));
        
        // AJAX handlers para frontend
        add_action('wp_ajax_lumiq_capture_lead', array($this, 'ajax_capture_lead'));
        add_action('wp_ajax_nopriv_lumiq_capture_lead', array($this, 'ajax_capture_lead'));
        
        add_action('wp_ajax_lumiq_track_click', array($this, 'ajax_track_click'));
        add_action('wp_ajax_nopriv_lumiq_track_click', array($this, 'ajax_track_click'));
    }

    /**
     * Carregar CSS e JS do frontend
     */
    public function enqueue_frontend_assets() {
        // Só carrega se estiver habilitado
        if (!get_option('lumiq_enabled')) {
            return;
        }
        
        // Só carrega se tiver API Key
        if (empty(get_option('lumiq_api_key'))) {
            return;
        }
        
        // CSS do widget
        wp_enqueue_style(
            'lumiq-frontend-css',
            LUMIQ_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            time()
        );
        
        // JS do widget
        wp_enqueue_script(
            'lumiq-frontend-js',
            LUMIQ_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            time(),
            true
        );
        
        // Passar dados para o JavaScript
        wp_localize_script('lumiq-frontend-js', 'lumiqConfig', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lumiq_frontend_nonce'),
            'captureType' => get_option('lumiq_capture_type', 'form'),
            'buttonColor' => get_option('lumiq_button_color', '#25D366'),
            'buttonPosition' => get_option('lumiq_button_position', 'right'),
            'buttonSize' => get_option('lumiq_button_size', 'medium'),
            'buttonStyle' => get_option('lumiq_button_style', 'circle'),
            'buttonAnimation' => get_option('lumiq_button_animation', 'pulse'),
            'teamId' => get_option('lumiq_team_id', '')
        ));
    }
    
    /**
     * Renderizar widget no footer
     */
    public function render_widget() {
        // Verificar se está habilitado
        if (!get_option('lumiq_enabled')) {
            return;
        }
        
        // Verificar se tem API Key
        if (empty(get_option('lumiq_api_key'))) {
            return;
        }
        
        $button_text = get_option('lumiq_button_text', 'Fale Conosco');
        $capture_type = get_option('lumiq_capture_type', 'form');
        $button_position = get_option('lumiq_button_position', 'right');
        $button_size = get_option('lumiq_button_size', 'medium');
        $button_style = get_option('lumiq_button_style', 'circle');
        $button_animation = get_option('lumiq_button_animation', 'pulse');
        $button_color = get_option('lumiq_button_color', '#25D366');
        
        ?>
        <!-- LUMIQ WhatsApp Widget -->
        <div id="lumiq-widget" 
             class="lumiq-widget" 
             data-capture-type="<?php echo esc_attr($capture_type); ?>"
             data-position="<?php echo esc_attr($button_position); ?>">
            <button id="lumiq-widget-button" 
                    class="lumiq-widget-button lumiq-style-<?php echo esc_attr($button_style); ?> lumiq-animation-<?php echo esc_attr($button_animation); ?>"
                    data-size="<?php echo esc_attr($button_size); ?>"
                    style="background-color: <?php echo esc_attr($button_color); ?>"
                    aria-label="<?php echo esc_attr($button_text); ?>">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 0C7.164 0 0 7.164 0 16c0 2.825.737 5.477 2.024 7.777L.08 30.105l6.528-1.904A15.923 15.923 0 0016 32c8.837 0 16-7.163 16-16S24.837 0 16 0z" fill="#fff"/>
                    <path d="M25.5 15.5c0 5.247-4.253 9.5-9.5 9.5a9.46 9.46 0 01-4.849-1.337l-3.396.891.915-3.341A9.453 9.453 0 016.5 15.5c0-5.247 4.253-9.5 9.5-9.5s9.5 4.253 9.5 9.5z" fill="currentColor"/>
                    <path d="M20.347 18.24c-.271-.135-1.605-.792-1.853-.882-.248-.09-.429-.135-.61.135-.18.27-.702.882-.86 1.062-.158.18-.316.203-.587.067-.271-.135-1.144-.421-2.178-1.343-.806-.717-1.35-1.603-1.508-1.873-.158-.27-.017-.416.118-.55.122-.121.27-.316.405-.473.135-.158.18-.27.27-.45.09-.18.045-.338-.022-.473-.067-.135-.61-1.47-.835-2.014-.22-.529-.443-.457-.61-.466-.158-.008-.338-.01-.518-.01s-.473.067-.72.338c-.247.27-.946.925-.946 2.256s.968 2.618 1.103 2.798c.135.18 1.901 2.902 4.605 4.07.645.28 1.148.447 1.541.572.648.206.237.178 1.615.106.49-.054 1.505-.615 1.717-1.208.212-.594.212-1.103.148-1.208-.064-.105-.245-.17-.516-.305z" fill="#fff"/>
                </svg>
            </button>
        </div>
        
        <?php if ($capture_type === 'form'): ?>
        <!-- Modal de Formulário -->
        <div id="lumiq-modal" class="lumiq-modal" style="display: none;">
            <div class="lumiq-modal-overlay"></div>
            <div class="lumiq-modal-content">
                <button class="lumiq-modal-close" aria-label="Fechar">×</button>
                
                <div class="lumiq-modal-header">
                    <svg width="48" height="48" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 0C7.164 0 0 7.164 0 16c0 2.825.737 5.477 2.024 7.777L.08 30.105l6.528-1.904A15.923 15.923 0 0016 32c8.837 0 16-7.163 16-16S24.837 0 16 0z" fill="<?php echo esc_attr($button_color); ?>"/>
                        <path d="M25.5 15.5c0 5.247-4.253 9.5-9.5 9.5a9.46 9.46 0 01-4.849-1.337l-3.396.891.915-3.341A9.453 9.453 0 016.5 15.5c0-5.247 4.253-9.5 9.5-9.5s9.5 4.253 9.5 9.5z" fill="<?php echo esc_attr($button_color); ?>"/>
                        <path d="M20.347 18.24c-.271-.135-1.605-.792-1.853-.882-.248-.09-.429-.135-.61.135-.18.27-.702.882-.86 1.062-.158.18-.316.203-.587.067-.271-.135-1.144-.421-2.178-1.343-.806-.717-1.35-1.603-1.508-1.873-.158-.27-.017-.416.118-.55.122-.121.27-.316.405-.473.135-.158.18-.27.27-.45.09-.18.045-.338-.022-.473-.067-.135-.61-1.47-.835-2.014-.22-.529-.443-.457-.61-.466-.158-.008-.338-.01-.518-.01s-.473.067-.72.338c-.247.27-.946.925-.946 2.256s.968 2.618 1.103 2.798c.135.18 1.901 2.902 4.605 4.07.645.28 1.148.447 1.541.572.648.206.237.178 1.615.106.49-.054 1.505-.615 1.717-1.208.212-.594.212-1.103.148-1.208-.064-.105-.245-.17-.516-.305z" fill="#fff"/>
                    </svg>
                    <h3>Fale Conosco</h3>
                    <p>Preencha os dados abaixo e nossa equipe entrará em contato</p>
                </div>
                
                <form id="lumiq-form" class="lumiq-form">
                    <div class="lumiq-form-group">
                        <label for="lumiq-name">Nome *</label>
                        <input type="text" id="lumiq-name" name="name" required placeholder="Seu nome">
                    </div>
                    
                    <div class="lumiq-form-group">
                        <label for="lumiq-phone">WhatsApp *</label>
                        <input type="tel" id="lumiq-phone" name="phone" required placeholder="(00) 00000-0000">
                    </div>
                    
                    <div class="lumiq-form-group">
                        <label for="lumiq-email">Email</label>
                        <input type="email" id="lumiq-email" name="email" placeholder="seu@email.com">
                    </div>
                    
                    <div class="lumiq-form-group">
                        <label for="lumiq-message">Mensagem</label>
                        <textarea id="lumiq-message" name="message" rows="3" placeholder="Como podemos ajudar?"></textarea>
                    </div>
                    
                    <button type="submit" class="lumiq-form-submit">
                        <span class="lumiq-submit-text">Enviar Mensagem</span>
                        <span class="lumiq-submit-loading" style="display: none;">
                            <span class="lumiq-spinner"></span> Enviando...
                        </span>
                    </button>
                    
                    <p class="lumiq-form-privacy">
                        Seus dados estão protegidos e serão usados apenas para contato.
                    </p>
                </form>
                
                <div id="lumiq-success" class="lumiq-success" style="display: none;">
                    <div class="lumiq-success-icon">✓</div>
                    <h3>Mensagem enviada!</h3>
                    <p>Em breve nossa equipe entrará em contato via WhatsApp.</p>
                    <p class="lumiq-redirect-msg">Redirecionando para o WhatsApp...</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <!-- /LUMIQ WhatsApp Widget -->
        <?php
    }
    
    /**
     * AJAX: Capturar lead
     */
    public function ajax_capture_lead() {
        check_ajax_referer('lumiq_frontend_nonce', 'nonce');
        
        // Sanitizar dados
        $lead_data = $this->api->sanitize_lead_data(array(
            'name' => $_POST['name'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'email' => $_POST['email'] ?? '',
            'message' => $_POST['message'] ?? '',
            'team_id' => get_option('lumiq_team_id'),
            'page_url' => $_POST['page_url'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ));
        
        // Validar campos obrigatórios
        if (empty($lead_data['name']) || empty($lead_data['phone'])) {
            wp_send_json_error(array('message' => 'Nome e telefone são obrigatórios'));
        }
        
        // Enviar para API LUMIQ
        $result = $this->api->capture_lead($lead_data);
        
        if ($result && isset($result['success'])) {
            wp_send_json_success(array(
                'message' => 'Lead capturado com sucesso!',
                'whatsapp_url' => $result['whatsapp_url'] ?? '',
                'whatsapp_number' => $result['whatsapp_number'] ?? ''
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message'] ?? 'Erro ao capturar lead. Tente novamente.'
            ));
        }
    }
    
    /**
     * AJAX: Registrar clique
     */
    public function ajax_track_click() {
        check_ajax_referer('lumiq_frontend_nonce', 'nonce');
        
        $this->api->track_click();
        
        wp_send_json_success();
    }
}
