$(document).ready(function() {
    // When modal shows, focus OTP and initialize digit inputs if present
    $('#recoverModal').on('shown.bs.modal', function () {
        if ($('.otp-input').length) {
            setupOtpDigits();
            $('.otp-input').first().focus();
        } else {
            $('#otp_code').focus();
        }
    });

    // Helper: update hidden combined otp value from digit inputs
    function updateHiddenOtp() {
        if ($('#otp_code').length && $('.otp-input').length) {
            var val = '';
            $('.otp-input').each(function() { val += ($(this).val() || ''); });
            $('#otp_code').val(val);
            return val;
        }
        return $('#otp_code').val() || '';
    }

    // Debug panel rendering removed (resend feature disabled)

    // Setup behavior for six single-digit OTP inputs (auto-advance, paste, backspace)
    function setupOtpDigits() {
        var $inputs = $('.otp-input');
        if (!$inputs.length) return;

        $inputs.each(function(index, el) {
            var $el = $(el);
            $el.attr('maxlength', 1);
            $el.attr('inputmode', 'numeric');
            $el.attr('pattern', '[0-9]*');
            $el.val('');

            $el.off('input.otp keydown.otp paste.otp focus.otp');

            $el.on('input.otp', function(e) {
                var v = this.value.replace(/\D/g, '');
                this.value = v.slice(-1);
                updateHiddenOtp();
                if (this.value !== '') {
                    // move to next
                    var next = $inputs.eq(index + 1);
                    if (next.length) next.focus();
                }
            });

            $el.on('keydown.otp', function(e) {
                var key = e.key;
                if (key === 'Backspace') {
                    if (this.value === '') {
                        var prev = $inputs.eq(index - 1);
                        if (prev.length) {
                            prev.val('');
                            prev.focus();
                            updateHiddenOtp();
                            e.preventDefault();
                        }
                    } else {
                        // allow normal backspace to clear current
                        this.value = '';
                        updateHiddenOtp();
                        e.preventDefault();
                    }
                } else if (key === 'ArrowLeft') {
                    var prev = $inputs.eq(index - 1);
                    if (prev.length) prev.focus();
                    e.preventDefault();
                } else if (key === 'ArrowRight') {
                    var next = $inputs.eq(index + 1);
                    if (next.length) next.focus();
                    e.preventDefault();
                }
            });

            $el.on('paste.otp', function(e) {
                e.preventDefault();
                var pasted = (e.originalEvent || e).clipboardData.getData('text') || '';
                var digits = pasted.replace(/\D/g, '').split('');
                for (var i = 0; i < digits.length; i++) {
                    var target = $inputs.eq(index + i);
                    if (!target.length) break;
                    target.val(digits[i]);
                }
                // after paste, focus the last filled
                var lastIndex = Math.min(index + digits.length - 1, $inputs.length - 1);
                $inputs.eq(lastIndex).focus();
                updateHiddenOtp();
            });

            $el.on('focus.otp', function() { this.select(); });
        });
    }

    $('#recoverPasswordForm').on('submit', function(e) {
        e.preventDefault();

        // Show loading state
        var submitBtn = $(this).find('button[type="submit"]');
        var originalBtnText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        
        // Show loading overlay
        $('.loading-overlay').css('display', 'flex');
        
        var username = $('#check_username').val();
        var otp = $('#otp_code').val();
        var newPassword = $('#new_password').val();
        var confirmPassword = $('#new_confirm_password').val();

        // Basic validation
        if (!otp || otp.length !== 6) {
            alert('Please enter a valid 6-digit OTP');
            return;
        }

        if (!newPassword || !confirmPassword) {
            alert('Please enter both password fields');
            return;
        }

        if (newPassword !== confirmPassword) {
            alert('Passwords do not match!');
            return;
        }

        // Submit to validateOTP.php with improved error handling
        $.ajax({
            url: 'validateOTP.php',
            type: 'POST',
            data: {
                username: username,
                otp: otp,
                new_password: newPassword
            },
            dataType: 'json',
            success: function(response) {
                var submitBtn = $('#recoverPasswordForm').find('button[type="submit"]');
                // Hide loading overlay
                $('.loading-overlay').hide();
                
                if (response.success) {
                    submitBtn.html('<i class="fas fa-check"></i> Success!');
                    setTimeout(function() {
                        // Password update completed — close modal and redirect to login silently.
                        $('#recoverModal').modal('hide');
                        window.location.href = 'login.php';
                    }, 1000);
                } else {
                    submitBtn.html('<i class="fas fa-save"></i> SAVE').prop('disabled', false);
                    alert(response.message || 'Invalid OTP or error updating password');
                    // Clear OTP digit inputs and hidden field on error
                    $('.otp-input').val('');
                    $('#otp_code').val('');
                    var $first = $('.otp-input').first();
                    if ($first.length) $first.focus(); else $('#otp_code').focus();
                }
            },
            error: function(xhr) {
                var submitBtn = $('#recoverPasswordForm').find('button[type="submit"]');
                // Hide loading overlay
                $('.loading-overlay').hide();
                submitBtn.html('<i class="fas fa-save"></i> SAVE').prop('disabled', false);
                var response = xhr.responseJSON;
                alert(response ? response.message : 'An error occurred');
                // Clear OTP digit inputs and hidden field on error
                $('.otp-input').val('');
                $('#otp_code').val('');
                var $first = $('.otp-input').first();
                if ($first.length) $first.focus(); else $('#otp_code').focus();
            }
        });
    });

    // Toggle password visibility
    $("#show_hide_password a, #show_hide_password_confirm a").on('click', function(e) {
        e.preventDefault();
        var input = $(this).closest('.input-group').find('input');
        var icon = $(this).find('i');

        if(input.attr("type") == "text"){
            input.attr('type', 'password');
            icon.addClass("fa-eye-slash").removeClass("fa-eye");
        } else {
            input.attr('type', 'text');
            icon.removeClass("fa-eye-slash").addClass("fa-eye");
        }
    });

    // Resend OTP timer and debug code removed (feature disabled)

    // Resend feature removed; no click handler attached
});