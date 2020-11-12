<div class="control-box">
	<fieldset>
		<legend><?php echo acym_translation('ACYM_INSERT_CONTACTFORM_TAG'); ?></legend>

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php echo esc_html(__('Field type', 'contact-form-7')); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php echo esc_html(__('Field type', 'contact-form-7')); ?></legend>
							<label><input type="checkbox" name="required" /> <?php echo esc_html(__('Required field', 'contact-form-7')); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="<?php echo esc_attr($data['args']['content'].'-name'); ?>"><?php echo esc_html(__('Name', 'contact-form-7')); ?></label></th>
					<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr($data['args']['content'].'-name'); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="<?php echo esc_attr($data['args']['content'].'-acymmail'); ?>"><?php echo acym_translation('ACYM_MAIL_FIELD_CONTACT'); ?></label>
					</th>
					<td><input type="text" name="acymmail" class="classvalue oneline option" id="<?php echo esc_attr($data['args']['content'].'-acymmail'); ?>" /></td>
				</tr>

                <?php foreach ($data['propertyLabels'] as $key => $label) { ?>
					<tr>
						<th scope="row"><?php echo $label; ?></th>
						<td>
							<fieldset>
                                <?php
                                echo acym_selectMultiple(
                                    $data['lists'],
                                    $key,
                                    [],
                                    [],
                                    'id',
                                    'name'
                                );
                                ?>
								<input type="hidden" name="<?php echo $key; ?>" data-type="<?php echo $key; ?>" value="">

							</fieldset>
						</td>
					</tr>
                <?php } ?>

				<tr>
					<th scope="row"><label for="<?php echo esc_attr($data['args']['content'].'-class'); ?>"><?php echo esc_html(__('Class attribute', 'contact-form-7')); ?></label>
					</th>
					<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr($data['args']['content'].'-class'); ?>" /></td>
				</tr>
			</tbody>
		</table>
		<input type="hidden" name="values" value="">
	</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="acymsub" class="tag code" readonly="readonly" onfocus="this.select()" />
	<div class="submitbox">
		<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insert Tag', 'contact-form-7')); ?>" />
	</div>
	<br class="clear" />
	<p class="description mail-tag">
		<label for="<?php echo esc_attr($data['args']['content'].'-mailtag'); ?>">
            <?php
            echo sprintf(
                esc_html(
                    __(
                        "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.",
                        'contact-form-7'
                    )
                ),
                ' <strong><span class="mail-tag" ></span ></strong > '
            ); ?>
			<input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr($data['args']['content'].'-mailtag'); ?>" />
		</label>
	</p>
</div>
