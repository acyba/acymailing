<div class="cell grid-x align-center">
	<div class="cell acym__splashscreen__container grid-x">
		<div class="cell grid-x acym__splashscreen__header" style="background-image: url(<?php echo acym_escape(ACYM_IMAGES.'splashscreen/header_background.png'); ?>)">
			<img src="<?php echo ACYM_IMAGES.'splashscreen/header_astronaut.png'; ?>" alt="astronaut" class="acym__splashscreen__header__astronaut">
			<img src="<?php echo ACYM_IMAGES.'splashscreen/header_comet_top.png'; ?>" alt="" class="acym__splashscreen__header__comet__top">
			<img src="<?php echo ACYM_IMAGES.'splashscreen/header_comet_bottom.png'; ?>" alt="" class="acym__splashscreen__header__comet__bottom">
			<div class="cell auto"></div>
			<div class="cell medium-6 grid-x grid-margin-y">
				<h1 class="cell">AcyMailing 8.5 is here 🎉</h1>
				<p class="cell">Features and improvements come in your plugin, find out what’s new!</p>
                <?php
                //__START__starter_
                if (!acym_level(ACYM_ESSENTIAL)) {
                    echo '<a href="'.ACYM_ACYMAILING_WEBSITE.'pricing/" class="cell shrink button">Purchase a license!</a>';
                }
                //__END__starter_
                ?>
			</div>
		</div>
		<div class="cell grid-x align-center margin-top-3">
			<a href="<?php echo ACYM_ACYMAILING_WEBSITE.'change-log/'; ?>"
			   class="margin-right-1 cell shrink button-secondary button margin-left-1"
			   target="_blank">
                <?php echo acym_translation('ACYM_SEE_FULL_CHANGELOG'); ?>
			</a>
			<button class="cell shrink button acy_button_submit" type="button" data-task="listing"><?php echo acym_translation('ACYM_SKIP'); ?></button>
		</div>
		<div class="cell grid-x acym__splashscreen__body align-center margin-top-3">
			<h2 class="cell acym__splashscreen__body__title text-center">What are the new features?</h2>
			<div class="cell grid-x grid-margin-x align-center acym__splashscreen__body__card__container margin-bottom-2">
				<div class="cell medium-4 acym__splashscreen__body__card grid-x">
					<div class="cell grid-x align-center">
						<div class="acym__splashscreen__body__card__icon acym_vcenter align-center">
							<img src="<?php echo ACYM_IMAGES.'splashscreen/card_1.png'; ?>" alt="card_1_image">
						</div>
					</div>
					<h4 class="cell acym__splashscreen__body__card__title">New integration with hCaptcha</h4>
					<p class="cell acym__splashscreen__body__card__text">
						Improve the security of your subscription forms while staying compatible with the RGPD (European law)!
						The hCaptcha is a good alternative to the Google reCaptcha, which complies with the RGPD.
					</p>
				</div>
				<div class="cell medium-4 acym__splashscreen__body__card grid-x">
					<div class="cell grid-x align-center">
						<div class="acym__splashscreen__body__card__icon acym_vcenter align-center">
							<img src="<?php echo ACYM_IMAGES.'splashscreen/card_2.png'; ?>" alt="card_2_image">
						</div>
					</div>
                    <?php if (ACYM_CMS === 'wordpress') { ?>
						<h4 class="cell acym__splashscreen__body__card__title">New compatibility with WordPress Bedrock</h4>
						<p class="cell acym__splashscreen__body__card__text">
							We are happy to announce that AcyMailing is now compatible with WordPress Bedrock!
							WordPress Bedrock is a modern WordPress stack that helps you get started with the best development tools and project structure.
						</p>
                    <?php } else { ?>
						<h4 class="cell acym__splashscreen__body__card__title">New compatibility with autologin</h4>
						<p class="cell acym__splashscreen__body__card__text">
							The autologin plugin enables your users to automatically login to your website if the URL contains their username and password.
							If this plugin is installed, you'll be able to use the autologin feature in AcyMailing when inserting articles in your newsletters.
						</p>
                    <?php } ?>
				</div>
				<div class="cell medium-4 acym__splashscreen__body__card grid-x">
					<div class="cell grid-x align-center">
						<div class="acym__splashscreen__body__card__icon acym_vcenter align-center">
							<img src="<?php echo ACYM_IMAGES.'splashscreen/card_3.png'; ?>" alt="card_3_image">
						</div>
					</div>
					<h4 class="cell acym__splashscreen__body__card__title">More capabilities with segments</h4>
					<p class="cell acym__splashscreen__body__card__text">
						When creating a campaign, you can now choose to include or exclude subscribers matching a segment.
						You can also now export your subscribers matching a segment.
					</p>
				</div>
			</div>
			<h2 class="cell acym__splashscreen__body__title text-center">Please remind me the features you included into the previous release?</h2>
			<div class="cell grid-x acym__splashscreen__body__end grid-margin-x">
				<div class="cell medium-6 acym__splashscreen__body__end__card">
					<div class="cell margin-bottom-1 text-center">
						<img src="<?php echo ACYM_IMAGES.'splashscreen/end_card_1.png'; ?>" alt="">
					</div>
					<h2 class="cell text-center acym__splashscreen__body__end__card__title">A mailbox actions system</h2>
					<p class="cell text-center acym__splashscreen__body__end__card__text">
						Let your users subscribe/unsubscribe automatically by sending an email with a specific word, or let your admins send campaigns to a list with a simple
						email!
						AcyMailing look at the emails received in a dedicated mailbox and perform actions you selected in advance.
					</p>
				</div>
				<div class="cell medium-6 acym__splashscreen__body__end__card">
					<div class="cell margin-bottom-1 text-center">
						<img src="<?php echo ACYM_IMAGES.'splashscreen/end_card_2.png'; ?>" alt="">
					</div>
					<h2 class="cell text-center acym__splashscreen__body__end__card__title">Email video insertion</h2>
					<p class="cell text-center acym__splashscreen__body__end__card__text">
						When inserting YouTube, Dailymotion or Vimeo videos in an email, a play button will be added to indicate to your recipients that this is clickable.
						Increase your impact in your emails!
					</p>
				</div>
			</div>
		</div>
		<div class="cell grid-x align-center margin-top-1 margin-bottom-2">
			<a href="<?php echo ACYM_ACYMAILING_WEBSITE.'change-log/'; ?>"
			   class="margin-right-1 cell shrink button-secondary button margin-left-1"
			   target="_blank"><?php echo acym_translation('ACYM_SEE_FULL_CHANGELOG'); ?></a>
			<button class="cell shrink button acy_button_submit" type="button" data-task="listing"><?php echo acym_translation('ACYM_SKIP'); ?></button>
		</div>
		<div class="cell grid-x acym__splashscreen__footer align-center grid-margin-x">
			<a href="https://www.linkedin.com/company/acymailing/" class="cell shrink" target="_blank">
				<img src="<?php echo ACYM_IMAGES.'splashscreen/linkedin.png'; ?>" alt="">
			</a>
			<a href="https://www.facebook.com/AcyMailing/" class="cell shrink" target="_blank">
				<img src="<?php echo ACYM_IMAGES.'splashscreen/facebook.png'; ?>" alt="">
			</a>
			<a href="https://twitter.com/acymailingoff" class="cell shrink" target="_blank">
				<img src="<?php echo ACYM_IMAGES.'splashscreen/twitter.png'; ?>" alt="">
			</a>
		</div>
	</div>
</div>
