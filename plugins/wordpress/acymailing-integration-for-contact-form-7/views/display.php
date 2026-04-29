<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<span class="wpcf7-form-control-wrap <?php echo sanitize_html_class($data['identifier']); ?>">
	<span class="<?php echo esc_attr($data['class']); ?>">
		<?php
        if (!empty($data['listsToDisplay'])) {
            foreach ($data['listsToDisplay'] as $listId) {
                if (empty($data['listNames'][$listId])) {
                    continue;
                }

                $idInput = 'acylist_'.$listId.'_field_'.$data['identifier'];
                ?>

				<span class="onelist wpcf7-list-item">
				<input type="checkbox"
					   class="acym_checkbox"
					   name="<?php echo esc_attr($data['identifier']); ?>[]"
					   id="<?php echo esc_attr($idInput); ?>"
					   <?php checked(in_array($listId, $data['listsToCheckByDefault'])); ?>
					   value="<?php echo esc_attr($listId); ?>" />
				<label for="<?php echo esc_attr($idInput); ?>">
					<?php echo esc_html($data['listNames'][$listId]); ?>
				</label>
			</span>
                <?php
            }
        }
        ?>

		<input type="hidden"
			   name="acymhiddenlists_<?php echo esc_attr($data['identifier']); ?>"
			   data-acymfield="<?php echo esc_attr($data['identifier']); ?>"
			   data-acymmail="<?php echo esc_attr($data['emailField']); ?>"
			   data-acymname="<?php echo esc_attr($data['nameField']); ?>"
			   data-acymcf="<?php echo esc_attr(wp_json_encode($data['customFields'])); ?>"
			   value="<?php echo esc_attr(implode(',', $data['listsSubbedOnSubmit'])); ?>" />
		<input type="hidden" name="acymaction_<?php echo esc_attr($data['identifier']); ?>" value="<?php echo esc_url($data['submitUrl']); ?>" />
	</span>
	<?php
    echo wp_kses(
        $data['validationError'],
        [
            'span' => [
                'class' => [],
                'aria-hidden' => [],
            ],
        ]
    );
    ?>
</span>
