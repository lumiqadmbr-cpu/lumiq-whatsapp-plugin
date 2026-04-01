<?php
/**
 * Classe de comunicação com API LUMIQ
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lumiq_API {
    
    private $api_url;
    
    public function __construct() {
        $this->api_url = LUMIQ_API_URL;
    }
    
    /**
     * Validar API Key
     * 
     * @param string $api_key
     * @return array|false
     */
    public function validate_key($api_key) {
        $response = wp_remote_post($this->api_url . '/validate', array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'api_key' => $api_key
            ))
        ));
        
        if (is_wp_error($response)) {
            error_log('LUMIQ API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data;
    }
    
    /**
     * Buscar equipes do usuário
     * 
     * @param string $api_key
     * @return array|false
     */
    public function get_teams($api_key) {
        $response = wp_remote_get($this->api_url . '/teams', array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('LUMIQ API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data;
    }
    
    /**
     * Capturar lead
     * 
     * @param array $lead_data
     * @return array|false
     */
    public function capture_lead($lead_data) {
        $api_key = get_option('lumiq_api_key');
        
        if (empty($api_key)) {
            return false;
        }
        
        $response = wp_remote_post($this->api_url . '/capture', array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode($lead_data)
        ));
        
        if (is_wp_error($response)) {
            error_log('LUMIQ API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data;
    }
    
    /**
     * Registrar clique no botão
     * 
     * @return bool
     */
    public function track_click() {
        $api_key = get_option('lumiq_api_key');
        
        if (empty($api_key)) {
            return false;
        }
        
        $response = wp_remote_post($this->api_url . '/track-click', array(
            'timeout' => 5,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode(array(
                'site_url' => get_site_url(),
                'timestamp' => current_time('mysql')
            ))
        ));
        
        return !is_wp_error($response);
    }
    
    /**
     * Enviar estatísticas
     * 
     * @param array $stats
     * @return bool
     */
    public function send_stats($stats) {
        $api_key = get_option('lumiq_api_key');
        
        if (empty($api_key)) {
            return false;
        }
        
        $response = wp_remote_post($this->api_url . '/stats', array(
            'timeout' => 10,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode($stats)
        ));
        
        return !is_wp_error($response);
    }
    
    /**
     * Verificar status da API
     * 
     * @return bool
     */
    public function check_status() {
        $response = wp_remote_get($this->api_url . '/status', array(
            'timeout' => 5
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        return $code === 200;
    }
    
    /**
     * Sanitizar dados do lead
     * 
     * @param array $data
     * @return array
     */
    public function sanitize_lead_data($data) {
        return array(
            'name' => sanitize_text_field($data['name'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'message' => sanitize_textarea_field($data['message'] ?? ''),
            'team_id' => sanitize_text_field($data['team_id'] ?? ''),
            'source' => 'wordpress-plugin',
            'page_url' => esc_url_raw($data['page_url'] ?? ''),
            'user_agent' => sanitize_text_field($data['user_agent'] ?? '')
        );
    }
    
    /**
     * Log de erro
     * 
     * @param string $message
     * @param mixed $data
     */
    public function log_error($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('LUMIQ WhatsApp: ' . $message);
            if ($data) {
                error_log('Data: ' . print_r($data, true));
            }
        }
    }
}
