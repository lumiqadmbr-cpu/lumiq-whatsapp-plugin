/**
 * LUMIQ WhatsApp - Admin JavaScript
 * Gerencia validação de API Key e carregamento de equipes
 */

jQuery(document).ready(function($) {
    
    /**
     * Botão "Validar" API Key
     */
    $('#lumiq_validate_key').on('click', function() {
        const apiKey = $('#lumiq_api_key').val().trim();
        const $button = $(this);
        const $status = $('#lumiq_validate_status');
        
        if (!apiKey) {
            $status.html('<span style="color: #dc3545;">❌ Informe a API Key</span>');
            return;
        }
        
        // Loading
        $button.prop('disabled', true).text('Validando...');
        $status.html('<span style="color: #6c757d;">⏳ Verificando...</span>');
        
        $.ajax({
            url: lumiqAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'lumiq_validate_key',
                nonce: lumiqAdmin.nonce,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    $status.html('<span style="color: #28a745;">✅ ' + response.data.message + '</span>');
                    
                    // Auto-carrega equipes após validação bem-sucedida
                    setTimeout(function() {
                        $('#lumiq_load_teams').trigger('click');
                    }, 500);
                    
                } else {
                    $status.html('<span style="color: #dc3545;">❌ ' + response.data.message + '</span>');
                }
            },
            error: function() {
                $status.html('<span style="color: #dc3545;">❌ Erro de conexão</span>');
            },
            complete: function() {
                $button.prop('disabled', false).text('Validar');
            }
        });
    });
    
    /**
     * Botão "Carregar Equipes"
     */
    $('#lumiq_load_teams').on('click', function() {
        const apiKey = $('#lumiq_api_key').val().trim();
        const $button = $(this);
        const $select = $('#lumiq_team_id');
        const $loading = $('#lumiq_teams_loading');
        
        if (!apiKey) {
            alert('⚠️ Por favor, informe a API Key primeiro!');
            $('#lumiq_api_key').focus();
            return;
        }
        
        // Mostra loading
        $button.prop('disabled', true);
        $loading.show();
        
        $.ajax({
            url: lumiqAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'lumiq_fetch_teams',
                nonce: lumiqAdmin.nonce,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success && response.data.teams) {
                    const teams = response.data.teams;
                    
                    // Salva seleção atual
                    const currentValue = $select.val();
                    
                    // Limpa select
                    $select.html('<option value="">Selecione uma equipe...</option>');
                    
                    // Popula com equipes
                    teams.forEach(function(team) {
                        const isActive = team.is_active !== false;
                        const label = team.name + (isActive ? '' : ' (Inativa)');
                        
                        $select.append(
                            $('<option></option>')
                                .val(team.id)
                                .text(label)
                                .prop('disabled', !isActive)
                        );
                    });
                    
                    // Restaura seleção se ainda existir
                    if (currentValue) {
                        $select.val(currentValue);
                    }
                    
                    // Feedback visual
                    $select.css('border-color', '#28a745');
                    setTimeout(function() {
                        $select.css('border-color', '');
                    }, 2000);
                    
                    // Mensagem de sucesso
                    const msg = teams.length === 1 
                        ? '✅ 1 equipe carregada!' 
                        : '✅ ' + teams.length + ' equipes carregadas!';
                    
                    showNotice(msg, 'success');
                    
                } else {
                    const errorMsg = response.data && response.data.message 
                        ? response.data.message 
                        : 'Erro ao carregar equipes';
                    
                    showNotice('❌ ' + errorMsg, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('LUMIQ Error:', error);
                showNotice('❌ Erro ao conectar com a API LUMIQ', 'error');
            },
            complete: function() {
                $button.prop('disabled', false);
                $loading.hide();
            }
        });
    });
    
    /**
     * Auto-carrega equipes se API Key já existe
     */
    const apiKeyValue = $('#lumiq_api_key').val().trim();
    if (apiKeyValue && $('#lumiq_team_id option').length <= 2) {
        // Aguarda 500ms para dar tempo da página carregar
        setTimeout(function() {
            $('#lumiq_load_teams').trigger('click');
        }, 500);
    }
    
    /**
     * Helper: Mostrar notificação WordPress-style
     */
    function showNotice(message, type) {
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        // Auto-remove após 5 segundos
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Validação antes de salvar
     */
    $('form').on('submit', function(e) {
        const apiKey = $('#lumiq_api_key').val().trim();
        const teamId = $('#lumiq_team_id').val();
        
        if (!apiKey) {
            e.preventDefault();
            alert('⚠️ Por favor, informe a API Key!');
            $('#lumiq_api_key').focus();
            return false;
        }
        
        if (!teamId) {
            e.preventDefault();
            alert('⚠️ Por favor, selecione uma equipe!');
            $('#lumiq_team_id').focus();
            return false;
        }
        
        return true;
    });
});
