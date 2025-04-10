<?php
$community_content = $data['engage_community'];
?>

<div class="acym__content cell">
	<div style="display: flex; justify-content: space-between;">
		<div class="shrink acym__title acym__dashboard__title"><?php echo acym_translation('ACYM_ENGAGE_COMMUNITY'); ?></div>
		<div style="user-select: none;">
			<i id="prevBtn" class="acymicon-keyboard-arrow-left disabled"></i>
			<i id="nextBtn" class="acymicon-keyboard-arrow-right"></i>
		</div>
	</div>
	<span class="separator"></span>
	<div class="sliderCommunity">
		<div class="slides-wrapper">
            <?php
            $chunkedSlides = array_chunk($community_content, 3);
            foreach ($chunkedSlides as $group) {
                echo '<div class="slide-group">';
                foreach ($group as $index => $content) {
                    ?>
					<div class="acym_vcenter slide gap-1">
						<div>
							<i class="<?php echo $content['icon']; ?> community-icons"></i>
						</div>
						<div>

							<h5><a href="<?php echo $content['link'] ? acym_completeLink($content['link']) : $content['link_doc']; ?>" target="_blank">
                                    <?php echo acym_escape(
                                        acym_translation(
                                            $content['title']
                                        )
                                    ); ?><i class="acymicon-external-link small-icon"></i></a></h5>
							<p><?php echo acym_escape(acym_translation($content['text'])); ?></p>
						</div>
					</div>
                    <?php
                    if (($index + 1) % 3 !== 0) {
                        echo '<span class="separator acym__dashboard__light__separator"></span>';
                    }
                }
                echo '</div>';
            }
            ?>
		</div>
	</div>
</div>
