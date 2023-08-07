$(document).ready(function () {
    let showError = function (message) {
        $('.testimonial-error-message').html(message).show()
    }

    let showSuccess = function (message) {
        $('.testimonial-success-message').html(message).show()
    }

    let handleError = function (data) {
        if (typeof data.errors !== 'undefined' && data.errors.length) {
            handleValidationError(data.errors)
        } else {
            if (typeof data.responseJSON !== 'undefined') {
                if (typeof data.responseJSON.errors !== 'undefined') {
                    if (data.status === 422) {
                        handleValidationError(data.responseJSON.errors)
                    }
                } else if (typeof data.responseJSON.message !== 'undefined') {
                    showError(data.responseJSON.message)
                } else {
                    $.each(data.responseJSON, (index, el) => {
                        $.each(el, (key, item) => {
                            showError(item)
                        })
                    })
                }
            } else {
                showError(data.statusText)
            }
        }
    }

    let handleValidationError = function (errors) {
        let message = ''
        $.each(errors, (index, item) => {
            if (message !== '') {
                message += '<br />'
            }
            message += item
        })
        showError(message)
    }

    $(document).on('click', '.testimonial-form button[type=submit]', function (event) {
        event.preventDefault()
        event.stopPropagation()

        $(this).addClass('button-loading')
        $('.testimonial-success-message').html('').hide()
        $('.testimonial-error-message').html('').hide()

        $.ajax({
            type: 'POST',
            cache: false,
            url: $(this).closest('form').prop('action'),
            data: new FormData($(this).closest('form')[0]),
            contentType: false,
            processData: false,
            success: (res) => {
                if (!res.error) {
                    $(this).closest('form').find('input[type=text]').val('')
                    $(this).closest('form').find('input[type=email]').val('')
                    $(this).closest('form').find('textarea').val('')
                    showSuccess(res.message)
                } else {
                    showError(res.message)
                }

                $(this).removeClass('button-loading')

                if (typeof refreshRecaptcha !== 'undefined') {
                    refreshRecaptcha()
                }
            },
            error: (res) => {
                if (typeof refreshRecaptcha !== 'undefined') {
                    refreshRecaptcha()
                }
                $(this).removeClass('button-loading')
                handleError(res)
            },
        })
    })
})
