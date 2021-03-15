<div class="cell grid-x acym__modal__users__summary" acym-data-query="<?php echo acym_escape(json_encode($dataModal)) ?>">
	<h2 class="cell shrink acym__title"><?php echo acym_translation('ACYM_SELECTED_SUBSCRIBER'); ?></h2>
	<div class="cell grid-x acym__modal__users__summary__actions margin-top-1 margin-bottom-1">
		<input type="text" class="cell" placeholder="<?php echo acym_translation('ACYM_SEARCH'); ?>" v-model="search">
	</div>
	<div class="cell grid-x acym__listing">
		<div class="grid-x cell acym__listing__header hide-for-small-only">
			<div class="cell acym__listing__header__title medium-6"><?php echo acym_translation('ACYM_EMAIL'); ?></div>
			<div class="cell acym__listing__header__title medium-4"><?php echo acym_translation('ACYM_NAME'); ?></div>
			<div class="cell large-1 acym__listing__header__title medium-2"><?php echo acym_translation('ACYM_ID'); ?></div>
		</div>
		<h4 class="cell text-center acym__list__settings__users__listing margin-top-1" v-show="listingError">{{ errorMessage }}</h4>
		<div class="cell text-center acym__list__settings__users__listing margin-top-1" v-show="listingLoading"><?php echo acym_loaderLogo(); ?></div>
		<div class="cell text-center acym__list__settings__users__listing margin-top-1" v-show="emptyListing"><?php echo acym_translation('ACYM_NO_SUBSCRIBERS_FOUND'); ?></div>
		<div v-show="displayListing"
			 class="grid-x cell acym__list__settings__users__listing"
			 v-infinite-scroll="loadMoreUsers"
			 :infinite-scroll-disabled="busy"
			 infinite-scroll-immediate-check="false">
			<div class="grid-x cell acym__listing__row" v-for="(user, index) in users">
				<div class="cell small-12 medium-6 acym__listing__title acym_word-break">
					<h6>{{ user.email }}</h6>
				</div>
				<div class="cell medium-4 small-10 acym_word-break">
					<span>{{ user.name }}</span>
				</div>
				<div class="medium-2 hide-for-small-only cell acym_word-break">
					<span>{{ user.id }}</span>
				</div>
			</div>
		</div>
	</div>
</div>
