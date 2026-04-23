jQuery(document).ready(function($) {
    
    // Abrir modal
    $('#lumiq-widget-button').on('click', function(e) {
        e.preventDefault();
        
        var captureType = lumiqConfig.captureType;
        
        if (captureType === 'direct') {
            // Redirecionar direto para WhatsApp
            trackClick();
            window.open('https://wa.me/' + lumiqConfig.teamId, '_blank');
        } else {
            // Abrir modal
            $('#lumiq-modal').fadeIn(300);
            $('body').css('overflow', 'hidden');
        }
    });
    
    // Fechar modal
    $('.lumiq-modal-close, .lumiq-modal-overlay').on('click', function() {
        $('#lumiq-modal').fadeOut(300);
        $('body').css('overflow', '');
    });
    
    // Enviar formulário
    $('#lumiq-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $('.lumiq-form-submit');
        var $submitText = $('.lumiq-submit-text');
        var $submitLoading = $('.lumiq-submit-loading');
        
        // Desabilitar botão
        $submitBtn.prop('disabled', true);
        $submitText.hide();
        $submitLoading.show();
        
        // Coletar dados
        var formData = {
            action: 'lumiq_capture_lead',
            nonce: lumiqConfig.nonce,
            name: $('#lumiq-name').val(),
            phone: $('#lumiq-phone').val(),
            email: $('#lumiq-email').val(),
            message: $('#lumiq-message').val(),
            page_url: window.location.href
        };
        
        // Enviar via AJAX
        $.ajax({
            url: lumiqConfig.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Mostrar mensagem de sucesso
                    $form.hide();
                    $('#lumiq-success').show();
                    
                    // Redirecionar para WhatsApp após 2 segundos
                    setTimeout(function() {
                        if (response.data.whatsapp_url) {
                            window.open(response.data.whatsapp_url, '_blank');
                        }
                        
                        // Fechar modal após 3 segundos
                        setTimeout(function() {
                            $('#lumiq-modal').fadeOut(300);
                            $('body').css('overflow', '');
                            
                            // Reset formulário
                            $form[0].reset();
                            $form.show();
                            $('#lumiq-success').hide();
                        }, 1000);
                    }, 2000);
                } else {
                    alert(response.data.message || 'Erro ao enviar. Tente novamente.');
                    $submitBtn.prop('disabled', false);
                    $submitText.show();
                    $submitLoading.hide();
                }
            },
            error: function() {
                alert('Erro de conexão. Tente novamente.');
                $submitBtn.prop('disabled', false);
                $submitText.show();
                $submitLoading.hide();
            }
        });
    });
    
    // Máscara de telefone
    $('#lumiq-phone').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        
        if (value.length <= 10) {
            value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
        } else {
            value = value.replace(/^(\d{2})(\d{5})(\d{0,4}).*/, '($1) $2-$3');
        }
        
        $(this).val(value);
    });
    
    // Rastrear clique
    function trackClick() {
        $.ajax({
            url: lumiqConfig.ajaxurl,
            type: 'POST',
            data: {
                action: 'lumiq_track_click',
                nonce: lumiqConfig.nonce
            }
        });
    }
});
