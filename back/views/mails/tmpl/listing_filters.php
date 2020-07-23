<div class="large-3 medium-8 cell">
    <?php echo acym_filterSearch($data['search'], 'mails_search', 'ACYM_SEARCH'); ?>
</div>
<div class="large-3 medium-4 cell">
    <?php
    $allTags = new stdClass();
    $allTags->name = acym_translation('ACYM_ALL_TAGS');
    $allTags->value = '';
    array_unshift($data['allTags'], $allTags);

    echo acym_select(
        $data['allTags'],
        'mails_tag',
        acym_escape($data['tag']),
        'class="acym__templates__filter__tags acym__select"',
        'value',
        'name'
    );
    ?>
</div>
