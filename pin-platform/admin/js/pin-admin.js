/**
 * PIN Platform - Admin JavaScript
 */
(function ($) {
    'use strict';

    $(document).ready(function () {

        // Trigger distribution.
        $('#pin-trigger-distribution').on('click', function () {
            var $btn = $(this);
            var $result = $('#pin-distribution-result');

            if (!confirm('Trigger distribution of the tax pool to all registered officers?')) {
                return;
            }

            $btn.prop('disabled', true).text('Processing...');

            $.post(pinAdmin.ajaxUrl, {
                action: 'pin_trigger_distribution',
                nonce: pinAdmin.nonce
            }, function (response) {
                if (response.success) {
                    var d = response.data;
                    $result.css('color', '#065f46').text(
                        'Distributed ₦' + parseFloat(d.total_distributed).toLocaleString() +
                        ' to ' + d.officer_count + ' officers (₦' +
                        parseFloat(d.per_officer).toLocaleString() + ' each).'
                    );
                    setTimeout(function () {
                        location.reload();
                    }, 2000);
                } else {
                    $result.css('color', '#991b1b').text(response.data.message);
                }
            }).fail(function () {
                $result.css('color', '#991b1b').text('Network error. Please try again.');
            }).always(function () {
                $btn.prop('disabled', false).text('Trigger Officer Distribution');
            });
        });

        // Verify officer.
        $('.pin-verify-officer').on('click', function () {
            var $btn = $(this);
            var officerId = $btn.data('officer-id');

            $btn.prop('disabled', true).text('Verifying...');

            $.post(pinAdmin.ajaxUrl, {
                action: 'pin_admin_verify_officer',
                nonce: pinAdmin.nonce,
                officer_id: officerId
            }, function (response) {
                if (response.success) {
                    $btn.replaceWith('<span class="dashicons dashicons-yes-alt" style="color: #2C5F2D;"></span>');
                    $btn.closest('tr').find('.pin-status').removeClass('pin-status-pending').addClass('pin-status-verified').text('Verified');
                } else {
                    alert(response.data.message);
                    $btn.prop('disabled', false).text('Verify');
                }
            }).fail(function () {
                alert('Network error. Please try again.');
                $btn.prop('disabled', false).text('Verify');
            });
        });

        // Settings form.
        $('#pin-settings-form').on('submit', function (e) {
            e.preventDefault();
            var $result = $('#pin-settings-result');
            var $btn = $(this).find('button[type="submit"]');

            $btn.prop('disabled', true).text('Saving...');

            $.post(pinAdmin.ajaxUrl, {
                action: 'pin_admin_update_settings',
                nonce: pinAdmin.nonce,
                tax_rate: $('#pin_tax_rate').val(),
                force_ngn: $('#pin_force_ngn').is(':checked') ? 1 : 0,
                min_distribution: $('#pin_min_distribution').val(),
                auto_distribute: $('#pin_auto_distribute').is(':checked') ? 1 : 0,
                paystack_key: $('#pin_paystack_key').val()
            }, function (response) {
                if (response.success) {
                    $result.css('color', '#065f46').text(response.data.message);
                } else {
                    $result.css('color', '#991b1b').text(response.data.message);
                }
            }).fail(function () {
                $result.css('color', '#991b1b').text('Network error. Please try again.');
            }).always(function () {
                $btn.prop('disabled', false).text('Save Settings');
            });
        });
    });

})(jQuery);
