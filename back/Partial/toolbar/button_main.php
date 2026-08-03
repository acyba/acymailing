<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<button class="acym_vcenter align-center acy_button_submit cell medium-6 large-shrink button <?php echo $data['isPrimary'] ? '' : 'button-secondary'; ?>"
    <?php
    foreach ($data['attributes'] as $oneAttribute => $oneValue) {
        if (is_array($oneValue) || is_object($oneValue)) {
            $oneValue = json_encode($oneValue);
        } elseif ($oneValue === true) {
            $oneValue = $oneAttribute;
        }

        echo ' '.acym_escape($oneAttribute).'="'.acym_escape($oneValue).'"';
    }
    ?>
>
    <?php
    if (!empty($data['icon'])) {
        echo '<i class="acymicon-'.acym_escape($data['icon']).'"></i>';
    }

    echo ' '.acym_escapeHtmlWithAllowedTags(
            $data['content'],
            [
                'span' => [
                    'id' => true,
                    'data-default' => true,
                ],
            ]
        );
    ?>
</button>
