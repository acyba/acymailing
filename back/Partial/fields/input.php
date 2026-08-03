<?php
// context verification
?>

<input
    <?php
    echo ' type="'.acym_escape($data['type']).'"';
    echo ' name="'.acym_escape($data['name']).'"';
    if (!empty($data['id'])) {
        echo ' id="'.acym_escape($data['id']).'"';
    }
    if (!empty($data['autocomplete'])) {
        echo ' autocomplete="'.acym_escape($data['autocomplete']).'"';
    }
    if (!empty($data['class'])) {
        echo ' class="'.acym_escape($data['class']).'"';
    }
    if (!empty($data['placeholder'])) {
        echo ' placeholder="'.acym_escape($data['placeholder']).'" aria-label="'.acym_escape($data['placeholder']).'"';
    }
    if (!empty($data['data-required'])) {
        echo ' data-required="'.acym_escape($data['data-required']).'"';
    }
    if (isset($data['value'])) {
        echo ' value="'.acym_escape($data['value']).'"';
    }
    if (isset($data['authorizedContent'])) {
        echo ' data-authorized-content="'.acym_escape($data['authorizedContent']).'"';
    }
    if (isset($data['style'])) {
        echo ' style="'.acym_escape($data['style']).'"';
    }
    if (isset($data['maxCharacters'])) {
        echo ' maxlength="'.acym_escape($data['maxCharacters']).'"';
    }
    if (!empty($data['required'])) {
        echo ' required';
    }
    if (!empty($data['readonly'])) {
        echo ' readonly="readonly"';
    }
    acym_disabled($data['disabled'] ?? false);
    ?>
>
