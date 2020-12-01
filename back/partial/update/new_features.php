<div class="cell grid-x align-center">
	<div class="cell acym__splashcreen__container grid-x">
		<div class="cell grid-x acym__splashcreen__header" style="background-image: url(<?php echo acym_escapeDB(ACYM_IMAGES.'splashscreen/header.png'); ?>)">
			<img src="<?php echo ACYM_IMAGES.'splashscreen/header_astronaute.png'; ?>" alt="astronaute" class="acym__splashcreen__header__astronaute">
			<img src="<?php echo ACYM_IMAGES.'splashscreen/header_plane.png'; ?>" alt="planes" class="acym__splashcreen__header__planes">
			<div class="cell auto"></div>
			<div class="cell medium-6 grid-x grid-margin-y">
				<h1 class="cell">New Version 6.19.0 has arrived</h1>
				<p class="cell">Once again we've an incredible release for you. <br> 2020 comes to its end but we're not done yet!</p>
				<a href="<?php echo ACYM_ACYMAILLING_WEBSITE.'pricing/'; ?>" class="cell shrink button">Upgrade my account</a>
			</div>
		</div>
		<div class="cell grid-x align-center margin-top-3">
			<a href="<?php echo ACYM_ACYMAILLING_WEBSITE.'change-log/'; ?>"
			   class="margin-right-1 cell shrink button-secondary button margin-left-1"
			   target="_blank"><?php echo acym_translation(
                    'ACYM_SEE_FULL_CHANGELOG'
                ); ?></a>
			<button class="cell shrink button acy_button_submit" type="button" data-task="listing"><?php echo acym_translation('ACYM_SKIP'); ?></button>
		</div>
		<div class="cell grid-x acym__splashcreen__body align-center margin-top-3 margin-bottom-3">
			<h4 class="cell acym__splashcreen__body__subtitle text-center">SEE WHAT'S NEW</h4>
			<h2 class="cell acym__splashcreen__body__title text-center">What are the new features?</h2>
			<div class="cell grid-x grid-margin-x align-center acym__splashcreen__body__card__container">
				<div class="cell medium-4 acym__splashcreen__body__card grid-x">
					<div class="cell grid-x">
						<div class="acym__splashcreen__body__card__icon acym_vcenter align-center">
							<img src="<?php echo ACYM_IMAGES.'splashscreen/card_1.png'; ?>" alt="card_1_image">
						</div>
					</div>
					<h4 class="cell acym__splashcreen__body__card__title">Advanced statistics</h4>
					<p class="cell acym__splashcreen__body__card__text">Statistics have been drastically improved. More information below.</p>
				</div>
				<?php
				//__START__wordpress_
				?>
				<div class="cell medium-4 acym__splashcreen__body__card grid-x">
					<div class="cell grid-x">
						<div class="acym__splashcreen__body__card__icon acym_vcenter align-center">
							<img src="<?php echo ACYM_IMAGES.'splashscreen/card_2.png'; ?>" alt="card_2_image">
						</div>
					</div>
					<h4 class="cell acym__splashcreen__body__card__title">MemberPress integration</h4>
					<p class="cell acym__splashcreen__body__card__text">Include MemberPress information into your emails and many more.</p>
				</div>
				<?php
    			//__END__wordpress_
    			?>
				<div class="cell medium-4 acym__splashcreen__body__card grid-x">
					<div class="cell grid-x">
						<div class="acym__splashcreen__body__card__icon acym_vcenter align-center">
							<img src="<?php echo ACYM_IMAGES.'splashscreen/card_3.png'; ?>" alt="card_3_image">
						</div>
					</div>
					<h4 class="cell acym__splashcreen__body__card__title">Splash Screen</h4>
					<p class="cell acym__splashcreen__body__card__text">Every time you update you'll get all the news from this page.</p>
				</div>
			</div>
			<div class="cell grid-x acym__splashcreen__body__middle margin-top-2">
				<div class="cell medium-6 grid-x grid-margin-x acym__splashcreen__body__middle__left">
					<h4 class="cell acym__splashcreen__body__subtitle">New main feature</h4>
					<h2 class="cell acym__splashcreen__body__middle__left__title">Statistics improvements</h2>
				</div>
				<div class="cell medium-6 acym__splashcreen__body__middle__right grid-x grid-margin-y">
					<div class="cell grid-x">
						<div class="cell medium-2 small-3 acym_vcenter">
							<div class="acym__splashcreen__body__middle__right__icon acym_vcenter align-center">
								<i class="acymicon-group"></i>
							</div>
						</div>
						<div class="cell auto grid-x">
							<h2 class="cell acym__splashcreen__body__middle__right__title">List subscription graph</h2>
							<p class="cell acym__splashcreen__body__middle__right__text">How many users subscribed / unsubscribed from your lists in the past weeks</p>
						</div>
					</div>
					<div class="cell grid-x">
						<div class="cell medium-2 small-3 acym_vcenter">
							<div class="acym__splashcreen__body__middle__right__icon acym_vcenter align-center">
								<i class="acymicon-email"></i>
							</div>
						</div>
						<div class="cell auto grid-x">
							<h2 class="cell acym__splashcreen__body__middle__right__title">Open Time</h2>
							<p class="cell acym__splashcreen__body__middle__right__text">See what are the best days to send your newsletter</p>
						</div>
					</div>
					<div class="cell grid-x">
						<div class="cell medium-2 small-3 acym_vcenter">
							<div class="acym__splashcreen__body__middle__right__icon acym_vcenter align-center">
								<i class="acymicon-bar-chart"></i>
							</div>
						</div>
						<div class="cell auto grid-x">
							<h2 class="cell acym__splashcreen__body__middle__right__title">Clic details</h2>
							<p class="cell acym__splashcreen__body__middle__right__text">See who clicked on a link (and what link has been clicked)</p>
						</div>
					</div>
				</div>
			</div>
			<h4 class="cell acym__splashcreen__body__subtitle text-center margin-top-2">Previous release</h4>
			<h2 class="cell acym__splashcreen__body__title text-center">Here's a reminder of the features included in the previous release</h2>
			<div class="cell grid-x acym__splashcreen__body__end grid-margin-x">
				<div class="cell medium-6 acym__splashcreen__body__end__card">
					<img src="<?php echo ACYM_IMAGES.'splashscreen/end_card_1.png'; ?>" alt="" class="cell margin-bottom-1">
					<h2 class="cell text-center acym__splashcreen__body__end__card__title">Follow-up emails</h2>
					<p class="cell text-center acym__splashcreen__body__end__card__text">Schedule and send automatic emails based on a user action.
						Example: Someone purchases a product on your website </p>
					<div class="cell acym-grid-margin-x grid-x align-center">
						<div class="cell large-3 medium-4 acym__splashcreen__body__end__card__minicard grid-x align-center">
							<h3 class="cell acym__splashcreen__body__end__card__title">DAY+1</h3>
							<p class="cell acym__splashcreen__body__end__card__minicard__title__text">Thank you for your purchase!</p>
						</div>
						<div class="cell large-3 medium-4 acym__splashcreen__body__end__card__minicard grid-x align-center">
							<h3 class="cell acym__splashcreen__body__end__card__title">DAY+10</h3>
							<p class="cell acym__splashcreen__body__end__card__minicard__title__text">What do you think about your purchase?</p>
						</div>
						<div class="cell large-3 medium-4 acym__splashcreen__body__end__card__minicard grid-x align-center">
							<h3 class="cell acym__splashcreen__body__end__card__title">DAY+30</h3>
							<p class="cell acym__splashcreen__body__end__card__minicard__title__text">Here are some suggestions for you</p>
						</div>
					</div>
				</div>
				<div class="cell medium-6 acym__splashcreen__body__end__card">
					<img src="<?php echo ACYM_IMAGES.'splashscreen/end_card_1.png'; ?>" alt="" class="cell margin-bottom-1">
					<h2 class="cell text-center acym__splashcreen__body__end__card__title">Default emails override</h2>
					<p class="cell text-center acym__splashcreen__body__end__card__text">Customize default emails sent from your website such as the subscription
						confirmation email, password reset email etc...</p>
					<div class="cell acym-grid-margin-x grid-x align-center">
						<div class="cell large-3 medium-4 acym__splashcreen__body__end__card__minicard grid-x">
							<h3 class="cell acym__splashcreen__body__end__card__title">13</h3>
							<p class="cell acym__splashcreen__body__end__card__minicard__title__text">Joomla customizable default emails</p>
						</div>
						<div class="cell large-3 medium-4 acym__splashcreen__body__end__card__minicard grid-x">
							<h3 class="cell acym__splashcreen__body__end__card__title">12</h3>
							<p class="cell acym__splashcreen__body__end__card__minicard__title__text">WordPress customizable default emails</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="cell grid-x align-center margin-top-1 margin-bottom-2">
			<a href="<?php echo ACYM_ACYMAILLING_WEBSITE.'change-log/'; ?>"
			   class="margin-right-1 cell shrink button-secondary button margin-left-1"
			   target="_blank"><?php echo acym_translation(
                    'ACYM_SEE_FULL_CHANGELOG'
                ); ?></a>
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
