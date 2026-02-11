<script>
$(document).ready(function() {
    // Image preview and remove
    function clearImagePreview() {
        $('#image').val('');
        $('#image_preview').addClass('hidden');
        $('#image_preview_img').attr('src', '');
    }

    $('#image').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#image_preview_img').attr('src', e.target.result);
                $('#image_preview').removeClass('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            clearImagePreview();
        }
    });

    $(document).on('click', '#image_remove_btn', function() {
        clearImagePreview();
    });

    // Prevent negative values in price inputs
    $('#cost_price, #sales_price, #discount, #tax').on('input blur', function() {
        const value = parseFloat($(this).val());
        if (value < 0 || isNaN(value)) {
            $(this).val(0);
        }
    });
    // Notification limit: natural numbers only (no decimals)
    $('#notification_limit').on('input blur', function() {
        let val = $(this).val();
        const num = parseInt(val, 10);
        if (isNaN(num) || num < 0) {
            $(this).val('');
        } else {
            $(this).val(num);
        }
    });

    $('#stock_form').on('submit', function(e) {
        e.preventDefault();

        let $form = $(this);
        let $submitBtn = $form.find('button[type="submit"]');
        
        if ($submitBtn.prop('disabled')) {
            return false;
        }

        let stock_name = $('#stock_name').val().trim();
        let category_id = $('#category_id').val();
        let barcode = $('#barcode').val().trim();
        let cost_price = $('#cost_price').val();
        let sales_price = $('#sales_price').val();
        let discount = $('#discount').val();
        let tax = $('#tax').val();

        if (!stock_name) {
            show_notification('error', '<?= trans("messages.enter_stock_name", [], session("locale")) ?: "Please enter stock name" ?>');
            return;
        }
        if (!category_id) {
            show_notification('error', '<?= trans("messages.enter_category", [], session("locale")) ?: "Please select a category" ?>');
            return;
        }
        if (!barcode) {
            show_notification('error', '<?= trans("messages.enter_barcode", [], session("locale")) ?: "Please enter barcode" ?>');
            return;
        }
        if (!cost_price || parseFloat(cost_price) < 0) {
            show_notification('error', '<?= trans("messages.enter_cost_price", [], session("locale")) ?: "Please enter cost price" ?>');
            return;
        }
        if (!sales_price || parseFloat(sales_price) < 0) {
            show_notification('error', '<?= trans("messages.enter_sales_price", [], session("locale")) ?: "Please enter sales price" ?>');
            return;
        }
        if (discount && parseFloat(discount) < 0) {
            show_notification('error', '<?= trans("messages.discount", [], session("locale")) ?> cannot be negative');
            return;
        }
        if (tax && parseFloat(tax) < 0) {
            show_notification('error', '<?= trans("messages.tax", [], session("locale")) ?> cannot be negative');
            return;
        }

        let originalBtnText = $submitBtn.html();
        $submitBtn.prop('disabled', true).css('opacity', '0.6').html('<?= trans("messages.processing", [], session("locale")) ?>...');

        let formData = new FormData(this);

        $.ajax({
            url: "{{ route('add_stock') }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (response.status === 'success') {
                    show_notification('success', '<?= trans("messages.stock_added_successfully", [], session("locale")) ?: "Stock added successfully!" ?>');
                    $('#stock_form')[0].reset();
                    if (typeof clearImagePreview === 'function') clearImagePreview();
                    if (response.redirect_url) {
                        setTimeout(function() {
                            window.location.href = response.redirect_url;
                        }, 1000);
                    }
                } else {
                    $submitBtn.prop('disabled', false).css('opacity', '1').html(originalBtnText);
                }
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).css('opacity', '1').html(originalBtnText);
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        show_notification('error', value[0]); 
                    });
                } else {
                    show_notification('error', '<?= trans("messages.something_went_wrong", [], session("locale")) ?: "Something went wrong!" ?>');
                }
            }
        });
    });
});
</script>
