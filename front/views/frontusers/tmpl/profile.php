<div id="acym_fulldiv_acyprofileform" class="acym_front_page">
    <?php
    if (!empty($data['show_page_heading'])) {
        echo '<h1 class="contentheading'.$data['suffix'].'">'.$data['page_heading'].'</h1>';
    }

    if (!empty($data['introtext'])) {
        echo '<span class="acym_introtext">'.$data['introtext'].'</span>';
    }
    ?>

	<form enctype="multipart/form-data"
		  action="<?php echo acym_frontendLink('frontusers'.(acym_isNoTemplate() ? '&'.acym_noTemplate() : '')); ?>"
		  method="post"
		  name="acyprofileform"
		  id="acyprofileform"
		  onsubmit="this.querySelector('input[type=submit]').click(); return false;">
		<fieldset class="adminform acy_user_info">
			<legend><span><?php echo acym_translation('ACYM_USER_INFORMATION'); ?></span></legend>
            <?php

            ?>
			<div id="acyuserinfo">
                <?php

                foreach ($data['fields'] as $field) {
                    $fieldDB = empty($field->option->fieldDB) ? '' : json_decode($field->option->fieldDB);
                    $field->value = empty($field->value) ? '' : json_decode($field->value);
                    $field->option = json_decode($field->option);
                    $valuesArray = [];
                    if (!empty($field->value)) {
                        foreach ($field->value as $value) {
                            $valueTmp = new stdClass();
                            $valueTmp->text = $value->title;
                            $valueTmp->value = $value->value;
                            if ($value->disabled == 'y') $valueTmp->disable = true;
                            $valuesArray[$value->value] = $valueTmp;
                        }
                    }
                    if (!empty($fieldDB) && !empty($fieldDB->value)) {
                        $fromDB = $data['fieldClass']->getValueFromDB($fieldDB);
                        foreach ($fromDB as $value) {
                            $valuesArray[$value->value] = $value->title;
                        }
                    }
                    $size = empty($field->option->size) ? '' : 'width:'.$field->option->size.'px';
                    echo '<span class="onefield fieldacy'.$field->id.'" id="field_'.$field->id.'">';

                    echo $data['fieldClass']->displayField($field, $field->default_value, $size, $valuesArray, true, true, $data['user']);
                    echo '</span>';
                }
                ?>
			</div>

            <?php
            $exportButton = $this->config->get('gdpr_export', 0);
            $deleteButton = $this->config->get('gdpr_delete', 0);
            if (!empty($data['user']->id) && !(empty($exportButton) && empty($deleteButton))) {
                ?>
				<div id="acyuseractions">
					<table cellpadding="0">
						<tr>
                            <?php
                            if ($exportButton == 1) {
                                ?>
								<td id="acybutton_subscriber_download_data" <?php if ($deleteButton == 1) {
                                    echo 'style="padding-right: 10px;"';
                                } ?>>
									<button class="btn" onclick="this.form.task.value='exportdata'; this.form.submit(); return false;">
                                        <?php echo acym_translation('ACYM_EXPORT_MY_DATA'); ?>
									</button>
								</td>
                                <?php
                            }
                            if ($deleteButton == 1) {
                                ?>
								<td id="acybutton_subscriber_delete_data">
									<button class="btn"
											onclick="if(confirm(ACYM_JS_TXT.ACYM_ARE_YOU_SURE + '\n' + ACYM_JS_TXT.ACYM_DELETE_MY_DATA_CONFIRM)){ this.form.task.value = 'delete'; this.form.submit(); } return false;">
                                        <?php echo acym_translation('ACYM_DELETE_MY_DATA'); ?>
									</button>
								</td>
                                <?php
                            }
                            ?>
						</tr>
					</table>
				</div>
            <?php } ?>
		</fieldset>
        <?php

        if ($data['displayLists']) {
            ?>
			<fieldset class="adminform acy_subscription_list">
				<legend><span><?php echo acym_translation('ACYM_SUBSCRIPTION'); ?></span></legend>

				<div id="acyusersubscription">
                    <?php
                    if (empty($data['dropdown'])) {
                        $values = [];
                        $values[0] = acym_selectOption('-1', 'ACYM_NO');
                        $values[1] = acym_selectOption('1', 'ACYM_YES');
                        $values[0]->class = 'btn-danger';
                        $values[1]->class = 'btn-success';

                        $k = 0;
                        foreach ($data['subscription'] as $row) {
                            if (empty($row->active) || !$row->visible) {
                                continue;
                            }
                            if (empty($row->status)) {
                                $row->status = -1;
                            }

                            echo '<div class="acym_list">
                                    <div class="acystatus">'.acym_radio(
                                    $values,
                                    'data[listsub]['.$row->id.'][status]',
                                    $row->status,
                                    [],
                                    ['id' => 'status'.$k++],
                                    true
                                ).'</div>
                                    <div class="list_name">'.$row->name.'</div>
                                </div>';
                        }
                    } else {
                        $selectedIndex = '';
                        $k = 0;
                        $dropdownOpts = [];
                        foreach ($data['subscription'] as $key => $row) {
                            if (empty($row->active) || !$row->visible) {
                                continue;
                            }

                            $value = 0;
                            $dropdownOpts[] = acym_selectOption($row->id, $row->name);
                            if ($row->status == 1) {
                                $value = 1;
                                $selectedIndex = $k;
                            }
                            echo '<input type="hidden" class="listsub-dropdown" name="data[listsub]['.$row->id.'][status]" value="'.$value.'">';

                            $k++;
                        }

                        echo acym_select($dropdownOpts, 'data[listsubdropdown]', $selectedIndex, null, 'value', 'text');
                    }
                    ?>
				</div>
			</fieldset>
            <?php
        }

        if (empty($data['user']->id) && $data['config']->get('captcha', '') == 1) {
            echo '<div id="trcaptcha" class="acy_onefield">';
            echo $data['captchaHelper']->display('acyprofileform');
            echo '</div>';
        }

        if (!empty($data['source'])) {
            echo '<input type="hidden" name="acy_source" value="'.acym_escape($data['source']).'" />';
        }

        // CMS specific things
        if (!empty($data['Itemid'])) {
            echo '<input type="hidden" name="Itemid" value="'.acym_escape($data['Itemid']).'" />';
        }

        if (acym_isNoTemplate()) {
            echo '<input type="hidden" name="tmpl" value="component"/>';
        }

        // Form params
        acym_formOptions(true, 'savechanges', null, 'frontusers');
        ?>

		<input type="hidden" name="hiddenlists" value="<?php echo implode(',', $data['hiddenlists']); ?>" />
		<input type="hidden" name="id" value="<?php echo acym_escape($data['user']->id); ?>" />
		<input type="hidden" name="key" value="<?php echo acym_escape($data['user']->key); ?>" />
		<input type="hidden" name="ajax" value="1" />
		<input type="hidden" name="acyprofile" value="1" />

		<p class="acymodifybutton">
			<input class="btn btn-primary"
				   type="submit"
				   onclick="return submitAcymForm('savechanges', 'acyprofileform', 'acym_checkChangeForm');"
				   value="<?php echo acym_escape(acym_translation(empty($data['user']->id) ? 'ACYM_SUBSCRIBE' : 'ACYM_SAVE_CHANGES')); ?>" />
		</p>
	</form>
    <?php if (!empty($data['posttext'])) {
        echo '<span class="acym_posttext">'.$data['posttext'].'</span>';
    } ?>
</div>
