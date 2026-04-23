jQuery(document).ready(function($) {
    
    console.log('LUMIQ JS carregado!');
    
    // OLHINHO
    $('#lumiq_toggle_key').on('click', function(e) {
        e.preventDefault();
        var $input = $('#lumiq_api_key');
        var $icon = $(this).find('.dashicons');
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });
    
    // VALIDAR
    $('#lumiq_validate_key').on('click', function(e) {
        e.preventDefault();
        
        var apiKey = $('#lumiq_api_key').val().trim();
        var $button = $(this);
        var $status = $('#lumiq_validate_status');
        
        if (!apiKey) {
            $status.html('<span style="color: #dc3545;">❌ Informe a API Key</span>').show();
            return;
        }
        
        $button.prop('disabled', true).text('Validando...');
        $status.html('<span style="color: #6c757d;">⏳ Verificando...</span>').show();
        
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
                    setTimeout(function() {
                        $('#lumiq_load_teams').trigger('click');
                    }, 500);
                } else {
                    $status.html('<span style="color: #dc3545;">❌ ' + (response.data.message || 'Erro') + '</span>');
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
    
    // CARREGAR EQUIPES
    $('#lumiq_load_teams').on('click', function(e) {
        e.preventDefault();
        
        var apiKey = $('#lumiq_api_key').val().trim();
        var $button = $(this);
        var $select = $('#lumiq_team_id');
        var $loading = $('#lumiq_teams_loading');
        var currentTeamId = $select.val();
        
        if (!apiKey) {
            alert('Informe a API Key!');
            return;
        }
        
        $button.prop('disabled', true);
        $loading.show();
        
        $.ajax({
            url: lumiqAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'lumiq_get_teams',
                nonce: lumiqAdmin.nonce,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success && response.data.teams) {
                    var teams = response.data.teams;
                    $select.html('<option value="">Selecione...</option>');
                    
                    $.each(teams, function(i, team) {
                        var $option = $('<option></option>')
                            .val(team.id)
                            .text(team.name)
                            .attr('data-name', team.name);
                        
                        if (team.id === currentTeamId) {
                            $option.prop('selected', true);
                        }
                        
                        $select.append($option);
                    });
                    
                    alert('✅ ' + teams.length + ' equipes carregadas!');
                } else {
                    alert('❌ Erro ao carregar equipes');
                }
            },
            error: function() {
                alert('❌ Erro de conexão');
            },
            complete: function() {
                $button.prop('disabled', false);
                $loading.hide();
            }
        });
    });
    
    // Atualizar o campo hidden do nome da equipe quando selecionar
    $('#lumiq_team_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var teamName = selectedOption.text();
        $('#lumiq_team_name').val(teamName);
    });
    
    // PREVIEW DO BOTÃO - Atualizar em tempo real
    function updatePreview() {
        var $preview = $('#lumiq-preview-button');
        var size = $('#lumiq_button_size').val();
        var style = $('input[name="lumiq_button_style"]:checked').val();
        var animation = $('#lumiq_button_animation').val();
        var color = $('#lumiq_button_color').val();
        var position = $('#lumiq_button_position').val();
        
        // Atualizar atributos
        $preview.attr('data-size', size);
        $preview.attr('data-style', style);
        $preview.attr('data-animation', animation);
        $preview.css('--lumiq-color', color);
        
        // Atualizar posição
        if (position === 'left') {
            $preview.css({'left': '20px', 'right': 'auto'});
        } else {
            $preview.css({'right': '20px', 'left': 'auto'});
        }
    }
    
    // Listeners para atualizar preview
    $('#lumiq_button_size, #lumiq_button_animation, #lumiq_button_position').on('change', updatePreview);
    $('#lumiq_button_color').on('input', updatePreview);
    $('input[name="lumiq_button_style"]').on('change', updatePreview);
    
    // Inicializar preview
    updatePreview();
});
