<script type="text/javascript">
    var cookies = document.cookie.split('; ');
    if (cookies !== undefined && cookies.length > 0) {
        for (let i = 0 ; i < cookies.length ; i++) {
            if (cookies[i].indexOf('acym_form_<?php echo $form->id;?>=') !== -1) document.getElementById('acym_fulldiv_<?php echo $form->form_tag_name; ?>').remove();
        }
    }
</script>
