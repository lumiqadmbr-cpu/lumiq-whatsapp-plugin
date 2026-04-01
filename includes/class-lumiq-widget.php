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
        
        // Renderizar widget no footer
        add_action('wp_footer', array($this, 'render_widget'));
        
        // AJAX handlers para frontend
        add_action('wp_ajax_lumiq_capture_lead', array($this, 'ajax_capture_lead'));
        add_action('wp_ajax_nopriv_lumiq_capture_lead', array($this, 'ajax_capture_lead'));
        
        add_action('wp_ajax_lumiq_track_click', array($this, 'ajax_track_click'));
        add_action('wp_ajax_nopriv_lumiq_track_click', array($this, 'ajax_track_click'));
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
        
        ?>
        <!-- LUMIQ WhatsApp Widget -->
        <div id="lumiq-widget" class="lumiq-widget" data-capture-type="<?php echo esc_attr($capture_type); ?>">
            <button id="lumiq-widget-button" class="lumiq-widget-button" aria-label="<?php echo esc_attr($button_text); ?>">
                <svg viewBox="0 0 32 32" class="lumiq-widget-icon">
                    <path fill="currentColor" d="M16 0C7.163 0 0 7.163 0 16c0 2.825.737 5.477 2.024 7.777L.08 30.105l6.528-1.904A15.923 15.923 0 0016 32c8.837 0 16-7.163 16-16S24.837 0 16 0zm0 29.333c-2.547 0-4.955-.717-7.011-1.952l-.501-.296-5.189 1.515 1.54-5.024-.325-.52A13.276 13.276 0 012.667 16c0-7.363 5.971-13.333 13.333-13.333S29.333 8.637 29.333 16 23.363 29.333 16 29.333z"/>
                    <path fill="currentColor" d="M22.838 18.638c-.375-.187-2.225-1.1-2.575-1.225-.35-.125-.6-.187-.85.187-.25.375-.975 1.225-1.2 1.475-.225.25-.45.275-.825.1-.375-.187-1.587-.588-3.025-1.875-1.125-1-1.875-2.225-2.1-2.6-.225-.375-.025-.575.162-.762.163-.163.375-.425.562-.638.188-.212.25-.375.375-.625s.063-.475-.037-.663c-.1-.187-.85-2.05-1.163-2.8-.312-.75-.625-.65-.85-.65-.225 0-.475-.025-.725-.025s-.675.1-.975.475-.975.95-.975 2.325.975 2.7 1.113 2.888c.137.187 1.975 3.012 4.787 4.225.663.287 1.188.462 1.588.587.675.213 1.288.187 1.775.113.538-.088 1.65-.675 1.888-1.325.237-.65.237-1.2.162-1.325-.075-.125-.275-.2-.625-.375z"/>
                </svg>
                <span class="lumiq-widget-text"><?php echo esc_html($button_text); ?></span>
            </button>
        </div>
        
        <?php if ($capture_type === 'form'): ?>
        <!-- Modal de Formulário -->
        <div id="lumiq-modal" class="lumiq-modal" style="display: none;">
            <div class="lumiq-modal-overlay"></div>
            <div class="lumiq-modal-content">
                <button class="lumiq-modal-close" aria-label="Fechar">×</button>
                
                <div class="lumiq-modal-header">
                    <svg viewBox="0 0 32 32" class="lumiq-modal-icon">
                        <path fill="currentColor" d="M16 0C7.163 0 0 7.163 0 16c0 2.825.737 5.477 2.024 7.777L.08 30.105l6.528-1.904A15.923 15.923 0 0016 32c8.837 0 16-7.163 16-16S24.837 0 16 0zm0 29.333c-2.547 0-4.955-.717-7.011-1.952l-.501-.296-5.189 1.515 1.54-5.024-.325-.52A13.276 13.276 0 012.667 16c0-7.363 5.971-13.333 13.333-13.333S29.333 8.637 29.333 16 23.363 29.333 16 29.333z"/>
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
