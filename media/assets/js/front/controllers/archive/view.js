jQuery(function ($) {
    function Init() {
        replacePreview();
    }

    Init();

    function replacePreview() {
        // We show a temporary preview in case the javascript fails. Replace it with an iframe to isolate it from the template's CSS
        const emailContent = jQuery('#archive_view__content').val();
        const $safetyPreview = jQuery('#archive_view__preview');
        let contentHeight = $safetyPreview.height();

        // On the archive listing in popup mode, the page is hidden so the height returned is 0 instead of the correct one
        if (contentHeight === 0) {
            contentHeight = 900;
        }

        $safetyPreview.replaceWith('<iframe id="email_preview_iframe"></iframe>');
        const $isolatedPreview = jQuery('#email_preview_iframe');

        // For Firefox

        $isolatedPreview.on('load', function (e) {
            setIframeContent($isolatedPreview, emailContent);
        });

        // Set the email content to the new iframe then set its height depending on the content
        setIframeContent($isolatedPreview, emailContent);
        $isolatedPreview.css('height', contentHeight + 'px');
    }

    function setIframeContent($isolatedPreview, emailContent) {
        emailContent = emailContent.replace('</body>', '<style>.hideonline {display: none !important;}</style></body>');
        $isolatedPreview.contents().find('html').html(emailContent);

        $isolatedPreview.contents().find('html').find('a').each(function () {
            // Make sure the links open in a new tab because we place it in an iframe and it doesn't open if there is no target
            jQuery(this).attr('target', '_blank');
        });
    }
});
