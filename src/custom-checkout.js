jQuery(document).ready(function ($) {

    // Trigger AJAX on form validation/update.
    $(document).on('click','a.show-form', function (e) {
        var dataId = $(this).data('id');
        var order = $(this).data('order');
        var targetRow = $('tr[data-id="' + dataId + '"][data-order="' + order + '"]');
        targetRow.toggle();

    });
    $(document).on('click','.modify-item-info', function (e) {
        e.preventDefault();
        var isValid = true;
        var targetRow = $(this).closest('tr');
        var formData = targetRow.find('input, select, textarea').serialize();
        // Example validation: Check if required fields are filled
        targetRow.find('input, select, textarea').each(function () {
            if (!$(this).val()) {
                isValid = false;
                $(this).css('border-color', 'red'); // Highlight the invalid field
                $(this).closest('div').css('color', 'red'); // Highlight the invalid field

            } else {
                $(this).css('border-color', ''); // Reset the field style
                $(this).closest('div').css('color', 'inherit'); // Highlight the invalid field
            }
        });

        if(isValid === false) {
            return false;
        }

        // Perform AJAX request.
        $.ajax({
            url: custom_ajax_object.ajax_url,
            method: 'POST',
            data: {
                action: 'update_info',
                security: custom_ajax_object.nonce,
                form_data: formData,
            },
            success: function (response) {
                if (response.success) {
                    location.reload(); // Refresh the page
                } else if (response.data.errors) {
                    // Display validation errors.
                    $.each(response.data.errors, function (field, error) {
                        $(`#${field}_error`).text(error).show();
                    });
                }
            },
            error: function () {
                console.log('AJAX request failed.');
            },
        });
    });

    $(document).on('click','#update-billing-button', function (e) {
        e.preventDefault();
        var form = $('#checkout-form');
        isValid = true;
        // Collect all form data.
        const formData = form.serialize();

        // Example validation: Check if required fields are filled
        form.find('input, select, textarea').each(function () {
            if ($(this).attr('aria-required') && !$(this).val()) {
                isValid = false;
                $(this).css('border-color', 'red'); // Highlight the invalid field
                $(this).closest('div').css('color', 'red'); // Highlight the invalid field
            } else {
                $(this).css('border-color', ''); // Reset the field style
                $(this).closest('div').css('color', 'inherit'); // Highlight the invalid field
            }
        });

        if(isValid === false) {
            return;
        }
        // Perform AJAX request.
        $.ajax({
            url: custom_ajax_object.ajax_url,
            method: 'POST',
            data: {
                action: 'update_checkout',
                security: custom_ajax_object.nonce,
                form_data: formData,
            },
            success: function (response) {
                if (response.success) {
                    location.reload(); // Refresh the page
                } else if (response.data.errors) {
                    // Display validation errors.
                    $.each(response.data.errors, function (field, error) {
                        $(`#${field}_error`).text(error).show();
                    });
                }
            },
            error: function () {
                console.log('AJAX request failed.');
            },
        });
    });
});
