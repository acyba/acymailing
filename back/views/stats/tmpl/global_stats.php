<div id="acym_stats_global">
    <?php
    if (empty($data['sentMails'])) {
        include __DIR__.DS.'global_stats_example.php';
    } else {
        include __DIR__.DS.'global_stats_data.php';
    }
    ?>
</div>
