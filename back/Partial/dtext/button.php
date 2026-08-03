<?php
// context verification
?>
<button type="button"
        id="acym__dtext__button"
        class="<?php echo acym_escape($data['class']); ?>"
        data-acym-editor="<?php echo acym_escape($data['editor']); ?>"
        data-acym-selection="<?php echo acym_escape($data['selection']); ?>">
    <?php
    if (!empty($data['icon'])) {
        echo '<i class="'.acym_escape($data['icon']).'"></i>';
    }
    ?>
    <?php echo acym_escapeHtml($data['text']); ?>
</button>
