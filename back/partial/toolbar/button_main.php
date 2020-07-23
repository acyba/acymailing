<button class="acym_vcenter align-center acym__toolbar__button acy_button_submit cell medium-6 large-shrink <?php echo $data['isPrimary'] ? 'acym__toolbar__button-primary' : 'acym__toolbar__button-secondary' ?>"

<?php foreach ($data['attributes'] as $attribute => $value) {
    echo $attribute.'="'.acym_escape($value).'" ';
}

echo '>';

if (!empty($data['icon'])) {
    echo '<i class="acymicon-'.$icon.'"></i>';
}

echo '  '.$data['content'];
?>
</button>
