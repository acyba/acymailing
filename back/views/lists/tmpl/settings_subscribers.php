<div class="grid-x acym__list__settings__subscribers acym__content" id="acym__list__settings__subscribers">
	<a name="subscribers"></a>
	<input type="hidden" id="subscribers_subscribed" value="<?php echo acym_escape(json_encode($data['subscribers'])); ?>" />
	<input type="hidden" id="requireConfirmation" value="<?php echo acym_escape($this->config->get('require_confirmation', 0)); ?>" />
	<h5 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_SUBSCRIBERS'); ?></h5>

	<div class="cell grid-x acym__list__settings__subscribers__search">
		<div class="cell medium-3"><input type="text" v-model="searchSubscribers" placeholder="<?php echo acym_translation('ACYM_SEARCH'); ?>"></div>
		<div class="cell medium-auto"></div>
		<div class="cell medium-3 grid-x">
            <?php
            $orderingOptions = [
                'id' => acym_strtolower(acym_translation('ACYM_ID')),
                'email' => acym_translation('ACYM_EMAIL'),
                'name' => acym_translation('ACYM_NAME'),
                'subscription_date' => acym_translation('ACYM_SUBSCRIPTION_DATE'),
                'confirmed' => acym_translation('ACYM_CONFIRMED'),
            ];
            $orderingOptionsClean = acym_escape(json_encode($orderingOptions));
            ?>
			<div class="cell small-11 acym__list__settings__subscribers__order">
				<select2
						name="users_ordering"
						v-if="<?php echo acym_isAdmin() ? 'true' : 'false'; ?>"
						value="<?php echo $data['ordering']; ?>"
						:options="<?php echo $orderingOptionsClean; ?>"
						v-model="users_ordering"></select2>
				<select name="users_ordering" v-model="users_ordering" v-if="<?php echo acym_isAdmin() ? 'false' : 'true'; ?>">
					<option v-for="(option, index) in <?php echo $orderingOptionsClean; ?>" :selected="index === '<?php echo $data['ordering']; ?>'" :value="index">{{ option }}
					</option>
				</select>
			</div>
			<div class="cell small-1 acym__list__settings__subscribers__order">
				<i class="acym__listing__ordering__sort-order <?php echo $data['classSortOrder']; ?>" aria-hidden="true" @click="sortOrdering"></i>
				<input type="hidden" name="users_ordering_sort_order" value="<?php echo $data['orderingSortOrder']; ?>">
			</div>
		</div>
		<div v-show="displayedSubscribers.length > 0" style="display:none;" class="cell grid-x acym__listing">
			<div class="grid-x cell acym__listing__header hide-for-medium-only hide-for-small-only">
				<div class="cell acym__listing__header__title large-4"><?php echo acym_translation('ACYM_EMAIL'); ?></div>
				<div class="cell acym__listing__header__title" :class="requireConfirmation==1?'large-3':'large-4'"><?php echo acym_translation('ACYM_NAME'); ?></div>
				<div class="cell large-2 acym__listing__header__title"><?php echo acym_translation('ACYM_SUBSCRIPTION_DATE'); ?></div>
				<div class="cell large-1 acym__listing__header__title" v-show="requireConfirmation==1"><?php echo acym_translation('ACYM_STATUS'); ?></div>
				<div class="cell large-2 acym__listing__header__title"></div>
			</div>
			<div class="grid-x cell acym__list__settings__subscribers__listing" v-infinite-scroll="loadMoreSubscriber" :infinite-scroll-disabled="busy">
				<div class="grid-x cell acym__listing__row" v-for="(sub, index) in displayedSubscribers">
					<div class="cell small-12 large-4 acym__listing__title acym_word-break">
						<h6 :class="sub.confirmed==1 || requireConfirmation==0?'':'acym__color__dark-gray'">
							{{ sub.email }}
							<span class="acym__hover__user_info" :data-id="sub.id">
								<?php echo acym_info('<i class="acymicon-circle-o-notch acymicon-spin"></i>'); ?>
							</span>
						</h6>
					</div>
					<div class="cell medium-7 small-10 acym_word-break" :class="requireConfirmation==1?'large-3':'large-4'">
						<span :class="sub.confirmed==1 || requireConfirmation==0?'':'acym__color__dark-gray'">{{ sub.name }}</span>
					</div>
					<div class="large-2 hide-for-medium-only hide-for-small-only cell acym_word-break">
						<span :class="sub.confirmed==1 || requireConfirmation==0?'':'acym__color__dark-gray'">{{ sub.subscription_date }}</span>
					</div>
					<div class="cell large-1 hide-for-medium-only hide-for-small-only acym_word-break" v-show="requireConfirmation==1 && sub.confirmed==0">
							<span class="acym__color__dark-gray">
								<?php echo acym_translation('ACYM_PENDING'); ?>
							</span>
					</div>
					<div class="cell large-1 hide-for-medium-only hide-for-small-only acym_word-break" v-show="requireConfirmation==1 && sub.confirmed==1">
						<span><?php echo acym_translation('ACYM_CONFIRMED'); ?></span>
					</div>
					<div class="large-2 medium-5 small-2 cell acym__list__settings__subscribers__users--action acym__list__action--unsubscribe_one acym_word-break"
						 v-on:click="unsubscribeUser(sub.id)">
						<i class="acymicon-times-circle"></i><span class="hide-for-small-only"><?php echo acym_strtolower(acym_translation('ACYM_UNSUBSCRIBE')); ?></span>
					</div>
				</div>
			</div>
		</div>
		<div class="cell grid-x align-center acym__list__subscribers__loading margin-top-1" v-show="loading">
			<div class="cell text-center acym__list__subscribers__loading__title"><?php echo acym_translation('ACYM_WE_ARE_LOADING_YOUR_DATA'); ?></div>
			<div class="cell grid-x shrink margin-top-1"><?php echo $data['svg']; ?></div>
		</div>
		<div class="grid-x cell acym__listing v-align-top acym__list__settings__subscribers__listing" v-show="displayedSubscribers.length==0 && !loading" style="display:none;">
			<span><?php echo acym_translation('ACYM_NO_SUBSCRIBERS_FOUND'); ?></span>
		</div>
	</div>
