jQuery(document).ready(function ($) {
    $('a').on('click', function (e) {
        var linkUrl = $(this).attr('href');
        $.ajax({
            url: linkCounter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'update_link_visit_count',
                link_url: linkUrl,
                nonce: linkCounter.nonce
            }
        });
    });
});