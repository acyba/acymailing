<div class="cell grid-x align-center">
	<div class="cell acym__splashscreen__container grid-x">
		<div class="cell grid-x acym__splashscreen__header" style="background-image: url(<?php echo acym_escape(ACYM_IMAGES.'splashscreen/header_background.png'); ?>)">
			<img src="<?php echo ACYM_IMAGES.'splashscreen/header_astronaut.png'; ?>" alt="astronaut" class="acym__splashscreen__header__astronaut">
			<img src="<?php echo ACYM_IMAGES.'splashscreen/header_comet_top.png'; ?>" alt="" class="acym__splashscreen__header__comet__top">
			<img src="<?php echo ACYM_IMAGES.'splashscreen/header_comet_bottom.png'; ?>" alt="" class="acym__splashscreen__header__comet__bottom">
			<div class="cell auto"></div>
			<div class="cell medium-6 grid-x grid-margin-y">
				<h1 class="cell">AcyMailing 8 is here 🎉</h1>
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
		<div class="cell grid-x acym__splashscreen__body align-center margin-top-3 margin-bottom-3">
			<h2 class="cell acym__splashscreen__body__title text-center">What are the new features?</h2>
			<div class="cell grid-x grid-margin-x align-center acym__splashscreen__body__card__container">
				<div class="cell medium-4 acym__splashscreen__body__card grid-x">
					<div class="cell grid-x align-center">
						<div class="acym__splashscreen__body__card__icon acym_vcenter align-center">
							<img src="<?php echo ACYM_IMAGES.'splashscreen/card_1.png'; ?>" alt="card_1_image">
						</div>
					</div>
					<h4 class="cell acym__splashscreen__body__card__title">Editor ease of use</h4>
					<p class="cell acym__splashscreen__body__card__text">
						The editor will now auto-scroll when moving a block near the top/bottom of the email you're designing.
						You can also set 3 default colors that will be available in the editor color pickers.
					</p>
				</div>
				<div class="cell medium-4 acym__splashscreen__body__card grid-x">
					<div class="cell grid-x align-center">
						<div class="acym__splashscreen__body__card__icon acym_vcenter align-center">
							<img src="<?php echo ACYM_IMAGES.'splashscreen/card_2.png'; ?>" alt="card_2_image">
						</div>
					</div>
					<h4 class="cell acym__splashscreen__body__card__title">Multilingual support for subscription forms</h4>
					<p class="cell acym__splashscreen__body__card__text">
						If the multilingual is active in AcyMailing, you'll be able to customize the subscribe button's text and the confirmation message of subscription forms per
						language.
					</p>
				</div>
				<div class="cell medium-4 acym__splashscreen__body__card grid-x">
					<div class="cell grid-x align-center">
						<div class="acym__splashscreen__body__card__icon acym_vcenter align-center">
							<img src="<?php echo ACYM_IMAGES.'splashscreen/card_3.png'; ?>" alt="card_3_image">
						</div>
					</div>
					<h4 class="cell acym__splashscreen__body__card__title">Email video insertion</h4>
					<p class="cell acym__splashscreen__body__card__text">
						When inserting YouTube, Dailymotion or Vimeo videos in an email, a play button will be added to indicate to your recipients that this is clickable.
						Increase your impact in your emails!
					</p>
				</div>
			</div>
			<div class="cell grid-x acym__splashscreen__body__middle margin-top-2">
				<div class="cell medium-4 grid-x grid-margin-x acym__splashscreen__body__middle__left">
					<h2 class="cell acym__splashscreen__body__subtitle">New main feature</h2>
				</div>
				<div class="cell medium-8 acym__splashscreen__body__middle__right grid-x grid-margin-y">
					<div class="cell grid-x grid-margin-x">
						<div class="cell medium-3 acym_vcenter align-center">
							<div class="acym__splashscreen__body__middle__right__icon acym_vcenter align-center">
								<img src="<?php echo ACYM_IMAGES.'splashscreen/main_feature.png'; ?>" alt="main_feature_image">
							</div>
						</div>
						<div class="cell auto grid-x">
							<h2 class="cell acym__splashscreen__body__middle__right__title">A mailbox actions system</h2>
							<p class="cell acym__splashscreen__body__middle__right__text">
								Let your users subscribe/unsubscribe automatically by sending an email with a specific word, or let your admins send campaigns to a list with a
								simple email!
								AcyMailing look at the emails received in a dedicated mailbox and perform actions you selected in advance.
							</p>
						</div>
					</div>
				</div>
			</div>
			<h2 class="cell acym__splashscreen__body__title text-center">Please remind me the features you included into the previous release?</h2>
			<div class="cell grid-x acym__splashscreen__body__end grid-margin-x">
				<div class="cell medium-6 acym__splashscreen__body__end__card">
					<div class="cell margin-bottom-1 text-center">
						<img src="<?php echo ACYM_IMAGES.'splashscreen/end_card_1.png'; ?>" alt="">
					</div>
					<h2 class="cell text-center acym__splashscreen__body__end__card__title">Design your emails faster</h2>
					<p class="cell text-center acym__splashscreen__body__end__card__text">
						Our drag&drop editor is now 5.5 times faster than the previous version! It is more fluid and with new functionalities, we really wanted to improve your
						experience on AcyMailing!
					</p>
				</div>
				<div class="cell medium-6 acym__splashscreen__body__end__card">
					<div class="cell margin-bottom-1 text-center">
						<img src="<?php echo ACYM_IMAGES.'splashscreen/end_card_2.png'; ?>" alt="">
					</div>
					<h2 class="cell text-center acym__splashscreen__body__end__card__title">Reusable zones in the editor</h2>
					<p class="cell text-center acym__splashscreen__body__end__card__text">
						You can now save zones along with their blocks, and reuse them in other emails! Save time designing your newsletters by clicking the save button at the
						top-right of a zone.
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
			<a href="https://www.facebook.com/acymailing/" class="cell shrink" target="_blank">
				<img src="<?php echo ACYM_IMAGES.'splashscreen/facebook.png'; ?>" alt="">
			</a>
			<a href="https://twitter.com/acymailingoff" class="cell shrink" target="_blank">
				<img src="<?php echo ACYM_IMAGES.'splashscreen/twitter.png'; ?>" alt="">
			</a>
		</div>
	</div>
</div>
