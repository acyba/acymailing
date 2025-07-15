<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<li class="acym_<?php echo esc_attr($data['property_name']); ?>_setting field_setting">
	<p class="section_label" style="margin-bottom: 0"><?php echo esc_html($data['label']); ?>:</p>
	<select id="<?php echo esc_attr($data['property_name']); ?>" multiple onchange="saveAcyListSelectMultiple('<?php echo esc_attr($data['property_name']); ?>', this)">
        <?php
        foreach ($data['lists'] as $id => $name) {
            ?>
			<option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($name); ?></option>
            <?php
        }
        ?>
	</select>
</li>
