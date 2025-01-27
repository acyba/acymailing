<?php
acym_addStyle(false, ACYM_CSS.'back/deactivate.min.css');
acym_addScript(true, 'var ACYM_JS_TXT = '.acym_getJSMessages());
acym_loadCmsScripts();
acym_addScript(false, ACYM_JS.'deactivate.min.js');
?>
<div class="acym_deactivate_modal acym_deactivate_modal-deactivation-feedback">
	<div class="acym_deactivate_modal_dialog">
		<div class="acym_deactivate_modal_header">
			<div class="acym_deactivate_modal_title_container">
				<h3 id="acym_deactivate_modal_h3"><?php echo acym_translation('ACYM_QUICK_FEEDBACK'); ?></h3>
			</div>
			<div id="acym_deactivate_modal_dashicons_container">
				<span class="dashicons dashicons-no-alt"></span>
			</div>
		</div>
		<div class="acym_deactivate_modal_body">
			<div id="acym_deactivate_modal_body_container" class="medium-7">
				<div>
					<h4 id="acym_deactivate_modal_title"><?php echo acym_translation('ACYM_WHY_YOU_UNINSTALL_ACYMAILING'); ?></h4>
				</div>
				<ul id="acym_deactivate_modal_list">
					<li>
						<label for="acym_deactivate_modal_list_reason1">
							<input id="acym_deactivate_modal_list_reason1" type="radio" value="ACYM_FOUND_BETTER_PLUGIN" name="reason">
                            <?php echo acym_translation('ACYM_FOUND_BETTER_PLUGIN'); ?>
						</label>
					</li>
					<li>
						<label for="acym_deactivate_modal_list_reason2">
							<input id="acym_deactivate_modal_list_reason2" type="radio" value="ACYM_NO_NEEDED" name="reason">
                            <?php echo acym_translation('ACYM_NO_NEEDED'); ?>
						</label>
					</li>
					<li>
						<label for="acym_deactivate_modal_list_reason3">
							<input id="acym_deactivate_modal_list_reason3" type="radio" value="ACYM_PLUGIN_NOT_WORKING" name="reason">
                            <?php echo acym_translation('ACYM_PLUGIN_NOT_WORKING'); ?>
						</label>
					</li>
					<li>
						<label for="acym_deactivate_modal_list_reason4">
							<input id="acym_deactivate_modal_list_reason4" type="radio" value="ACYM_TEMPORARY_DEACTIVATION" name="reason">
                            <?php echo acym_translation('ACYM_TEMPORARY_DEACTIVATION'); ?>
						</label>
					</li>
					<li>
						<label for="acym_deactivate_modal_list_otherReason">
							<input id="acym_deactivate_modal_list_otherReason" type="radio" value="ACYM_OTHER" name="reason">
                            <?php echo acym_translation('ACYM_OTHER'); ?>
							<textarea id="acym_feedback_otherReason" name="otherReasonText" rows="5" cols="50"></textarea>
						</label>
					</li>
				</ul>
				<div id="acym_feedback_contact_container_display">
					<div>
						<h4 id="acym_deactivate_modal_information_title"><?php echo acym_translation('ACYM_CONTACT_INFORMATION'); ?></h4>
					</div>
					<div>
						<label for="acym_feedback_contact_email" id="acym_feedback_contact_email_label">
                            <?php echo acym_translation('ACYM_EMAIL').' :'; ?>
							<input type="email" name="acym_feedback_contact_email" id="acym_feedback_contact_email" value="<?php echo acym_escape(acym_currentUserEmail()); ?>">
						</label>
					</div>
					<div id="acym_feedback_contact_checkbox_container">
						<input type="checkbox" name="acym_feedback_contact_checkbox" id="acym_feedback_contact_checkbox" checked>
						<label for="acym_feedback_contact_checkbox">
                            <?php echo acym_translation('ACYM_FEEDBACK_SEND_USER_EMAIL'); ?>
						</label>
					</div>
				</div>
			</div>
		</div>

		<div class="acym_deactivate_modal_footer">
			<a href="#" class="acym_deactivate_button button acym_deactivate_modal_button_close">
                <?php echo acym_translation('ACYM_CANCEL'); ?>
			</a>
			<a href="#" class="acym_deactivate_button button acym_deactivate_button_deactivate">
                <?php echo acym_translation('ACYM_SKIP_AND_DEACTIVATE'); ?>
			</a>
		</div>
	</div>
</div>
