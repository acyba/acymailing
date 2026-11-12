<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<div class="acym__unsubscribe__page grid-x grid-padding-x">
    <?php if ($data['displayProfileImage']) { ?>
		<div class="acym__unsubscribe__image__container cell hide-for-small-only">
            <?php if (!empty($this->config->get('profile_image'))) { ?>
				<img class="cell padding-3"
					 src="<?php echo acym_escapeUrl($this->config->get('profile_image')); ?>"
					 alt="">
            <?php } else { ?>
				<div class="cell padding-3 acym__unsubscribe__image__svg">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Internal SVG file.
                    echo $data['svgImage'];
                    ?>
				</div>
            <?php } ?>
		</div>
    <?php } ?>
    <?php $classContainer = $data['displayProfileImage'] ? '' : 'no-image'; ?>
	<div class="acym__unsubscribe__form__container cell <?php echo acym_escape($classContainer); ?>">
		<div class="acym__unsubscribe__container cell">
			<div class="acy__unsubscribe__form__header margin-bottom-3">
				<div class="acym__unsubscribe__form__header__image cell padding-bottom-1">
                    <?php if (!empty($this->config->get('profile_logo'))) { ?>
						<img class="cell" src="<?php echo acym_escapeUrl($this->config->get('profile_logo')); ?>" alt="<?php echo acym_escape(acym_getCMSConfig('sitename')); ?>">
                    <?php } ?>
				</div>
				<h1 class="acym__title acym__unsubscribe__title margin-bottom-1">
                    <?php
                    $profileTitle = $this->config->get('profile_title', '');
                    echo acym_escapeHtml(empty($profileTitle) ? acym_translation('ACYM_MODIFY_MY_PROFILE') : $profileTitle);
                    ?>
				</h1>
			</div>
			<div class="acym__profile__container cell margin-bottom-1">
                <?php include acym_getView('frontusers', 'profile'); ?>
			</div>
            <?php if ($this->config->get('display_built_by', 1)) { ?>
				<img class="cell acym__powered__by__image"
					 src="<?php echo acym_escapeUrl(ACYM_IMAGES.'editor/poweredby_black.png'); ?>"
					 alt="Powered by AcyMailing">
            <?php } ?>
		</div>
	</div>
</div>

<style>
	#acym_wrapper .acym__profile__container .switch{
		--color-switch: <?php echo acym_escapeHtml($data['unsubscribeColor']); ?>;
	}

	.acym__profile__container .button-primary{
		background-color: <?php echo acym_escapeHtml($data['unsubscribeColor']); ?> !important;
		border-color: <?php echo acym_escapeHtml($data['unsubscribeColor']); ?> !important;
	}

	.acym__profile__container .button-primary:hover{
		background-color: <?php echo acym_escapeHtml($data['hoverColor']); ?> !important;
		border-color: <?php echo acym_escapeHtml($data['hoverColor']); ?> !important;
	}

	.acym__profile__container .button-secondary{
		color: <?php echo acym_escapeHtml($data['unsubscribeColor']); ?> !important;
		border-color: <?php echo acym_escapeHtml($data['unsubscribeColor']); ?> !important;
	}

	.acym__profile__container .button-secondary:hover{
		background-color: <?php echo acym_escapeHtml($data['hoverColor']); ?> !important;
		border-color: <?php echo acym_escapeHtml($data['hoverColor']); ?> !important;
		color: #ffffff !important;
	}
</style>
