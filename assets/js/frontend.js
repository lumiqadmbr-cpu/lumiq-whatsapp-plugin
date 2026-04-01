/**
 * LUMIQ WhatsApp - Frontend JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        const $widget = $('#lumiq-widget');
        const $button = $('#lumiq-widget-button');
        const $modal = $('#lumiq-modal');
        const $form = $('#lumiq-form');
        const $success = $('#lumiq-success');
        const captureType = $widget.data('capture-type');
        
        // Apply custom styles from settings
        applyCustomStyles();
        
        // Button click handler
        $button.on('click', function() {
            // Track click
            trackClick();
            
            if (captureType === 'direct') {
                // Direct redirect to WhatsApp
                redirectToWhatsApp();
            } else {
                // Show modal form
                openModal();
            }
        });
        
        // Close modal
        $('.lumiq-modal-close, .lumiq-modal-overlay').on('click', function() {
            closeModal();
        });
        
        // Prevent closing when clicking inside modal content
        $('.lumiq-modal-content').on('click', function(e) {
            e.stopPropagation();
        });
        
        // ESC key to close
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $modal.is(':visible')) {
                closeModal();
            }
        });
        
        // Form submit
        $form.on('submit', function(e) {
            e.preventDefault();
            submitForm();
        });
        
        // Phone mask
        $('#lumiq-phone').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            
            if (value.length <= 11) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
                value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
                value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
                value = value.replace(/^(\d*)/, '($1');
            }
            
            $(this).val(value);
        });
        
        /**
         * Apply custom styles from WordPress settings
         */
        function applyCustomStyles() {
            const color = lumiqConfig.buttonColor;
            const position = lumiqConfig.buttonPosition;
            const size = lumiqConfig.buttonSize;
            
            // Apply color
            $button.css('--lumiq-color', color);
            
            // Apply position
            $widget.attr('data-position', position);
            
            // Apply size
            $button.attr('data-size', size);
        }
        
        /**
         * Track button click
         */
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
        
        /**
         * Direct redirect to WhatsApp
         */
        function redirectToWhatsApp(leadData = null) {
            const teamId = lumiqConfig.teamId;
            
            // For direct mode, we need to get WhatsApp number from API
            $.ajax({
                url: lumiqConfig.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lumiq_capture_lead',
                    nonce: lumiqConfig.nonce,
                    name: leadData ? leadData.name : 'Lead Direto',
                    phone: leadData ? leadData.phone : '0000000000',
                    email: leadData ? leadData.email : '',
                    message: leadData ? leadData.message : '',
                    page_url: window.location.href,
                    team_id: teamId
                },
                success: function(response) {
                    if (response.success && response.data.whatsapp_url) {
                        window.open(response.data.whatsapp_url, '_blank');
                    } else {
                        alert('Erro ao conectar. Tente novamente.');
                    }
                },
                error: function() {
                    alert('Erro de conexão. Verifique sua internet.');
                }
            });
        }
        
        /**
         * Open modal
         */
        function openModal() {
            $modal.fadeIn(300);
            $('body').css('overflow', 'hidden');
            
            // Focus first input
            setTimeout(function() {
                $('#lumiq-name').focus();
            }, 300);
        }
        
        /**
         * Close modal
         */
        function closeModal() {
            $modal.fadeOut(300);
            $('body').css('overflow', '');
            
            // Reset form after animation
            setTimeout(function() {
                resetForm();
            }, 300);
        }
        
        /**
         * Submit form
         */
        function submitForm() {
            const $submitBtn = $('.lumiq-form-submit');
            const $submitText = $('.lumiq-submit-text');
            const $submitLoading = $('.lumiq-submit-loading');
            
            // Get form data
            const leadData = {
                name: $('#lumiq-name').val().trim(),
                phone: $('#lumiq-phone').val().replace(/\D/g, ''),
                email: $('#lumiq-email').val().trim(),
                message: $('#lumiq-message').val().trim(),
                page_url: window.location.href,
                team_id: lumiqConfig.teamId
            };
            
            // Validate
            if (!leadData.name || !leadData.phone) {
                alert('Por favor, preencha nome e telefone.');
                return;
            }
            
            if (leadData.phone.length < 10) {
                alert('Digite um telefone válido.');
                return;
            }
            
            // Show loading
            $submitBtn.prop('disabled', true);
            $submitText.hide();
            $submitLoading.show();
            
            // Submit to API
            $.ajax({
                url: lumiqConfig.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lumiq_capture_lead',
                    nonce: lumiqConfig.nonce,
                    ...leadData
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $form.hide();
                        $success.fadeIn();
                        
                        // Redirect to WhatsApp after 2 seconds
                        setTimeout(function() {
                            if (response.data.whatsapp_url) {
                                window.open(response.data.whatsapp_url, '_blank');
                            }
                            
                            // Close modal after redirect
                            setTimeout(function() {
                                closeModal();
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
                    alert('Erro de conexão. Verifique sua internet.');
                    $submitBtn.prop('disabled', false);
                    $submitText.show();
                    $submitLoading.hide();
                }
            });
        }
        
        /**
         * Reset form
         */
        function resetForm() {
            $form[0].reset();
            $form.show();
            $success.hide();
            
            $('.lumiq-form-submit').prop('disabled', false);
            $('.lumiq-submit-text').show();
            $('.lumiq-submit-loading').hide();
        }
        
        /**
         * Entrance animation
         */
        setTimeout(function() {
            $widget.css({
                opacity: 0,
                transform: 'translateY(100px)'
            }).animate({
                opacity: 1
            }, 600, function() {
                $(this).css('transform', 'translateY(0)');
            });
        }, 1000);
    });

})(jQuery);
