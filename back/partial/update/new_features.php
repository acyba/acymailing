<div class="cell grid-x align-center">
	<div class="cell acym__splashcreen__container grid-x">
		<div class="cell grid-x acym__splashcreen__header" style="background-image: url(<?php echo acym_escapeDB(ACYM_IMAGES.'splashscreen/background.png'); ?>)">
			<img src="<?php echo ACYM_IMAGES.'splashscreen/header_astronaut.png'; ?>" alt="astronaute" class="acym__splashcreen__header__astronaute">
			<img src="<?php echo ACYM_IMAGES.'splashscreen/header_airplane.png'; ?>" alt="planes" class="acym__splashcreen__header__planes">
			<div class="cell auto"></div>
			<div class="cell medium-6 grid-x grid-margin-y">
				<h1 class="cell">AcyMailing 7.6 has arrived 🎉</h1>
				<p class="cell">Features and improvements come in your plugin, find out what’s new!</p>
                <?php
                //__START__starter_
                if (!acym_level(ACYM_ESSENTIAL)) {
                    ?>
					<a href="<?php echo ACYM_ACYMAILLING_WEBSITE.'pricing/'; ?>" class="cell shrink button">Purchase a license!</a>
                    <?php
                }
                //__END__starter_
                ?>
			</div>
		</div>
		<div class="cell grid-x align-center margin-top-3">
			<a href="<?php echo ACYM_ACYMAILLING_WEBSITE.'change-log/'; ?>"
			   class="margin-right-1 cell shrink button-secondary button margin-left-1"
			   target="_blank">
                <?php echo acym_translation('ACYM_SEE_FULL_CHANGELOG'); ?>
			</a>
			<button class="cell shrink button acy_button_submit" type="button" data-task="listing"><?php echo acym_translation('ACYM_SKIP'); ?></button>
		</div>
		<div class="cell grid-x acym__splashcreen__body align-center margin-top-3 margin-bottom-3">
			<h2 class="cell acym__splashcreen__body__title text-center">What are the new features?</h2>
			<div class="cell grid-x grid-margin-x align-center acym__splashcreen__body__card__container">
				<div class="cell medium-4 acym__splashcreen__body__card grid-x">
					<div class="cell grid-x align-center">
						<div class="acym__splashcreen__body__card__icon acym_vcenter align-center">
							<img src="<?php echo ACYM_IMAGES.'splashscreen/card_1.png'; ?>" alt="card_1_image">
						</div>
					</div>
					<h4 class="cell acym__splashcreen__body__card__title">Integration with AcyChecker</h4>
					<p class="cell acym__splashcreen__body__card__text">AcyMailing is now integrated with our email checker tool. Clean your contacts to reduce sending costs and
						improve your deliverability.</p>
				</div>
				<div class="cell medium-4 acym__splashcreen__body__card grid-x">
					<div class="cell grid-x align-center">
						<div class="acym__splashcreen__body__card__icon acym_vcenter align-center">
							<img src="<?php echo ACYM_IMAGES.'splashscreen/card_2.png'; ?>" alt="card_2_image">
						</div>
					</div>
					<h4 class="cell acym__splashcreen__body__card__title">A new add-on with JCal Pro is available</h4>
					<p class="cell acym__splashcreen__body__card__text">Automatically insert events in your emails, no need to do it manually anymore!</p>
				</div>
				<div class="cell medium-4 acym__splashcreen__body__card grid-x">
					<div class="cell grid-x align-center">
						<div class="acym__splashcreen__body__card__icon acym_vcenter align-center">
							<img src="<?php echo ACYM_IMAGES.'splashscreen/card_3.png'; ?>" alt="card_3_image">
						</div>
					</div>
					<h4 class="cell acym__splashcreen__body__card__title">AcyMailing actions for Elementor</h4>
					<p class="cell acym__splashcreen__body__card__text">You can now add custom AcyMailing actions on Elementor form submission to subscribe the users.</p>
				</div>
			</div>
			<div class="cell grid-x acym__splashcreen__body__middle margin-top-2">
				<div class="cell medium-6 grid-x grid-margin-x acym__splashcreen__body__middle__left">
					<h4 class="cell acym__splashcreen__body__subtitle">New main feature</h4>
					<h2 class="cell acym__splashcreen__body__middle__left__title">Unsubscribe Reason</h2>
				</div>
				<div class="cell medium-6 acym__splashcreen__body__middle__right grid-x grid-margin-y">
					<div class="cell grid-x">
						<div class="cell medium-2 small-3 acym_vcenter">
							<div class="acym__splashcreen__body__middle__right__icon acym_vcenter align-center">
								<i class="acymicon-autorenew"></i>
							</div>
						</div>
						<div class="cell auto grid-x">
							<h2 class="cell acym__splashcreen__body__middle__right__title">Know why your users unsubscribe from your lists</h2>
							<p class="cell acym__splashcreen__body__middle__right__text">A text field has been added into the unsubscribe page so you can now collect feedback.
								This way you’ll be able to know what improvements you should do to keep them in your subscribers list.</p>
						</div>
					</div>
				</div>
			</div>
			<h2 class="cell acym__splashcreen__body__title text-center">Please remind me the features you included into the previous release?</h2>
			<div class="cell grid-x acym__splashcreen__body__end grid-margin-x">
				<div class="cell medium-6 acym__splashcreen__body__end__card">
					<div class="cell margin-bottom-1 text-center">
						<img src="<?php echo ACYM_IMAGES.'splashscreen/end_card_1.png'; ?>" alt="">
					</div>
					<h2 class="cell text-center acym__splashcreen__body__end__card__title">Loading time improvement</h2>
					<p class="cell text-center acym__splashcreen__body__end__card__text">The listing loading time has been optimised.</p>
				</div>
				<div class="cell medium-6 acym__splashcreen__body__end__card">
					<div class="cell margin-bottom-1 text-center">
						<img src="<?php echo ACYM_IMAGES.'splashscreen/end_card_2.png'; ?>" alt="">
					</div>
					<h2 class="cell text-center acym__splashcreen__body__end__card__title">Subscription form on Elementor</h2>
					<p class="cell text-center acym__splashcreen__body__end__card__text">A subscription form can be integrated into your posts with Elementor.</p>
				</div>
			</div>
		</div>
		<div class="cell grid-x align-center margin-top-1 margin-bottom-2">
			<a href="<?php echo ACYM_ACYMAILLING_WEBSITE.'change-log/'; ?>"
			   class="margin-right-1 cell shrink button-secondary button margin-left-1"
			   target="_blank"><?php echo acym_translation('ACYM_SEE_FULL_CHANGELOG'); ?></a>
			<button class="cell shrink button acy_button_submit" type="button" data-task="listing"><?php echo acym_translation('ACYM_SKIP'); ?></button>
		</div>
		<div class="cell grid-x acym__splashcreen__footer align-center grid-margin-x">
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
