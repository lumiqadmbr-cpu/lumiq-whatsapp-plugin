<?php
/**
 * Classe Widget (Botão Flutuante) do LUMIQ WhatsApp
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lumiq_Widget {
    
    public function __construct() {
        add_action('wp_footer', array($this, 'render_button'));
    }
    
    /**
     * Renderizar botão flutuante
     */
    public function render_button() {
        // Verificar se está habilitado
        if (!get_option('lumiq_enabled')) {
            return;
        }
        
        // Verificar se tem API Key
        if (empty(get_option('lumiq_api_key'))) {
            return;
        }
        
        // Verificar se tem equipe selecionada
        if (empty(get_option('lumiq_team_id'))) {
            return;
        }
        
        $position = get_option('lumiq_button_position', 'right');
        $color = get_option('lumiq_button_color', '#25D366');
        $size = get_option('lumiq_button_size', 'medium');
        $capture_type = get_option('lumiq_capture_type', 'form');
        
        ?>
        <!-- LUMIQ WhatsApp Button -->
        <div id="lumiq-whatsapp-button" 
             class="position-<?php echo esc_attr($position); ?> size-<?php echo esc_attr($size); ?>"
             style="background-color: <?php echo esc_attr($color); ?>;">
            <svg viewBox="0 0 24 24" fill="white">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
            </svg>
        </div>
        
        <?php if ($capture_type === 'form'): ?>
        <!-- Modal do Formulário -->
        <div id="lumiq-modal">
            <div id="lumiq-modal-content" style="position: relative;">
                <button id="lumiq-modal-close">&times;</button>
                <h3>Fale Conosco</h3>
                <form id="lumiq-form">
                    <input type="text" id="lumiq-name" placeholder="Seu nome *" required>
                    <input type="tel" id="lumiq-phone" placeholder="Seu WhatsApp *" required>
                    <input type="email" id="lumiq-email" placeholder="Seu email">
                    <textarea id="lumiq-message" rows="3" placeholder="Sua mensagem"></textarea>
                    <button type="submit">Enviar Mensagem</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <script>
        jQuery(document).ready(function($) {
            const button = $('#lumiq-whatsapp-button');
            const modal = $('#lumiq-modal');
            const closeBtn = $('#lumiq-modal-close');
            const captureType = '<?php echo esc_js($capture_type); ?>';
            
            // Click no botão
            button.on('click', function() {
                if (captureType === 'direct') {
                    // Redirecionar direto para captura
                    window.location.href = lumiqConfig.apiUrl + '/capture?api_key=' + lumiqConfig.apiKey + '&team_id=' + lumiqConfig.teamId;
                } else {
                    // Abrir modal
                    modal.addClass('active');
                }
            });
            
            // Fechar modal
            closeBtn.on('click', function() {
                modal.removeClass('active');
            });
            
            // Fechar modal ao clicar fora
            modal.on('click', function(e) {
                if (e.target === this) {
                    modal.removeClass('active');
                }
            });
            
            // Submit do formulário
            $('#lumiq-form').on('submit', function(e) {
                e.preventDefault();
                
                const name = $('#lumiq-name').val();
                const phone = $('#lumiq-phone').val();
                const email = $('#lumiq-email').val();
                const message = $('#lumiq-message').val();
                
                // Enviar para API
                $.ajax({
                    url: lumiqConfig.apiUrl + '/capture',
                    type: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'x-api-key': lumiqConfig.apiKey
                    },
                    data: JSON.stringify({
                        name: name,
                        phone: phone,
                        email: email,
                        message: message,
                        team_id: lumiqConfig.teamId
                    }),
                    success: function(response) {
                        if (response.success && response.whatsapp_url) {
                            window.location.href = response.whatsapp_url;
                        } else {
                            alert('Erro ao enviar. Tente novamente.');
                        }
                    },
                    error: function() {
                        alert('Erro ao enviar. Tente novamente.');
                    }
                });
            });
        });
        </script>
        <?php
    }
}
