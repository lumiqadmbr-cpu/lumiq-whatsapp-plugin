jQuery(document).ready(function($) {
    
    // Validar API Key
    $('#validate-key-btn').on('click', function() {
        const apiKey = $('#lumiq_api_key').val();
        const button = $(this);
        const resultDiv = $('#key-validation-result');
        
        if (!apiKey) {
            resultDiv.removeClass('success').addClass('error').html('Por favor, insira uma API Key!');
            return;
        }
        
        button.prop('disabled', true).text('Validando...');
        resultDiv.removeClass('success error').html('');
        
        $.ajax({
            url: lumiqAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'lumiq_validate_key',
                nonce: lumiqAdmin.nonce,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    resultDiv.addClass('success').html('✅ ' + response.data.message);
                    
                    // Popular dropdown de equipes
                    if (response.data.teams && response.data.teams.length > 0) {
                        const select = $('#lumiq_team_id');
                        select.html('<option value="">Selecione uma equipe...</option>');
                        
                        response.data.teams.forEach(function(team) {
                            select.append('<option value="' + team.id + '">' + team.name + '</option>');
                        });
                        
                        resultDiv.append('<br>✅ ' + response.data.teams.length + ' equipe(s) encontrada(s)');
                    }
                } else {
                    resultDiv.addClass('error').html('❌ ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                resultDiv.addClass('error').html('❌ Erro ao validar: ' + error);
            },
            complete: function() {
                button.prop('disabled', false).text('Validar Chave');
            }
        });
    });
    
    // Carregar Equipes
    $('#load-teams-btn').on('click', function() {
        const button = $(this);
        const select = $('#lumiq_team_id');
        const apiKey = $('#lumiq_api_key').val();
        
        if (!apiKey) {
            alert('Por favor, configure e valide a API Key primeiro!');
            return;
        }
        
        button.prop('disabled', true).text('Carregando...');
        
        $.ajax({
            url: lumiqAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'lumiq_load_teams',
                nonce: lumiqAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data.teams) {
                    select.html('<option value="">Selecione uma equipe...</option>');
                    
                    response.data.teams.forEach(function(team) {
                        select.append('<option value="' + team.id + '">' + team.name + '</option>');
                    });
                    
                    alert('✅ ' + response.data.teams.length + ' equipe(s) carregada(s)!');
                } else {
                    alert('❌ ' + (response.data || 'Erro ao carregar equipes'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro completo:', xhr.responseText);
                alert('❌ Erro ao carregar equipes: ' + error);
            },
            complete: function() {
                button.prop('disabled', false).text('Carregar Equipes');
            }
        });
    });
    
    // Atualizar preview ao mudar cor/tamanho/posição
    $('input[name="lumiq_button_color"]').on('change', function() {
        $('#lumiq-preview-button').css('background-color', $(this).val());
    });
    
    $('select[name="lumiq_button_size"]').on('change', function() {
        const sizes = { small: '50px', medium: '60px', large: '70px' };
        const size = sizes[$(this).val()];
        $('#lumiq-preview-button').css({ width: size, height: size });
    });
    
    $('select[name="lumiq_button_position"]').on('change', function() {
        const preview = $('#lumiq-preview-button');
        if ($(this).val() === 'left') {
            preview.css({ left: '20px', right: 'auto' });
        } else {
            preview.css({ right: '20px', left: 'auto' });
        }
    });
});
