<script type="text/javascript">
    if (acymCookies === undefined) {
        var acymCookies = document.cookie.split('; ');
    }
    if (acymCookies !== undefined && acymCookies.length > 0) {
        for (let i = 0 ; i < acymCookies.length ; i++) {
            if (acymCookies[i].indexOf('acym_form_<?php echo $form->id; ?>=') !== -1) {
                document.getElementById('acym_fulldiv_<?php echo $form->form_tag_name; ?>').remove();
            }
        }
    }
</script>
