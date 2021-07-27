<div class="cell grid-x acym__content" id="acym__plugin__available">
	<input type="hidden" id="acym__plugin__available__plugins" value="<?php echo acym_escape(ACYM_AVAILABLE_PLUGINS); ?>" />
	<form id="acym_form"
		  action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>"
		  method="post"
		  name="acyForm"
		  class="cell grid-x acym__form__campaign__edit"
		  data-abide>
        <?php
        $workflow = $data['workflowHelper'];
        echo $workflow->display($data['tabs'], $data['tab'], false, true);
        ?>
		<div id="acym__plugin__available__application" class="cell grid-x">
			<div class="cell grid-x grid-margin-x">
				<input type="text" class="cell medium-3" v-model="search" placeholder="<?php echo acym_translation('ACYM_SEARCH'); ?>">
				<div class="cell medium-4 large-2">
					<select2 :name="'acym__plugins__type'" :options="<?php echo acym_escape(json_encode($data['types'])); ?>" v-model="type"></select2>
				</div>
				<div class="cell medium-4 large-2">
					<select2 :name="'acym__plugins__level'" :options="<?php echo acym_escape(json_encode($data['level'])); ?>" v-model="level"></select2>
				</div>
			</div>
			<div class="cell grid-x" v-show="noPluginTodisplay" style="display: none;">
				<h2 class="cell text-center acym__title__primary__color"><?php echo acym_translation('ACYM_NO_ADD_ONS_TO_DISPLAY'); ?></h2>
			</div>
			<div class="cell grid-x margin-bottom-2">
				<div class="cell grid-x align-center text-center acym__plugin__available__loader__page margin-top-3 margin-bottom-3" v-show="loading">
                    <?php echo acym_loaderLogo(); ?>
				</div>
				<div class="cell grid-x grid-margin-x grid-margin-y" v-show="!loading" style="display: none;" v-infinite-scroll="loadMorePlugins" :infinite-scroll-disabled="busy">
					<div class="acym__plugins__card cell grid-x xlarge-3 large-4 medium-6" v-for="(plugin, index) in displayedPlugins">
						<div class="acym__plugins__card__params_type shrink cell">{{ plugin.category }}</div>
						<div class="acym__plugins__card__image margin-bottom-1 cell grid-x align-center">
							<img :src="imageUrl(plugin.image)" alt="plugin image" class="cell">
						</div>
						<div class="acym__plugins__card__params cell grid-x">
							<div class="cell grid-x acym_vcenter acym__plugins__card__params__title-line">
								<h2 class="cell medium-10 acym__plugins__card__params__title acym_text_ellipsis" :title="plugin.name">{{ plugin.name }}</h2>
								<a target="_blank" :href="plugin.documentation" class="acym__plugins__documentation cell medium-2"><i class="acymicon-book"></i></a>
							</div>
							<div ref="plugins" :class="isOverflown(index)" class="acym__plugins__card__params_desc cell" v-html="plugin.description"></div>
							<div class="cell grid-x acym__plugins__card__actions">
								<div class="cell grid-x align-center" v-show="!rightLevel(plugin.level)">
									<div class="cell grid-x">
										<button :data-acym-tooltip="<?php echo acym_escapeDB(acym_translation('ACYM_YOU_DONT_HAVE_THE_RIGHT_LEVEL')); ?> + ucfirst(plugin.level)"
												type="button"
												class="acym__plugins__button acym__plugins__button-disabled button button-secondary acym__plugins__button__purchase cell text-center cell small-5">
                                            <?php echo acym_translation('ACYM_DOWNLOAD'); ?><i class="acymicon-file_download"></i>
										</button>
										<div class="cell auto"></div>
										<a target="_blank"
										   href="<?php echo ACYM_ACYMAILLING_WEBSITE; ?>pricing"
										   class="acym__plugins__button cell small-5 acym__plugins__button__purchase text-center button button-secondary">
                                            <?php echo acym_translation('ACYM_PURCHASE'); ?>
											<i class="acymicon-cart-arrow-down"></i>
										</a>
									</div>
								</div>
								<div v-if="!installed[plugin.image]" v-show="rightLevel(plugin.level)" class="cell grid-x acym__plugins__card__actions">
									<button v-show="'<?php echo ACYM_CMS; ?>' == 'joomla'" type="button" class="acym__plugins__button cell text-center button button-secondary" @click="download(plugin)">
										<span v-show="!downloading[plugin.image]">
											<?php echo acym_translation('ACYM_DOWNLOAD'); ?>
											<i class="acymicon-file_download"></i>
										</span>
										<span v-show="downloading[plugin.image]"><?php echo acym_loaderLogo(); ?></span>
									</button>
									<a v-show="'<?php echo ACYM_CMS; ?>' == 'wordpress'"
									   target="_blank"
									   :href="plugin.downloadlink"
									   class="acym__plugins__button cell acym__plugins__button__purchase text-center button button-secondary">
                                        <?php echo acym_translation('ACYM_DOWNLOAD'); ?>
										<i class="acymicon-file_download"></i>
									</a>
								</div>
								<div v-if="installed[plugin.image]" class="cell grid-x acym__plugins__card__actions">
									<button type="button" class="acym__plugins__button cell text-center acym__plugins__button-disabled button button-secondary">
                                        <?php echo acym_translation('ACYM_ADD_ON_SUCCESSFULLY_INSTALLED'); ?>
										<i class="acymicon-check"></i>
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
