jQuery(document).on( 'click', '.sagepaydirect-ssl-nag .notice-dismiss', function() {

    jQuery.ajax({
        url: ajaxurl,
        data: {
            action: 'dismiss_sagepaydirect_ssl_nag'
        }
    })

})

jQuery(document).on( 'click', '.sagepaydirect-cctype-nag .notice-dismiss', function() {

    jQuery.ajax({
        url: ajaxurl,
        data: {
            action: 'dismiss_sagepaydirect_cctype_nag'
        }
    });

})