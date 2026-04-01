/**
 * LUMIQ WhatsApp - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Toggle API Key visibility
        $('#lumiq-toggle-key').on('click', function() {
            const $input = $('#lumiq_api_key');
            const type = $input.attr('type');
            
            if (type === 'password') {
                $input.attr('type', 'text');
                $(this).find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                $input.attr('type', 'password');
                $(this).find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        });
        
        // Validate API Key
        $('#lumiq-validate-key').on('click', function() {
            const $button = $(this);
            const $status = $('#lumiq-key-status');
            const apiKey = $('#lumiq_api_key').val().trim();
            
            if (!apiKey) {
                showStatus('error', 'Digite uma API Key válida');
                return;
            }
            
            $button.prop('disabled', true).text('Validando...');
            showStatus('loading', 'Validando chave com LUMIQ...');
            
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
                        showStatus('success', response.data.message);
                        
                        // Carregar equipes
                        if (response.data.teams && response.data.teams.length > 0) {
                            populateTeams(response.data.teams);
                            $('#lumiq-team-row').slideDown();
                        }
                        
                        // Auto-save
                        setTimeout(function() {
                            $('#lumiq-settings-form').submit();
                        }, 1500);
                    } else {
                        showStatus('error', response.data.message);
                    }
                },
                error: function() {
                    showStatus('error', 'Erro de conexão. Verifique sua internet.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Validar Chave');
                }
            });
        });
        
        // Load teams on page load if API key exists
        if ($('#lumiq_api_key').val()) {
            loadTeams();
        }
        
        function loadTeams() {
            $.ajax({
                url: lumiqAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lumiq_get_teams',
                    nonce: lumiqAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.teams) {
                        populateTeams(response.data.teams);
                        $('#lumiq-team-row').show();
                    }
                }
            });
        }
        
        function populateTeams(teams) {
            const $select = $('#lumiq_team_id');
            const currentValue = $select.data('current') || $select.val();
            
            $select.empty();
            $select.append('<option value="">Selecione uma equipe...</option>');
            
            teams.forEach(function(team) {
                const selected = team.id === currentValue ? 'selected' : '';
                $select.append(
                    `<option value="${team.id}" ${selected}>${team.name} (${team.member_count} vendedores)</option>`
                );
            });
        }
        
        function showStatus(type, message) {
            const $status = $('#lumiq-key-status');
            $status.removeClass('success error loading').addClass(type);
            $status.html(message).slideDown();
        }
        
        // Live Preview
        function updatePreview() {
            const color = $('#lumiq_button_color').val();
            const text = $('#lumiq_button_text').val();
            const position = $('#lumiq_button_position').val();
            const size = $('#lumiq_button_size').val();
            
            const $preview = $('#lumiq-preview-button');
            
            // Update color
            $preview.css('background-color', color);
            
            // Update position
            $preview.css({
                right: position === 'right' ? '20px' : 'auto',
                left: position === 'left' ? '20px' : 'auto'
            });
            
            // Update size
            let buttonSize = 60;
            if (size === 'small') buttonSize = 50;
            if (size === 'large') buttonSize = 70;
            
            $preview.css({
                width: buttonSize + 'px',
                height: buttonSize + 'px'
            });
        }
        
        // Update preview on change
        $('#lumiq_button_color, #lumiq_button_text, #lumiq_button_position, #lumiq_button_size').on('change input', updatePreview);
        
        // Initialize preview
        updatePreview();
        
        // Form validation
        $('#lumiq-settings-form').on('submit', function() {
            const apiKey = $('#lumiq_api_key').val().trim();
            
            if (!apiKey) {
                alert('Por favor, configure uma API Key válida antes de salvar.');
                return false;
            }
        });
    });

})(jQuery);
