jQuery(function($) {
    function Init() {
        replacePreview();
    }

    Init();

    function replacePreview() {
        // We show a temporary preview in case the javascript fails. Replace it with an iframe to isolate it from the template's CSS
        let emailContent = jQuery('#archive_view__content').val();
        let safetyPreview = jQuery('#archive_view__preview');
        let contentHeight = safetyPreview.height();

        // On the archive listing in popup mode, the page is hidden so the height returned is 0 instead of the correct one
        if (contentHeight === 0) {
            contentHeight = 900;
        }

        safetyPreview.replaceWith('<iframe id="email_preview_iframe"></iframe>');
        let isolatedPreview = jQuery('#email_preview_iframe');

        // For Firefox

        isolatedPreview.on('load', function (e) {
            isolatedPreview.contents().find('html').html(emailContent);
        });

        // Set the email content to the new iframe then set its height depending on the content
        isolatedPreview.contents().find('html').html(emailContent);
        isolatedPreview.css('height', contentHeight + 'px');
    }
});
