<button class="acym_vcenter align-center acy_button_submit cell medium-6 large-shrink button <?php echo $data['isPrimary'] ? '' : 'button-secondary'; ?>"
<?php
foreach ($data['attributes'] as $attribute => $value) {
    echo $attribute.'="'.acym_escape($value).'" ';
}

echo '>';

if (!empty($data['icon'])) {
    echo '<i class="acymicon-'.$icon.'"></i>';
}

echo '  '.$data['content'];
?>
</button>
