(function($) {
    "use strict";

    const thumbnail = window.npcinkSiteToolboxThumbnail || {};
    thumbnail.uploadFrame = false;

    $(document).on('click', 'button.ts-ets-remove', function() {
        thumbnail.postId = $(this).data('id');
        thumbnail.parent = $(this).closest('td.ts-ets-option');

        if (!confirm(thumbnail.confirm)) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'npcink_site_toolbox_thumbnail_remove',
                nonce: $('#npcink_site_toolbox_thumbnail_nonce').val(),
                post_id: thumbnail.postId
            },
            success: function(data) {
                if (data !== '') {
                    thumbnail.parent.html(data);
                }
            }
        });
    });

    $(document).ready(function() {
        thumbnail.uploadFrame = wp.media({
            title: thumbnail.upload_title,
            button: {
                text: thumbnail.upload_add
            },
            multiple: false
        });

        thumbnail.uploadFrame.on('select', function() {
            thumbnail.selection = thumbnail.uploadFrame.state().get('selection');

            thumbnail.selection.map(function(attachment) {
                if (attachment.id) {
                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'npcink_site_toolbox_thumbnail_update',
                            nonce: $('#npcink_site_toolbox_thumbnail_nonce').val(),
                            post_id: thumbnail.postId,
                            thumb_id: attachment.id
                        },
                        success: function(data) {
                            if (data !== '') {
                                thumbnail.parent.html(data);
                            }
                        }
                    });
                }
            });
        });
    });

    $(document).on('click', 'button.ts-ets-add', function(event) {
        event.preventDefault();

        thumbnail.postId = $(this).data('id');
        thumbnail.parent = $(this).closest('td.ts-ets-option');

        if (thumbnail.uploadFrame) {
            thumbnail.uploadFrame.open();
        }
    });
})(jQuery);
