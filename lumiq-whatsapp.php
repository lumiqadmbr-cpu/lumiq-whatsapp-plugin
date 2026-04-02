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
define('LUMIQ_TEAMS_API_URL', 'https://lumiq-smoky.vercel.app/api/teams');

class LumiqWhatsApp {
    
    private $option_name = 'lumiq_settings';
    
    public function __construct() {
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Frontend hooks
        add_action('wp_footer', array($this, 'add_whatsapp_button'));
        
        // AJAX hooks
        add_action('wp_ajax_lumiq_fetch_teams', array($this, 'ajax_fetch_teams'));
        add_action('wp_ajax_lumiq_validate_key', array($this, 'ajax_validate_key'));
    }
    
    /**
     * Adiciona menu no admin
     */
    public function add_admin_menu() {
        add_menu_page(
            'LUMIQ WhatsApp',
            'LUMIQ',
            'manage_options',
            'lumiq-whatsapp',
            array($this, 'settings_page'),
            'dashicons-whatsapp',
            30
        );
    }
    
    /**
     * Registra configurações
     */
    public function register_settings() {
        register_setting('lumiq_settings_group', $this->option_name);
        
        add_settings_section(
            'lumiq_main_section',
            'Configurações da API',
            array($this, 'section_callback'),
            'lumiq-whatsapp'
        );
        
        add_settings_field(
            'api_key',
            'API Key',
            array($this, 'api_key_field'),
            'lumiq-whatsapp',
            'lumiq_main_section'
        );
        
        add_settings_field(
            'team_id',
            'Equipe de Distribuição',
            array($this, 'team_field'),
            'lumiq-whatsapp',
            'lumiq_main_section'
        );
        
        add_settings_field(
            'button_text',
            'Texto do Botão (Tooltip)',
            array($this, 'button_text_field'),
            'lumiq-whatsapp',
            'lumiq_main_section'
        );
        
        add_settings_field(
            'button_message',
            'Mensagem Padrão',
            array($this, 'button_message_field'),
            'lumiq-whatsapp',
            'lumiq_main_section'
        );
        
        add_settings_field(
            'button_position',
            'Posição do Botão',
            array($this, 'button_position_field'),
            'lumiq-whatsapp',
            'lumiq_main_section'
        );
    }
    
    /**
     * Enfileira scripts e estilos do admin
     */
    public function enqueue_admin_scripts($hook) {
        // Apenas na página do plugin
        if ($hook !== 'toplevel_page_lumiq-whatsapp') {
            return;
        }
        
        wp_enqueue_script(
            'lumiq-admin-js',
            LUMIQ_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            LUMIQ_VERSION,
            true
        );
        
        wp_localize_script('lumiq-admin-js', 'lumiqAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lumiq_nonce'),
            'teams_api_url' => LUMIQ_TEAMS_API_URL
        ));
        
        // CSS do admin (opcional)
        wp_enqueue_style(
            'lumiq-admin-css',
            LUMIQ_PLUGIN_URL . 'assets/admin.css',
            array(),
            LUMIQ_VERSION
        );
    }
    
    /**
     * AJAX: Buscar equipes da API LUMIQ
     */
    public function ajax_fetch_teams() {
        check_ajax_referer('lumiq_nonce', 'nonce');
        
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API Key não informada'));
            return;
        }
        
        // Requisição para API LUMIQ
        $response = wp_remote_get(LUMIQ_TEAMS_API_URL, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => 'Erro ao conectar: ' . $response->get_error_message()
            ));
            return;
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($http_code !== 200) {
            wp_send_json_error(array(
                'message' => 'API retornou erro: ' . ($data['error'] ?? 'Desconhecido')
            ));
            return;
        }
        
        if (isset($data['teams']) && is_array($data['teams'])) {
            wp_send_json_success(array('teams' => $data['teams']));
        } else {
            wp_send_json_error(array('message' => 'Formato de resposta inválido'));
        }
    }
    
    /**
     * AJAX: Validar API Key
     */
    public function ajax_validate_key() {
        check_ajax_referer('lumiq_nonce', 'nonce');
        
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API Key não informada'));
            return;
        }
        
        $response = wp_remote_get(LUMIQ_API_URL . '/validate-key?key=' . urlencode($api_key), array(
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Erro ao validar'));
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['valid']) && $data['valid'] === true) {
            wp_send_json_success(array('message' => 'API Key válida!'));
        } else {
            wp_send_json_error(array('message' => 'API Key inválida'));
        }
    }
    
    /**
     * Callbacks das seções e campos
     */
    public function section_callback() {
        echo '<p>Configure sua integração com LUMIQ para distribuir leads automaticamente via WhatsApp.</p>';
    }
    
    public function api_key_field() {
        $options = get_option($this->option_name);
        $value = isset($options['api_key']) ? $options['api_key'] : '';
        ?>
        <input type="text" 
               id="lumiq_api_key" 
               name="<?php echo $this->option_name; ?>[api_key]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" 
               placeholder="lumiq_live_..." />
        <button type="button" id="lumiq_validate_key" class="button" style="margin-left: 10px;">
            Validar
        </button>
        <span id="lumiq_validate_status" style="margin-left: 10px;"></span>
        <p class="description">
            Obtenha sua API Key no <a href="https://lumiq-smoky.vercel.app/dashboard/wordpress" target="_blank">dashboard LUMIQ</a>
        </p>
        <?php
    }
    
    public function team_field() {
        $options = get_option($this->option_name);
        $selected = isset($options['team_id']) ? $options['team_id'] : '';
        ?>
        <select id="lumiq_team_id" 
                name="<?php echo $this->option_name; ?>[team_id]" 
                class="regular-text">
            <option value="">Selecione uma equipe...</option>
            <?php if (!empty($selected)): ?>
                <option value="<?php echo esc_attr($selected); ?>" selected>
                    Equipe Selecionada
                </option>
            <?php endif; ?>
        </select>
        <button type="button" id="lumiq_load_teams" class="button" style="margin-left: 10px;">
            Carregar Equipes
        </button>
        <span id="lumiq_teams_loading" style="display:none; margin-left: 10px;">
            <span class="spinner is-active" style="float: none; margin: 0;"></span>
        </span>
        <p class="description">Clique em "Carregar Equipes" após informar a API Key válida</p>
        <?php
    }
    
    public function button_text_field() {
        $options = get_option($this->option_name);
        $value = isset($options['button_text']) ? $options['button_text'] : 'Fale Conosco';
        ?>
        <input type="text" 
               name="<?php echo $this->option_name; ?>[button_text]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" />
        <p class="description">Texto que aparece ao passar o mouse sobre o botão</p>
        <?php
    }
    
    public function button_message_field() {
        $options = get_option($this->option_name);
        $value = isset($options['button_message']) ? $options['button_message'] : 'Olá! Vim do site e gostaria de mais informações.';
        ?>
        <textarea name="<?php echo $this->option_name; ?>[button_message]" 
                  rows="3" 
                  class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">Mensagem que será enviada automaticamente ao vendedor</p>
        <?php
    }
    
    public function button_position_field() {
        $options = get_option($this->option_name);
        $value = isset($options['button_position']) ? $options['button_position'] : 'bottom-right';
        ?>
        <select name="<?php echo $this->option_name; ?>[button_position]">
            <option value="bottom-right" <?php selected($value, 'bottom-right'); ?>>
                Inferior Direito
            </option>
            <option value="bottom-left" <?php selected($value, 'bottom-left'); ?>>
                Inferior Esquerdo
            </option>
        </select>
        <p class="description">Posição do botão flutuante no site</p>
        <?php
    }
    
    /**
     * Página de configurações
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>⚡ LUMIQ WhatsApp</h1>
            <p class="description" style="font-size: 14px; margin-bottom: 20px;">
                Versão <?php echo LUMIQ_VERSION; ?> | 
                <a href="https://lumiq-smoky.vercel.app" target="_blank">Dashboard LUMIQ</a>
            </p>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('lumiq_settings_group');
                do_settings_sections('lumiq-whatsapp');
                submit_button('💾 Salvar Configurações');
                ?>
            </form>
            
            <hr style="margin: 40px 0;">
            
            <div class="lumiq-help-box">
                <h2>📋 Como Configurar</h2>
                <ol>
                    <li>Cole sua <strong>API Key</strong> obtida no dashboard LUMIQ</li>
                    <li>Clique em <strong>"Validar"</strong> para confirmar a chave</li>
                    <li>Clique em <strong>"Carregar Equipes"</strong> para listar suas equipes disponíveis</li>
                    <li>Selecione a <strong>equipe</strong> que receberá os leads deste site</li>
                    <li>Personalize o texto do tooltip e mensagem padrão</li>
                    <li>Escolha a posição do botão flutuante</li>
                    <li>Salve as configurações</li>
                </ol>
                
                <p><strong>✅ Pronto! O botão WhatsApp aparecerá automaticamente em todas as páginas do seu site.</strong></p>
                
                <h3>🎯 Como Funciona</h3>
                <ul>
                    <li>Quando um visitante clicar no botão, ele será direcionado automaticamente para um vendedor da equipe</li>
                    <li>A distribuição segue a estratégia configurada no LUMIQ (Round Robin, Random ou Prioridade)</li>
                    <li>Todos os leads são registrados no dashboard para acompanhamento</li>
                </ul>
                
                <h3>💡 Precisa de Ajuda?</h3>
                <p>Acesse o <a href="https://lumiq-smoky.vercel.app/dashboard" target="_blank">Dashboard LUMIQ</a> para gerenciar equipes, ver relatórios e configurações avançadas.</p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Adiciona botão flutuante WhatsApp no frontend
     */
    public function add_whatsapp_button() {
        $options = get_option($this->option_name);
        
        // Verifica configurações obrigatórias
        if (empty($options['api_key']) || empty($options['team_id'])) {
            return;
        }
        
        $button_text = isset($options['button_text']) ? $options['button_text'] : 'Fale Conosco';
        $button_message = isset($options['button_message']) ? $options['button_message'] : 'Olá! Vim do site e gostaria de mais informações.';
        $button_position = isset($options['button_position']) ? $options['button_position'] : 'bottom-right';
        
        // Define posicionamento
        $position_style = $button_position === 'bottom-left' 
            ? 'left: 20px;' 
            : 'right: 20px;';
        
        ?>
        <!-- LUMIQ WhatsApp Button -->
        <style>
            #lumiq-whatsapp-button {
                position: fixed;
                bottom: 20px;
                <?php echo $position_style; ?>
                width: 60px;
                height: 60px;
                background-color: #25D366;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
                z-index: 9999;
                transition: all 0.3s ease;
                border: none;
                outline: none;
            }
            
            #lumiq-whatsapp-button:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 20px rgba(37, 211, 102, 0.6);
                background-color: #20BA5A;
            }
            
            #lumiq-whatsapp-button:active {
                transform: scale(1.05);
            }
            
            #lumiq-whatsapp-button svg {
                width: 32px;
                height: 32px;
                fill: white;
            }
            
            /* Tooltip */
            #lumiq-whatsapp-button::before {
                content: attr(data-tooltip);
                position: absolute;
                <?php echo $button_position === 'bottom-left' ? 'left: 70px;' : 'right: 70px;'; ?>
                background: #333;
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                white-space: nowrap;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.3s ease;
                font-size: 14px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            }
            
            #lumiq-whatsapp-button:hover::before {
                opacity: 1;
            }
            
            /* Animação de pulse sutil */
            @keyframes lumiq-pulse {
                0%, 100% {
                    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
                }
                50% {
                    box-shadow: 0 4px 20px rgba(37, 211, 102, 0.6);
                }
            }
            
            #lumiq-whatsapp-button {
                animation: lumiq-pulse 2s ease-in-out infinite;
            }
            
            #lumiq-whatsapp-button:hover {
                animation: none;
            }
            
            /* Responsive */
            @media (max-width: 768px) {
                #lumiq-whatsapp-button {
                    width: 56px;
                    height: 56px;
                    bottom: 15px;
                    <?php echo $button_position === 'bottom-left' ? 'left: 15px;' : 'right: 15px;'; ?>
                }
                
                #lumiq-whatsapp-button svg {
                    width: 28px;
                    height: 28px;
                }
                
                #lumiq-whatsapp-button::before {
                    display: none; /* Esconde tooltip no mobile */
                }
            }
        </style>
        
        <button 
            id="lumiq-whatsapp-button" 
            data-tooltip="<?php echo esc_attr($button_text); ?>"
            data-message="<?php echo esc_attr($button_message); ?>"
            data-api-key="<?php echo esc_attr($options['api_key']); ?>"
            data-team-id="<?php echo esc_attr($options['team_id']); ?>"
            aria-label="<?php echo esc_attr($button_text); ?>"
        >
            <!-- Ícone WhatsApp SVG -->
            <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                <path d="M16 0C7.163 0 0 7.163 0 16c0 2.825.739 5.478 2.032 7.781L0 32l8.448-2.016A15.923 15.923 0 0016 32c8.837 0 16-7.163 16-16S24.837 0 16 0zm0 29.333c-2.547 0-4.96-.713-7.013-1.952l-.501-.304-5.189 1.237 1.253-4.992-.333-.52A13.227 13.227 0 012.667 16c0-7.363 5.97-13.333 13.333-13.333S29.333 8.637 29.333 16 23.363 29.333 16 29.333zm7.317-9.984c-.4-.2-2.373-1.173-2.741-1.307-.368-.133-.635-.2-.901.2-.267.4-1.035 1.307-1.269 1.573-.235.267-.469.301-.869.1-.4-.2-1.688-.621-3.216-1.984-1.189-1.061-1.992-2.371-2.227-2.771-.235-.4-.025-.616.176-.816.181-.181.4-.469.6-.704.2-.235.267-.4.4-.667.133-.267.067-.501-.033-.704-.1-.2-.901-2.173-1.235-2.973-.325-.776-.656-.672-.901-.685-.235-.013-.501-.016-.768-.016s-.704.1-1.072.501c-.368.4-1.403 1.371-1.403 3.344s1.437 3.877 1.637 4.144c.2.267 2.827 4.317 6.848 6.053.957.413 1.704.659 2.285.843.96.304 1.835.261 2.525.157.771-.115 2.373-.971 2.707-1.909.333-.939.333-1.744.235-1.909-.1-.165-.368-.267-.768-.469z"/>
            </svg>
        </button>
        
        <script>
        (function() {
            'use strict';
            
            const button = document.getElementById('lumiq-whatsapp-button');
            
            if (!button) return;
            
            button.addEventListener('click', async function(e) {
                e.preventDefault();
                
                const apiKey = this.dataset.apiKey;
                const teamId = this.dataset.teamId;
                const message = this.dataset.message;
                
                // Desabilita botão temporariamente
                this.style.opacity = '0.6';
                this.style.pointerEvents = 'none';
                
                try {
                    const response = await fetch('<?php echo LUMIQ_API_URL; ?>/capture', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'x-api-key': apiKey
                        },
                        body: JSON.stringify({
                            name: 'Visitante do Site',
                            phone: '',
                            email: '',
                            message: message,
                            team_id: teamId
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success && data.whatsapp_url) {
                        // Abre WhatsApp em nova aba
                        window.open(data.whatsapp_url, '_blank');
                    } else {
                        console.error('LUMIQ Error:', data);
                        alert('Erro ao conectar. Tente novamente em instantes.');
                    }
                    
                } catch (error) {
                    console.error('LUMIQ Error:', error);
                    alert('Erro ao conectar. Verifique sua conexão e tente novamente.');
                    
                } finally {
                    // Reabilita botão
                    setTimeout(() => {
                        this.style.opacity = '1';
                        this.style.pointerEvents = 'auto';
                    }, 1000);
                }
            });
        })();
        </script>
        <!-- /LUMIQ WhatsApp Button -->
        <?php
    }
}

// Inicializa o plugin
new LumiqWhatsApp();
