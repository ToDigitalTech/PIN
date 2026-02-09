/**
 * PIN Platform - Public JavaScript
 */
(function ($) {
    'use strict';

    var PIN = {
        /**
         * Initialize all event handlers.
         */
        init: function () {
            this.initTabs();
            this.initAddWorker();
            this.initCSVUpload();
            this.initRemoveWorker();
            this.initPayrollPreview();
            this.initPayrollProcess();
            this.initBankForms();
        },

        /**
         * Tab switching.
         */
        initTabs: function () {
            $(document).on('click', '.pin-tab', function () {
                var tabId = $(this).data('tab');
                $(this).siblings('.pin-tab').removeClass('active');
                $(this).addClass('active');
                $(this).closest('.pin-dashboard, .pin-transparency').find('.pin-tab-content').removeClass('active');
                $('#tab-' + tabId).addClass('active');
            });
        },

        /**
         * Add Worker form toggle and submission.
         */
        initAddWorker: function () {
            $('#pin-show-add-worker').on('click', function () {
                $('#pin-add-worker-form').slideToggle(200);
            });

            $('#pin-cancel-add-worker').on('click', function () {
                $('#pin-add-worker-form').slideUp(200);
            });

            $('#pin-worker-form-ajax').on('submit', function (e) {
                e.preventDefault();
                var $form = $(this);
                var $result = $('#pin-add-worker-result');
                var $btn = $form.find('button[type="submit"]');

                $btn.prop('disabled', true).text('Adding...');

                var data = {
                    action: 'pin_add_worker',
                    nonce: pinAjax.nonce,
                    first_name: $form.find('[name="first_name"]').val(),
                    last_name: $form.find('[name="last_name"]').val(),
                    email: $form.find('[name="email"]').val(),
                    salary: $form.find('[name="salary"]').val(),
                    bank_name: $form.find('[name="bank_name"]').val(),
                    bank_account: $form.find('[name="bank_account"]').val(),
                    account_name: $form.find('[name="account_name"]').val()
                };

                $.post(pinAjax.ajaxUrl, data, function (response) {
                    if (response.success) {
                        $result.removeClass('error').addClass('success').text(response.data.message);
                        $form[0].reset();
                        // Reload page after short delay to show updated worker list.
                        setTimeout(function () {
                            location.reload();
                        }, 1500);
                    } else {
                        $result.removeClass('success').addClass('error').text(response.data.message);
                    }
                }).fail(function () {
                    $result.removeClass('success').addClass('error').text('Network error. Please try again.');
                }).always(function () {
                    $btn.prop('disabled', false).text('Add Worker');
                });
            });
        },

        /**
         * CSV bulk upload.
         */
        initCSVUpload: function () {
            $('#pin-csv-upload-form').on('submit', function (e) {
                e.preventDefault();
                var $form = $(this);
                var $result = $('#pin-csv-result');
                var $btn = $form.find('button[type="submit"]');

                var formData = new FormData();
                formData.append('action', 'pin_bulk_upload_workers');
                formData.append('nonce', pinAjax.nonce);
                formData.append('csv_file', $form.find('[name="csv_file"]')[0].files[0]);

                $btn.prop('disabled', true).text('Uploading...');

                $.ajax({
                    url: pinAjax.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.success) {
                            var msg = response.data.message;
                            if (response.data.errors && response.data.errors.length > 0) {
                                msg += ' Errors: ' + response.data.errors.join('; ');
                            }
                            $result.removeClass('error').addClass('success').text(msg);
                            setTimeout(function () {
                                location.reload();
                            }, 2000);
                        } else {
                            $result.removeClass('success').addClass('error').text(response.data.message);
                        }
                    },
                    error: function () {
                        $result.removeClass('success').addClass('error').text('Network error. Please try again.');
                    },
                    complete: function () {
                        $btn.prop('disabled', false).text('Upload CSV');
                    }
                });
            });
        },

        /**
         * Remove worker.
         */
        initRemoveWorker: function () {
            $(document).on('click', '.pin-remove-worker', function () {
                if (!confirm('Remove this worker from your payroll?')) {
                    return;
                }
                var $btn = $(this);
                var workerId = $btn.data('worker-id');

                $btn.prop('disabled', true).text('Removing...');

                $.post(pinAjax.ajaxUrl, {
                    action: 'pin_remove_worker',
                    nonce: pinAjax.nonce,
                    worker_id: workerId
                }, function (response) {
                    if (response.success) {
                        $btn.closest('tr').fadeOut(300, function () {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message);
                        $btn.prop('disabled', false).text('Remove');
                    }
                }).fail(function () {
                    alert('Network error. Please try again.');
                    $btn.prop('disabled', false).text('Remove');
                });
            });
        },

        /**
         * Payroll preview loading.
         */
        initPayrollPreview: function () {
            $('#pin-load-preview').on('click', function () {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Loading...');

                $.post(pinAjax.ajaxUrl, {
                    action: 'pin_get_payroll_preview',
                    nonce: pinAjax.nonce
                }, function (response) {
                    if (response.success) {
                        var d = response.data;
                        $('#preview-workers').text(d.workers.length);
                        $('#preview-gross').text(PIN.formatCurrency(d.total_gross));
                        $('#preview-tax').text(PIN.formatCurrency(d.total_tax));
                        $('#preview-net').text(PIN.formatCurrency(d.total_net));
                        $('#preview-total').text(PIN.formatCurrency(d.total_gross));
                        $('#pin-process-payroll-btn').prop('disabled', false);
                    } else {
                        alert(response.data.message);
                    }
                }).fail(function () {
                    alert('Network error. Please try again.');
                }).always(function () {
                    $btn.prop('disabled', false).text('Load Preview');
                });
            });
        },

        /**
         * Process payroll.
         */
        initPayrollProcess: function () {
            $('#pin-payroll-form').on('submit', function (e) {
                e.preventDefault();
                var $result = $('#pin-payroll-result');
                var $btn = $('#pin-process-payroll-btn');
                var payPeriod = $('#pay_period').val();

                if (!payPeriod) {
                    $result.removeClass('success').addClass('error').text('Please select a pay period.');
                    return;
                }

                if (!confirm('Process payroll for ' + payPeriod + '? This will create a WooCommerce order for payment.')) {
                    return;
                }

                $btn.prop('disabled', true).text('Processing...');

                $.post(pinAjax.ajaxUrl, {
                    action: 'pin_process_payroll',
                    nonce: pinAjax.nonce,
                    pay_period: payPeriod
                }, function (response) {
                    if (response.success) {
                        $result.removeClass('error').addClass('success').html(
                            response.data.message +
                            ' <a href="' + response.data.checkout_url + '" class="pin-btn pin-btn-primary pin-btn-sm">Complete Payment</a>'
                        );
                    } else {
                        $result.removeClass('success').addClass('error').text(response.data.message);
                        $btn.prop('disabled', false).text('Process Payroll & Pay');
                    }
                }).fail(function () {
                    $result.removeClass('success').addClass('error').text('Network error. Please try again.');
                    $btn.prop('disabled', false).text('Process Payroll & Pay');
                });
            });
        },

        /**
         * Bank detail update forms (worker + officer).
         */
        initBankForms: function () {
            // Worker bank form.
            $('#pin-worker-bank-form').on('submit', function (e) {
                e.preventDefault();
                PIN.submitBankForm($(this), 'pin_worker_update_bank', '#pin-worker-bank-result');
            });

            // Officer bank form.
            $('#pin-officer-bank-form').on('submit', function (e) {
                e.preventDefault();
                PIN.submitBankForm($(this), 'pin_officer_update_bank', '#pin-officer-bank-result');
            });
        },

        /**
         * Submit bank form helper.
         */
        submitBankForm: function ($form, action, resultSelector) {
            var $result = $(resultSelector);
            var $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true).text('Saving...');

            $.post(pinAjax.ajaxUrl, {
                action: action,
                nonce: pinAjax.nonce,
                bank_name: $form.find('[name="bank_name"]').val(),
                bank_account: $form.find('[name="bank_account"]').val(),
                account_name: $form.find('[name="account_name"]').val()
            }, function (response) {
                if (response.success) {
                    $result.removeClass('error').addClass('success').text(response.data.message);
                } else {
                    $result.removeClass('success').addClass('error').text(response.data.message);
                }
            }).fail(function () {
                $result.removeClass('success').addClass('error').text('Network error. Please try again.');
            }).always(function () {
                $btn.prop('disabled', false).text('Update Bank Details');
            });
        },

        /**
         * Format number as Nigerian Naira.
         */
        formatCurrency: function (amount) {
            return '₦' + parseFloat(amount).toLocaleString('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    };

    // Initialize on document ready.
    $(document).ready(function () {
        PIN.init();
    });

})(jQuery);
