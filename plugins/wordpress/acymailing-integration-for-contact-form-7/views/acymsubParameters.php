<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="control-box">
	<fieldset>
		<legend><?php echo esc_html(acym_translation('ACYM_INSERT_CONTACTFORM_TAG')) ?>
			<a href="https://docs.acymailing.com/addons/wordpress-add-ons/contact-form-7" target="_blank">
                <?php echo esc_html(acym_translation('ACYM_SEE_DOCUMENTATION')) ?>
			</a>
		</legend>

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
                        <?php
                        // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                        esc_html_e('Field type', 'contact-form-7');
                        ?>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
                                <?php
                                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                                esc_html_e('Field type', 'contact-form-7');
                                ?>
							</legend>
							<label>
								<input type="checkbox" name="required" /> <?php
                                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                                esc_html_e('Required field', 'contact-form-7');
                                ?>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="<?php echo esc_attr($data['args']['content'].'-name'); ?>">
                            <?php
                            // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                            esc_html_e('Name', 'contact-form-7');
                            ?>
						</label>
					</th>
					<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr($data['args']['content'].'-name'); ?>" /></td>
				</tr>
				<tr>
					<th scope="row">
						<label for="<?php echo esc_attr($data['args']['content'].'-acymmail'); ?>">
                            <?php echo esc_html(acym_translation('ACYM_MAIL_FIELD_CONTACT')); ?>
						</label>
					</th>
					<td>
						<input type="text"
							   name="acymmail"
							   placeholder="your-email"
							   class="classvalue oneline option"
							   id="<?php echo esc_attr($data['args']['content'].'-acymmail'); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="<?php echo esc_attr($data['args']['content'].'-acymname'); ?>">
                            <?php echo esc_html(acym_translation('ACYM_NAME_FIELD_CONTACT')); ?>
						</label>
					</th>
					<td>
						<input type="text"
							   name="acymname"
							   placeholder="your-name"
							   class="classvalue oneline option"
							   id="<?php echo esc_attr($data['args']['content'].'-acymname'); ?>" />
					</td>
				</tr>

                <?php foreach ($data['propertyLabels'] as $key => $label) { ?>
					<tr>
						<th scope="row"><?php echo esc_html($label); ?></th>
						<td>
							<fieldset>
                                <?php
                                echo wp_kses(
                                    acym_selectMultiple(
                                        $data['lists'],
                                        $key,
                                        [],
                                        [],
                                        'id',
                                        'name'
                                    ),
                                    [
                                        'select' => ['name' => [], 'id' => [], 'class' => [], 'multiple' => [], 'onchange' => []],
                                        'option' => ['value' => [], 'selected' => [], 'disabled' => [], 'data-hidden' => []],
                                        'optgroup' => ['label' => []],
                                    ]
                                );
                                ?>
								<input type="hidden" name="<?php echo esc_attr($key); ?>" data-type="<?php echo esc_attr($key); ?>" value="">
							</fieldset>
						</td>
					</tr>
                <?php } ?>

				<tr>
					<th scope="row">
						<label for="<?php echo esc_attr($data['args']['content'].'-class'); ?>">
                            <?php
                            // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                            esc_html_e('Class attribute', 'contact-form-7');
                            ?>
						</label>
					</th>
					<td>
						<input type="text"
							   name="class"
							   class="classvalue oneline option"
							   id="<?php echo esc_attr($data['args']['content'].'-class'); ?>" />
					</td>
				</tr>
			</tbody>
		</table>
		<input type="hidden" name="values" value="">
	</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="acymsub" class="tag code" readonly="readonly" onfocus="this.select()" />
	<div class="submitbox">
		<input type="button" class="button button-primary insert-tag" value="<?php
        // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
        esc_attr_e('Insert Tag', 'contact-form-7');
        ?>" />
	</div>
	<br class="clear" />
	<p class="description mail-tag">
		<label for="<?php echo esc_attr($data['args']['content'].'-mailtag'); ?>">
            <?php
            echo sprintf(
                esc_html(
                // translators: %s: a mail tag span
                    __(
                        'To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.',
                        // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                        'contact-form-7'
                    )
                ),
                ' <strong><span class="mail-tag"></span></strong> '
            ); ?>
			<input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr($data['args']['content'].'-mailtag'); ?>" />
		</label>
	</p>
</div>
