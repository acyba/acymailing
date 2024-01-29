<li class="acym_<?php echo $data['property_name']; ?>_setting field_setting">
	<p class="section_label" style="margin-bottom: 0"><?php echo $data['label']; ?>:</p>
	<select id="<?php echo $data['property_name']; ?>" multiple onchange="saveAcyListSelectMultiple('<?php echo $data['property_name']; ?>', this)">
        <?php
        foreach ($data['lists'] as $id => $name) {
            echo '<option value="'.$id.'">'.acym_escape($name).'</option>';
        }
        ?>
	</select>
</li>
