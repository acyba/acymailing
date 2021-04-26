<div class="cell grid-x acym__content" id="acym__plugin__installed">
	<form id="acym_form"
		  action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>"
		  method="post"
		  name="acyForm"
		  class="cell grid-x acym__form__campaign__edit"
		  data-abide>
		<input type="hidden" name="all__plugins" id="acym__plugins__all" value="<?php echo empty($data['plugins']) ? '[]' : acym_escape($data['plugins']); ?>">
		<input type="hidden" name="plugin__folder_name" value="">
        <?php
        $workflow = $data['workflowHelper'];
        echo $workflow->display($data['tabs'], $data['tab'], false, true);
        ?>
		<div id="acym__plugin__installed__application" class="cell grid-x">
            <?php if (strpos($data['plugins'], '"total":"0"') !== false) { ?>
				<div class="cell grid-x align-center">
					<h2 class="cell text-center acym__title__primary__color"><?php echo acym_translation('ACYM_YOU_DONT_HAVE_ADD_ONS'); ?></h2>
					<a href="<?php echo acym_completeLink('plugins&task=available'); ?>"
					   class="cell shrink button  text-center margin-top-1 margin-bottom-2"><?php echo acym_translation('ACYM_DOWNLOAD_MY_FIRST_ONE'); ?></a>
				</div>
            <?php } else { ?>
				<div class="cell grid-x grid-margin-x margin-bottom-1">
					<input type="text" class="cell medium-3 margin-bottom-0" v-model="search" placeholder="<?php echo acym_translation('ACYM_SEARCH'); ?>">
					<div class="cell medium-4 large-2">
						<select2 :name="'acym__plugins__type'" :options="<?php echo acym_escape(json_encode($data['types'])); ?>" v-model="type"></select2>
					</div>
					<div class="cell medium-4 large-2">
						<select2 :name="'acym__plugins__level'" :options="<?php echo acym_escape(json_encode($data['level'])); ?>" v-model="level"></select2>
					</div>
					<div class="cell grid-x medium-5 align-right">
						<div class="cell medium-2"></div>
						<button type="button" class="acy_button_submit button button-secondary acym_vcenter" data-task="checkUpdates">
							<i class="acymicon-autorenew"></i><?php echo acym_translation('ACYM_CHECK_FOR_UPDATES'); ?>
						</button>
					</div>
				</div>
				<div class="cell grid-x" v-show="noPluginTodisplay" style="display: none;">
					<h2 class="cell text-center acym__title__primary__color"><?php echo acym_translation('ACYM_NO_ADD_ONS_TO_DISPLAY'); ?></h2>
				</div>
				<div class="cell grid-x margin-bottom-2" v-show="!noPluginTodisplay">
					<div class="cell grid-x align-center text-center acym__plugin__available__loader__page margin-top-3 margin-bottom-3" v-show="loading">
                        <?php echo acym_loaderLogo(); ?>
					</div>
					<div class="cell grid-x grid-margin-x grid-margin-y"
						 v-show="!loading"
						 style="display: none;"
						 v-infinite-scroll="loadMorePlugins"
						 :infinite-scroll-disabled="busy">
						<div class="acym__plugins__card cell grid-x xlarge-3 large-4 medium-6"
							 :id="'acym__plugins__card__' + plugin.folder_name"
							 v-for="(plugin, index) in displayedPlugins"
							 :key="plugin">
							<div v-show="!showSettings[plugin.id]" class="acym__plugins__info__container">
								<button v-if="plugin.type == 'ADDON'" @click="deletePlugin(plugin.id)" type="button" class="acym__plugins__button__delete">
									<i class="acymicon-trash-o"></i>
								</button>
								<div class="acym__plugins__card__params_type shrink cell">{{ plugin.category }}</div>
								<div class="acym__plugins__card__image margin-bottom-1 cell grid-x align-center">
									<img :src="imageUrl(plugin.folder_name, plugin.type)" alt="plugin image" class="cell">
								</div>
								<div class="acym__plugins__card__params cell grid-x">
									<div class="cell grid-x acym_vcenter acym__plugins__card__params__title-line">
										<h2 class="cell medium-10 acym__plugins__card__params__title acym_text_ellipsis" :title="plugin.title">{{ plugin.title }}</h2>
										<a target="_blank" :href="documentationUrl(plugin.folder_name)" class="acym__plugins__documentation cell medium-1">
											<i class="acymicon-book"></i>
										</a>
										<i v-if="plugin.settings && plugin.settings!='not_installed'"
										   @click="toggleSettings(plugin.folder_name)"
										   class="acymicon-cog cell shrink acym__plugins__settings__toggle cursor-pointer"></i>
										<i v-if="plugin.settings && plugin.settings=='not_installed'"
										   class="acymicon-cog cell shrink acym__plugins__settings__toggle__blocked cursor-pointer acym__color__medium-gray acym__tooltip">
											<span class="acym__tooltip__text"><?php echo acym_translation('ACYM_SETTINGS_AVAILABLE_INSTALLED_EXTENSION'); ?></span>
										</i>
									</div>
									<div ref="plugins" :class="isOverflown(index)" class="acym__plugins__card__params_desc cell" v-html="plugin.description"></div>

									<div v-if="plugin.type == 'ADDON'" class="acym__plugins__card__actions cell grid-x acym_vcenter" v-show="rightLevel(plugin.level)">
										<div class="cell grid-x acym_vcenter medium-8">
											<span class="cell shrink"><?php echo acym_translation('ACYM_ENABLED'); ?>:</span>
											<vue-switch :plugin="plugin" :ischecked="isActivated(plugin.active)"></vue-switch>
										</div>
										<div class="cell grid-x acym_vcenter medium-4 align-right" v-show="plugin.uptodate === '0'">
											<button data-acym-tooltip="<?php echo acym_translation('ACYM_UPDATE'); ?>"
													type="button"
													class="acym__plugins__button shrink acym__plugins__button__update cell text-center"
													@click="updatePlugin(plugin)">
												<span v-show="!updating[plugin.id]"><i class="acymicon-file_download"></i></span>
												<span v-show="updating[plugin.id]"><?php echo acym_loaderLogo(); ?></span>
											</button>
										</div>
									</div>
								</div>
							</div>
							<div class="cell grid-x acym__plugins__settings__container" v-if="plugin.settings">
								<h3 class="cell acym__title__primary__color text-center">{{ plugin.title }}</h3>
								<div class="acym__plugins__settings__options__container cell">
									<div class="cell grid-x acym_vcenter acym__plugins__settings-one" v-for="(settings, index) in plugin.settings" :key="index" v-html="settings">
									</div>
								</div>
								<div class="cell grid-x align-center">
									<button type="button"
											@click="toggleSettings(plugin.folder_name)"
											class="cell medium-5 button acym__button__cancel"><?php echo acym_translation('ACYM_CANCEL'); ?></button>
									<div class="cell medium-1 hide-for-small-only"></div>
									<button type="button" class="cell medium-5 button acy_button_submit" data-task="saveSettings">
                                        <?php echo acym_translation('ACYM_SAVE'); ?>
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
                <?php
            } ?>
		</div>
        <?php acym_formOptions(); ?>
	</form>
</div>
