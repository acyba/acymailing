<?php
$hideForScroll = '';
if (!$edition && isset($form->settings['display']['scroll']) && $form->settings['display']['scroll'] != 0) {
    $hideForScroll = 'style="display: none;"';
}
?>
<div id="acym_fulldiv_<?php echo $form->form_tag_name; ?>"
	 class="acym__subscription__form__popup__overlay acym__subscription__form-erase"
    <?php echo $hideForScroll; ?>>
	<div class="acym__subscription__form__popup">
		<div class="acym__subscription__form__popup__close acymicon-remove"></div>
        <?php
        if ($edition) {
            echo '<form action="#" onsubmit="return false;" id="'.$form->form_tag_name.'">';
        } else {
            $isButton = $form->settings['display']['display_action'] === 'yes';
            if ($isButton) {
                $cookieExpirationAttr = 'acym-data-cookie="0"';
            } else {
                $cookieExpirationAttr = empty($form->settings['cookie']['cookie_expiration']) ? 'acym-data-cookie="1"'
                    : 'acym-data-cookie="'.$form->settings['cookie']['cookie_expiration'].'"';
            }
            echo '<form acym-data-id="'.$form->id.'" '.$cookieExpirationAttr.' action="'.$form->form_tag_action.'" id="'.$form->form_tag_name.'" name="'.$form->form_tag_name.'" enctype="multipart/form-data" onsubmit="return submitAcymForm(\'subscribe\',\''.$form->form_tag_name.'\', \'acymSubmitSubForm\')">';
        }
        if (in_array($form->settings['style']['position'], ['image-top', 'image-left'])) {
            if (!empty($form->settings['message']['text']) && $form->settings['message']['position'] === 'before-image') {
                echo '<p id="acym__subscription__form__popup-text">'.nl2br(acym_translation($form->settings['message']['text'])).'</p>';
            }
            include acym_getPartial('forms', 'image');
        }
        echo '<div class="acym__subscription__form__popup__fields-button">';
        include acym_getPartial('forms', 'fields');
        if (!empty($form->settings['message']['text']) && $form->settings['message']['position'] == 'before-button') {
            echo '<p id="acym__subscription__form__popup-text">'.nl2br(acym_translation($form->settings['message']['text'])).'</p>';
        }
        include acym_getPartial('forms', 'button');
        echo '</div>';
        if (in_array($form->settings['style']['position'], ['image-bottom', 'image-right'])) {
            if (!empty($form->settings['message']['text']) && $form->settings['message']['position'] == 'before-image') {
                echo '<p id="acym__subscription__form__popup-text">'.nl2br(acym_translation($form->settings['message']['text'])).'</p>';
            }
            include acym_getPartial('forms', 'image');
        }
        include acym_getPartial('forms', 'hidden_params');
        ?>
		</form>
	</div>
</div>
<style>
	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__popup__overlay{
		display: <?php echo $edition ? 'inline' : 'none';?>;
		position: fixed;
		top: 0;
		bottom: 0;
		right: 0;
		left: 0;
		background-color: rgba(200, 200, 200, .5);
		z-index: 999999;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup__close{
		position: absolute;
		top: 10px;
		right: 10px;
		font-weight: bold;
		font-size: 1rem;
		cursor: pointer;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup{
		position: fixed;
		left: 50%;
		transform: translate(-50%, -50%);
		top: 50%;
		padding: <?php echo $form->settings['style']['padding']['height'];?>px <?php echo $form->settings['style']['padding']['width'];?>px;
		background-color: <?php echo $form->settings['style']['background_color'];?>;
		color: <?php echo $form->settings['style']['text_color'];?> !important;
		background-image: url("<?php echo $form->settings['style']['background_image']; ?>");
		background-size: <?php echo $form->settings['style']['background_size']; ?>;
		background-position: <?php echo str_replace('_', ' ', $form->settings['style']['background_position']); ?>;
		background-repeat: <?php echo $form->settings['style']['background_repeat']; ?>;
		z-index: 999999;
		text-align: center;
		display: flex;
		justify-content: center;
		align-items: center;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .responseContainer{
		margin-bottom: 0 !important;
		padding: .4rem !important;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup <?php echo '#'.$form->form_tag_name;?>{
		margin: 0;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .acym__subscription__form__fields, <?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .acym__subscription__form__button{
		display: block;
		width: 100%;
		margin: 1rem 0 !important;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .acym__subscription__form__fields input:not([type="radio"]):not([type="checkbox"]), <?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .acym__subscription__form__fields label{
		display: block;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .acym__subscription__form__fields input[type="radio"], <?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .acym__subscription__form__fields input[type="checkbox"]{
		margin-left: 5px;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .acym__subscription__form__fields .acym__subscription__form__lists{
		display: block;
		width: 100%;
		margin: 1rem 10px !important;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .acym__subscription__form__fields .acym__user__edit__email{
		margin: auto;
	}

	<?php if(!empty($form->settings['message']['color'])) { ?>
	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>#acym__subscription__form__popup-text{
		color: <?php echo $form->settings['message']['color']; ?>;
	}

	<?php } ?>

	<?php if (in_array($form->settings['style']['position'], ['image-right', 'image-left'])){?>
	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup <?php echo '#'.$form->form_tag_name;?>{
		display: flex;
		justify-content: center;
		align-items: center
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup__fields-button, <?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__image{
		display: inline-block;
	}

	<?php }?>

</style>
<?php if (!$edition) { ?>
	<script type="text/javascript">
        const acymBackupFormTimeout<?php echo $form->form_tag_name; ?> = setTimeout(() => {
            acym_initPopupDisplay<?php echo $form->form_tag_name; ?>(true);
        }, 1000);

        window.addEventListener('DOMContentLoaded', function () {
            clearTimeout(acymBackupFormTimeout<?php echo $form->form_tag_name; ?>);
            acym_initPopupDisplay<?php echo $form->form_tag_name; ?>();
        });

        function acym_initPopupDisplay<?php echo $form->form_tag_name; ?>(addedDelayForBackup = false) {
            const acym_popupForm = document.querySelector('#acym_fulldiv_<?php echo $form->form_tag_name; ?>.acym__subscription__form__popup__overlay');

            if (!acym_popupForm) {
                return;
            }

            const isDisplayButton = <?php echo $isButton ? 'true' : 'false'; ?>;

            function acym_closePopupform<?php echo $form->form_tag_name;?>(element) {
                element.style.display = 'none';

                if (isDisplayButton) {
                    return;
                }

                let expirationDate = new Date();
                expirationDate.setDate(expirationDate.getDate() + <?php echo empty($form->settings['cookie']['cookie_expiration']) ? 1
                    : $form->settings['cookie']['cookie_expiration'];?>);
                document.cookie = 'acym_form_<?php echo $form->id; ?>=' + Date.now() + ';expires=' + expirationDate.toUTCString() + ';path=/';
            }

            acym_popupForm.addEventListener('click', function (event) {
                if (event.target.closest('.acym__subscription__form__popup') === null) {
                    acym_closePopupform<?php echo $form->form_tag_name; ?>(this);
                }
            });
            document.querySelector('#acym_fulldiv_<?php echo $form->form_tag_name; ?> .acym__subscription__form__popup__close').addEventListener('click', function (event) {
                acym_closePopupform<?php echo $form->form_tag_name; ?>(event.target.closest('.acym__subscription__form__popup__overlay'));
            });

            if (isDisplayButton) {
                displayByButton();
            } else {
                displayByDelayAndScroll();
            }

            function displayByButton() {
                const button = document.querySelector('#<?php echo $form->settings['display']['button'];?>');

                if (!button) {
                    console.error('Could not find the button with the ID <?php echo $form->settings['display']['button'];?>');
                    return;
                }

                button.addEventListener('click', function () {
                    acym_popupForm.style.display = 'inline';
                });
            }

            function displayByDelayAndScroll() {
                const delayDisplay = parseInt(<?php echo $form->settings['display']['delay']; ?>);
                const scrollPercentLimit = parseInt(<?php echo $form->settings['display']['scroll']; ?>);
                let windowSize;
                let browserHeight;
                let delayRemaining = false;
                if (delayDisplay > 0) {
                    delayRemaining = true;
                }
                let scrollRemaining = false;
                if (scrollPercentLimit > 0) {
                    scrollRemaining = true;
                }

                windowSize = document.getElementsByTagName('body')[0].clientHeight;
                browserHeight = document.documentElement.clientHeight;
                if (windowSize <= browserHeight && !delayRemaining) {
                    scrollRemaining = false;
                    acym_popupForm.style.display = 'inline';
                }

                function displayAcymPopupForm() {
                    let scrollPercent = Math.round((window.scrollY) / (windowSize - browserHeight) * 100);
                    if (scrollPercent >= scrollPercentLimit) {
                        scrollRemaining = false;
                        window.removeEventListener('scroll', displayAcymPopupForm);
                        if (!delayRemaining) {
                            if (acym_popupForm) {
                                acym_popupForm.style.display = 'inline';
                            }
                        }
                    }
                }

                window.addEventListener('scroll', displayAcymPopupForm);

                let delayInMs = delayDisplay * 1000;
                if (addedDelayForBackup && delayDisplay > 1000) {
                    delayInMs -= 1000;
                }

                setTimeout(function () {
                    if (acym_popupForm !== null) {
                        delayRemaining = false;
                        if (!scrollRemaining) {
                            acym_popupForm.style.display = 'inline';
                        }
                    }
                }, delayInMs);
            }
        }
	</script>
    <?php
    include acym_getPartial('forms', 'cookie');
}
?>
