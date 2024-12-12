<div class="grid-x acym__list__settings__subscribers acym__content" id="acym__list__settings__subscribers">
	<a name="subscribers"></a>
	<input type="hidden" name="list[name]" value="<?php echo acym_escape($data['listInformation']->name); ?>" />
	<input type="hidden" id="subscribers_subscribed" value="<?php echo acym_escape(json_encode($data['subscribers'])); ?>" />
	<input type="hidden" id="requireConfirmation" value="<?php echo acym_escape($this->config->get('require_confirmation', 0)); ?>" />
	<h5 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_SUBSCRIBERS'); ?></h5>

	<div class="cell grid-x acym__list__settings__subscribers__search">
		<div class="cell medium-3"><input type="text" v-model="searchSubscribers" placeholder="<?php echo acym_translation('ACYM_SEARCH'); ?>"></div>
		<div class="cell medium-auto"></div>
		<div v-show="displayedSubscribers.length > 0" style="display:none;" class="cell grid-x acym__listing">
			<div class="grid-x cell acym__listing__header hide-for-medium-only hide-for-small-only">
				<div @click="orderByTable('id')" class="cell acym__listing__header__title large-1 cursor-pointer acym_vcenter list_order_gap">
					ID
					<i v-show="columnOrderSelectedCss == 'id'"
					   :class="[(columnOrderSelected == 'id' ? 'acymicon-sort-amount-desc' : 'acymicon-sort-amount-asc'),(columnOrderSelectedCss == 'id' ? 'acymicon-sort-amount-asc' : 'acymicon-sort-amount-desc') ] "
					   class="dataTable__icon__size"></i>
				</div>
				<div @click="orderByTable('email')" class="cell acym__listing__header__title large-3 cursor-pointer acym_vcenter list_order_gap">
                    <?php echo acym_translation('ACYM_EMAIL'); ?>
					<i v-show="columnOrderSelectedCss == 'email'"
					   :class=" columnOrderSelected == 'email' ? 'acymicon-sort-amount-desc' : 'acymicon-sort-amount-asc' "
					   class="dataTable__icon__size"></i>
				</div>
				<div @click="orderByTable('name')"
					 class="cell acym__listing__header__title cursor-pointer acym_vcenter list_order_gap"
					 :class="requireConfirmation==1?'large-2':'large-3'">
                    <?php echo acym_translation('ACYM_NAME'); ?>
					<i v-show="columnOrderSelectedCss == 'name'"
					   :class=" columnOrderSelected == 'name' ? 'acymicon-sort-amount-desc' : 'acymicon-sort-amount-asc' "
					   class="dataTable__icon__size"></i>
				</div>
				<div @click="orderByTable('subscription_date')"
					 class="cell large-2 acym__listing__header__title cursor-pointer acym_vcenter list_order_gap">
                    <?php echo acym_translation('ACYM_SUBSCRIPTION_DATE'); ?>
					<i v-show="columnOrderSelectedCss == 'subscription_date'"
					   :class=" columnOrderSelected == 'subscription_date' ? 'acymicon-sort-amount-desc' : 'acymicon-sort-amount-asc' "
					   class="dataTable__icon__size"></i>
				</div>
				<div @click="orderByTable('confirmed')"
					 class="cell large-1 acym__listing__header__title cursor-pointer acym_vcenter list_order_gap"
					 v-show="requireConfirmation==1">
                    <?php echo acym_translation('ACYM_STATUS'); ?>
					<i v-show="columnOrderSelectedCss == 'confirmed'"
					   :class=" columnOrderSelected == 'confirmed' ? 'acymicon-sort-amount-desc' : 'acymicon-sort-amount-asc' "
					   class="dataTable__icon__size"></i>
				</div>
				<div class="cell large-2 acym__listing__header__title"></div>
			</div>
			<div class="grid-x cell acym__list__settings__subscribers__listing"
				 v-infinite-scroll="loadMoreSubscriber"
				 :infinite-scroll-disabled="busy"
				 infinite-scroll-distance="10">
				<div class="grid-x cell align-middle acym__listing__row" v-for="(sub, index) in displayedSubscribers">
					<div class="cell medium-7 small-10 acym_word-break large-1">
						<span :class="sub.confirmed==1 || requireConfirmation==0?'':'acym__color__dark-gray'">{{ sub.id }}</span>
					</div>
					<div class="cell small-12 large-3 acym__listing__title acym_word-break">
						<h6 :class="sub.confirmed==1 || requireConfirmation==0?'':'acym__color__dark-gray'">
							{{ sub.email }}
							<span class="acym__hover__user_info" :data-id="sub.id">
								<?php echo acym_info('<i class="acymicon-circle-o-notch acymicon-spin"></i>'); ?>
							</span>
						</h6>
					</div>
					<div class="cell medium-7 small-10 acym_word-break" :class="requireConfirmation==1?'large-2':'large-3'">
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
