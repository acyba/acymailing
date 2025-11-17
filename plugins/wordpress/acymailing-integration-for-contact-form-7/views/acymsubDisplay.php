<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<span class="wpcf7-form-control-wrap <?php echo sanitize_html_class($data['tagName']); ?>">
	<span class="<?php echo esc_attr($data['class']); ?>">
		<?php
        foreach ($data['detailsLists']['displayLists'] as $listId) {
            $check = false;
            if (in_array($listId, $data['detailsLists']['defaultLists'])) {
                $check = true;
            }
            $idInput = 'acylist_'.$listId.'_field_'.$data['tagName'];
            ?>

			<span class="onelist wpcf7-list-item">
				<input type="checkbox"
					   class="acym_checkbox"
					   name="<?php echo esc_attr($data['tagName']); ?>[]"
					   id="<?php echo esc_attr($idInput); ?>" <?php checked($check); ?> value="<?php echo esc_attr($listId); ?>" />
				<label for="<?php echo esc_attr($idInput); ?>"><?php echo esc_html($data['listNames'][$listId]); ?></label>
			</span>
            <?php
        }

        $acymmail = '';
        $acymname = '';
        foreach ($data['tag']->options as $oneOption) {
            if (strpos($oneOption, 'acymmail:') !== false) {
                $optionExploded = explode(':', $oneOption);
                $acymmail .= $optionExploded[1];
            }
            if (strpos($oneOption, 'acymname:') !== false) {
                $optionExploded = explode(':', $oneOption);
                $acymname .= $optionExploded[1];
            }
        }
        ?>

		<input type="hidden"
			   name="acymhiddenlists_<?php echo esc_attr($data['tagName']); ?>"
			   data-acymfield="<?php echo esc_attr($data['tagName']); ?>"
			   data-acymmail="<?php echo esc_attr($acymmail); ?>"
			   data-acymname="<?php echo esc_attr($acymname); ?>"
			   value="<?php echo esc_attr(implode(',', $data['detailsLists']['autoLists'])); ?>" />
		<input type="hidden" name="acymaction_<?php echo esc_attr($data['tagName']); ?>" value="<?php echo esc_url($data['acymSubmitUrl']); ?>" />
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
