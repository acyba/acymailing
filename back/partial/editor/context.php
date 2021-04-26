<div id="acym__wysid__right__toolbar__current-block" style="display: none;" class="grid-padding-x cell acym__wysid__right__toolbar--menu">
	<p class="acym__wysid__right__toolbar__current-block__empty cell text-center margin-top-1"><?php echo acym_translation('ACYM_NO_BLOCK_SELECTED'); ?></p>
	<div id="acym__wysid__context__block" class="grid-x cell padding-1 acym__wysid__context__modal" style="display: none">
		<p class="cell acym__wysid__right__toolbar__p acym__title"><?php echo acym_translation('ACYM_BACKGROUND'); ?><i class="acymicon-keyboard_arrow_up"></i></p>
		<div class="cell grid-x acym__wysid__context__modal__container">
			<div class="cell grid-x acym_vcenter">
				<label class="cell small-6"><?php echo acym_translation('ACYM_BACKGROUND_COLOR'); ?></label>
				<input type="text" id="acym__wysid__context__block__background-color">
			</div>
			<div class="cell grid-x acym_vcenter">
				<label class="cell small-6"><?php echo acym_translation('ACYM_BACKGROUND_IMAGE'); ?></label>
				<i class="acymicon-insert_photo acym__color__light-blue cursor-pointer" id="acym__wysid__context__block__background-image"></i>
				<i class="acymicon-close acym__color__red cursor-pointer" style="display: none" id="acym__wysid__context__block__background-image__remove"></i>
			</div>
			<div class="cell grid-x acym_vcenter">
                <?php echo acym_switch('transparent_background', 0, acym_translation('ACYM_TRANSPARENT_BACKGROUND'), [], 'small-6 acym__wysid__context__block__transparent__bg');
                ?>
			</div>
		</div>
		<p class="cell acym__wysid__right__toolbar__p acym__title"><?php echo acym_translation('ACYM_PADDING'); ?><i class="acymicon-keyboard_arrow_up"></i></p>
		<div class="cell grid-x acym__wysid__context__modal__container">
			<div class="cell grid-x align-center margin-bottom-1">
				<input type="number" min="0" class="cell small-2 acym__wysid__context__block__padding" data-block-padding="top">
			</div>
			<div class="cell grid-x align-center acym_vcenter margin-bottom-1">
				<input type="number" min="0" class="cell small-2 acym__wysid__context__block__padding" data-block-padding="left">
				<div class="small-4 cell acym__wysid__context__block__padding__exemple"></div>
				<input type="number" min="0" class="cell small-2 acym__wysid__context__block__padding" data-block-padding="right">
			</div>
			<div class="cell grid-x align-center">
				<input type="number" min="0" class="cell small-2 acym__wysid__context__block__padding" data-block-padding="bottom">
			</div>
		</div>
		<p class="cell acym__wysid__right__toolbar__p acym__wysid__context__modal__container--structure acym__title"><?php echo acym_translation('ACYM_STRUCTURE'); ?>
			<i class="acymicon-keyboard_arrow_up"></i></p>
		<div class="cell grid-x acym__wysid__context__modal__container acym__wysid__context__modal__container--structure acym__wysid__context__modal__container--structure--container">
			<div class="cell grid-x grid-margin-x acym_vcenter">
				<h6 class="cell shrink"><?php echo acym_translation('ACYM_RESIZE_COLUMNS_OF_ROW'); ?></h6>
				<div class="cell auto hide-for-medium-only hide-for-small-only"></div>
				<a href="<?php echo ACYM_DOCUMENTATION; ?>" target="_blank"><i class="acymicon-book"></i></a>
			</div>
			<div class="grid-x cell acym__wysid__context__modal__container__block-settings grid-margin-y acym-grid-margin-x">
			</div>
			<div class="cell grid-x acym__wysid__context__modal__block-background acym_vcenter margin-top-1">
			</div>
			<div class="cell grid-x acym__wysid__context__modal__block-padding">
			</div>
		</div>
		<p class="cell acym__wysid__right__toolbar__p acym__title"><?php echo acym_translation('ACYM_BORDER'); ?><i class="acymicon-keyboard_arrow_up"></i></p>
		<div class="cell grid-x acym__wysid__context__modal__container">
			<div class="cell grid-x">
				<label class="cell small-5"><?php echo acym_translation('ACYM_RADIUS'); ?></label>
				<input type="number" max="20" min="0" class="cell small-2 acym__wysid__context__block__border__actions" data-css="border-radius">
			</div>
			<div class="cell grid-x">
				<label class="cell small-5"><?php echo acym_translation('ACYM_WIDTH'); ?></label>
				<input type="number" max="10" min="0" class="cell small-2 acym__wysid__context__block__border__actions" data-css="border-width"">
			</div>
			<div class="cell grid-x acym_vcenter">
				<label class="cell small-5"><?php echo acym_translation('ACYM_COLOR'); ?></label>
				<input type="number" max="20" min="0" class="cell small-2" id="acym__wysid__context__block__border__color">
			</div>
		</div>
		<p class="cell acym__wysid__right__toolbar__p acym__title"><?php echo acym_translation('ACYM_ADVANCED_OPTIONS'); ?><i class="acymicon-keyboard_arrow_up"></i></p>
		<div class="cell grid-x acym__wysid__context__modal__container">
			<label class="cell small-5"><?php echo acym_translation('ACYM_HTML_ID').acym_info('ACYM_HTML_ID_DESC'); ?></label>
			<input type="text"
				   class="cell small-6"
				   id="acym__wysid__context__block__custom_id"
				   placeholder="<?php echo acym_escape(acym_translation('ACYM_HTML_ID')); ?>">
			<div class="cell grid-x acym__wysid__context__block__code-source">
				<label class="cell small-5 acym_vcenter">
                    <?php echo acym_translation('ACYM_EDIT_BLOCK_HTML').acym_info('ACYM_BECAREFUL_EDITING_SOURCE_CODE'); ?>
				</label>
				<div class="cell shrink acym_vcenter">
					<button type="button" class="button button-secondary" id="acym__wysid__context__block__edit-html">
                        <?php echo acym_translation('ACYM_EDIT_HTML'); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
    <?php include acym_getPartial('editor', 'context_text'); ?>
	<div id="acym__wysid__context__button" class="grid-x padding-1 acym__wysid__context__modal" style="display: none">
		<p class="cell acym__wysid__right__toolbar__p__open acym__wysid__right__toolbar__p acym__title">
            <?php echo acym_translation('ACYM_BUTTON_TYPE'); ?>
			<i class="acymicon-keyboard_arrow_up"></i>
		</p>
		<div class="grid-x cell acym__wysid__context__modal__container grid-margin-x">
			<button type="button"
					class="button-radio cell medium-4 acym__wysid__context__button--type"
					acym-button-radio-group="buttonType"
					acym-data-type="call-action"><?php echo acym_translation('ACYM_CALL_TO_ACTION'); ?></button>
            <?php
            echo acym_tooltip(
                '<button type="button" 
					class="button-radio cell acym__wysid__context__button--type" 
					acym-button-radio-group="buttonType" 
					acym-data-type="unsubscribe">'.acym_translation('ACYM_UNSUBSCRIBE').'</button>',
                acym_translation('ACYM_UNSUBSCRIBE_BUTTON_DESC'),
                'cell medium-4 grid-x'
            );

            echo acym_tooltip(
                '<button type="button" 
                	class="button-radio cell acym__wysid__context__button--type" 
					acym-button-radio-group="buttonType" 
					acym-data-type="confirm">'.acym_translation('ACYM_SUBSCRIPTION_CONFIRMATION').'</button>',
                acym_translation('ACYM_SUBSCRIPTION_CONFIRMATION_BUTTON_DESC'),
                'cell medium-4 grid-x'
            );
            ?>
		</div>
		<p class="cell acym__wysid__right__toolbar__p__open acym__wysid__right__toolbar__p acym__title">
            <?php echo acym_translation('ACYM_CONTENT'); ?><i class="acymicon-keyboard_arrow_up"></i>
		</p>
		<div class="cell grid-x acym__wysid__context__modal__container">
			<div class="grid-x cell acym__wysid__context__button__text__container">
				<label class="cell small-5"><?php echo acym_translation('ACYM_TEXT'); ?></label>
				<input id="acym__wysid__context__button__text" class="auto cell" type="text" placeholder="<?php echo acym_translation('ACYM_MY_BUTTON'); ?>">
			</div>
			<div class="grid-x cell acym__wysid__context__button__link__container">
				<div class="input-group cell grid-x">
					<label class="cell small-5" for="acym__wysid__context__button__link"><?php echo acym_translation('ACYM_LINK'); ?></label>
					<input id="acym__wysid__context__button__link" class="input-group-field cell auto" type="text" placeholder="https://www.example.com">
				</div>
			</div>
		</div>
		<p class="cell acym__wysid__right__toolbar__p__open acym__wysid__right__toolbar__p acym__title">
            <?php echo acym_translation('ACYM_FONT'); ?><i class="acymicon-keyboard_arrow_up"></i>
		</p>
		<div class="grid-x cell acym__wysid__context__modal__container">
			<div class="cell grid-x">
				<label class="cell small-5"><?php echo acym_translation('ACYM_FORMATTING'); ?></label>
				<i id="acym__wysid__context__button__italic" class="acymicon-format_italic small-1 cell acym__wysid__context__button__actions-i"></i>
				<i id="acym__wysid__context__button__bold" class="acymicon-format_bold small-1 cell acym__wysid__context__button__actions-i"></i>
			</div>
			<div class="cell grid-x">
				<label for="acym__wysid__context__button__font" class="cell small-5"><?php echo acym_translation('ACYM_FONT_FAMILY'); ?></label>
				<select id="acym__wysid__context__button__font-family" class="auto cell">
					<option style="font-family: 'Andale Mono'">Andale Mono</option>
					<option style="font-family: 'Arial'">Arial</option>
					<option style="font-family: 'Book Antiqua'">Book Antiqua</option>
					<option style="font-family: 'Comic Sans MS'">Comic Sans MS</option>
					<option style="font-family: 'Courier New'">Courier New</option>
					<option style="font-family: 'Georgia'">Georgia</option>
					<option style="font-family: 'Helvetica'">Helvetica</option>
					<option style="font-family: 'Impact'">Impact</option>
					<option style="font-family: 'Times New Roman'">Times New Roman</option>
					<option style="font-family: 'Trebuchet MS'">Trebuchet MS</option>
					<option style="font-family: 'Verdana'">Verdana</option>
				</select>
			</div>
			<div class="cell grid-x">
				<label for="acym__wysid__context__button__font" class="cell small-5"><?php echo acym_translation('ACYM_SIZE'); ?></label>
				<select id="acym__wysid__context__button__font-size" class="small-5 cell">
					<option>10</option>
					<option>12</option>
					<option>14</option>
					<option>16</option>
					<option>18</option>
					<option>20</option>
					<option>24</option>
					<option>28</option>
					<option>30</option>
					<option>34</option>
					<option>36</option>
				</select>
			</div>
			<div class="cell grid-x">
				<label for="acym__wysid__context__button__background" class="cell small-5"><?php echo acym_translation('ACYM_COLOR'); ?></label>
				<input type="text" id="acym__wysid__context__button__color" class="small-2 cell">
			</div>
		</div>
		<p class="cell acym__wysid__right__toolbar__p__open acym__wysid__right__toolbar__p acym__title">
            <?php echo acym_translation('ACYM_BORDER'); ?><i class="acymicon-keyboard_arrow_up"></i>
		</p>
		<div class="cell grid-x acym__wysid__context__modal__container">
			<div class="grid-x cell">
				<label class="cell small-5"><?php echo acym_translation('ACYM_WIDTH'); ?></label>
				<select id="acym__wysid__context__button__border-width" class="small-5 cell">
					<option>0</option>
					<option>1</option>
					<option>2</option>
					<option>3</option>
					<option>4</option>
					<option>5</option>
				</select>
			</div>
			<div class="grid-x cell">
				<label class="small-5 cell"><?php echo acym_translation('ACYM_RADIUS'); ?></label>
				<select id="acym__wysid__context__button__border-radius" class="small-5 cell">
					<option>0</option>
					<option>5</option>
					<option>10</option>
					<option>15</option>
					<option>20</option>
					<option>25</option>
				</select>
			</div>
			<div class="cell grid-x">
				<label class="cell small-5"><?php echo acym_translation('ACYM_COLOR'); ?></label>
				<input type="text" id="acym__wysid__context__button__border-color" class="small-5 cell">
			</div>
		</div>
		<p class="cell acym__wysid__right__toolbar__p__open acym__wysid__right__toolbar__p acym__title">
            <?php echo acym_translation('ACYM_OTHER'); ?><i class="acymicon-keyboard_arrow_up"></i>
		</p>
		<div class="cell grid-x acym__wysid__context__modal__container">
			<div class="cell grid-x">
				<label for="acym__wysid__context__button__background-color" class="cell small-5">
                    <?php echo acym_translation('ACYM_BACKGROUND_COLOR').acym_info('ACYM_BACKGROUND_COLOR_BUTTON_DESC'); ?>
				</label>
				<input type="text" id="acym__wysid__context__button__background-color" class="small-5 cell">
			</div>
			<div class="grid-x cell">
				<div class="cell grid-x">
					<label class="cell small-5"><?php echo acym_translation('ACYM_ALIGNMENT'); ?></label>
					<i class="acymicon-format_align_left cell shrink acym__wysid__context__button__align" id="acym__wysid__context__button__align__left" data-align="left"></i>
					<i class="acymicon-format_align_center cell shrink acym__wysid__context__button__align"
					   id="acym__wysid__context__button__align__center"
					   data-align="center"></i>
					<i class="acymicon-format_align_right cell shrink acym__wysid__context__button__align" id="acym__wysid__context__button__align__right" data-align="right"></i>
				</div>
			</div>
			<div class="cell grid-x margin-top-1 acym_vcenter margin-bottom-1">
                <?php
                echo acym_switch('full_width', 0, acym_tooltip(acym_translation('ACYM_FULL_WIDTH'), acym_translation('ACYM_FULL_WIDTH_DESC')), [], 'small-5');
                ?>
				<div class="cell grid-x acym__button__padding">
					<div class="cell grid-x">
						<div class="cell grid-x small-12">
							<label class="cell small-3"><?php echo acym_translation('ACYM_PADDING'); ?></label>
							<div class="small-6 padding-right-1 padding-left-1 cell acym__wysid__context__button__slider" data-output="slider__output__button__width">
								<div class="slider" data-slider="" data-end="100" data-initial-start="25">
									<span class="slider-handle"
										  data-slider-handle=""
										  role="slider"
										  tabindex="0"
										  aria-controls="slider__output__button__width"
										  aria-valuemax="50"
										  data-valuenow="25"
										  aria-valuemin="10"
										  style="left: 44%"></span>
									<span class="slider-fill" data-slider-fill="" style="width: 44%;"></span>
								</div>
							</div>
							<div class="small-2 cell" id="acym__wysid__context__space__input">
								<input type="number" id="slider__output__button__width" max="50" min="10" step="1">
							</div>
						</div>
						<div class="cell grid-x acym_vcenter">
							<div class="cell small-3 grid-x acym__wysid__context__button__slider" data-output="slider__output__button__height">
								<div class="slider vertical" data-slider data-initial-start="25" data-end="100" data-vertical="true">
									<span class="slider-handle" data-slider-handle role="slider" tabindex="0" aria-controls="slider__output__button__height"></span>
									<span class="slider-fill" data-slider-fill></span>
								</div>
							</div>
							<div class="cell small-7 acym__button__padding__shape align-center acym_vcenter"><?php echo acym_translation('ACYM_BUTTON'); ?></div>
							<div class="cell grid-x">
								<input type="number" class="cell small-2" id="slider__output__button__height" max="50" min="10" step="1">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="acym__wysid__context__space" style="display: none" class="grid-x padding-1 acym__wysid__context__modal">
		<label class="cell small-3"><?php echo acym_translation('ACYM_HEIGHT'); ?></label>
		<div class="small-6 padding-right-1 cell" id="acym__wysid__context__space__slider">
			<div class="slider" data-slider="" data-initial-start="50" data-start="10" data-e="2mf38c-e">
				<span class="slider-handle"
					  data-slider-handle=""
					  role="slider"
					  tabindex="0"
					  aria-controls="sliderOutput1"
					  aria-valuemax="100"
					  aria-valuemin="10"
					  aria-valuenow="50"
					  aria-orientation="horizontal"
					  style="left: 44%;"></span>
				<span class="slider-fill" data-slider-fill="" style="width: 44%;"></span>
			</div>
		</div>
		<div class="small-2 cell" id="acym__wysid__context__space__input">
			<input type="number" id="sliderOutput1" max="100" min="10" step="1">
		</div>
	</div>
	<div id="acym__wysid__context__image" style="display: none" class="grid-x padding-1 acym__wysid__context__modal">
		<p class="cell acym__wysid__right__toolbar__p__open acym__wysid__right__toolbar__p acym__title">
            <?php echo acym_translation('ACYM_POSITION'); ?><i class="acymicon-keyboard_arrow_up"></i></p>
		<div class="cell grid-x acym__wysid__context__modal__container">
			<div class="cell grid-x">
				<label class="cell small-3"><?php echo acym_translation('ACYM_ALIGNMENT'); ?></label>
				<div class="cell auto grid-x">
					<i class="acymicon-format_align_left cell shrink acym__wysid__context__image__align"
					   id="acym__wysid__context__image__align__left"
					   data-float="left"
					   data-css='{"float": "left"}'></i>
					<i class="acymicon-format_align_center cell shrink acym__wysid__context__image__align"
					   id="acym__wysid__context__image__align__left"
					   data-float="none"
					   data-css='{"marginRight": "auto", "marginLeft": "auto", "float": "none"}'></i>
					<i class="acymicon-format_align_right cell shrink acym__wysid__context__image__align"
					   id="acym__wysid__context__image__align__left"
					   data-float="right"
					   data-css='{"float": "right"}'></i>
				</div>
			</div>
		</div>
		<p class="cell acym__wysid__right__toolbar__p__open acym__wysid__right__toolbar__p acym__title">
            <?php echo acym_translation('ACYM_IMAGE_URL'); ?><i class="acymicon-keyboard_arrow_up"></i>
		</p>
		<div class="cell grid-x acym__wysid__context__modal__container align-center">
			<label for="acym__wysid__context__image__url" class="cell small-3"><?php echo acym_translation('ACYM_URL'); ?></label>
			<input type="text"
				   name="image_url"
				   value=""
				   id="acym__wysid__context__image__url"
				   placeholder="https://www.example.com/image.png"
				   class="cell small-9">
			<button type="button" class="cell shrink button button-secondary margin-top-1" id="acym__wysid__context__image__change"><?php echo acym_translation(
                    'ACYM_MEDIA_MANAGE'
                ); ?></button>
		</div>
		<p class="cell acym__wysid__right__toolbar__p__open acym__wysid__right__toolbar__p acym__title">
            <?php echo acym_translation('ACYM_LINK'); ?><i class="acymicon-keyboard_arrow_up"></i>
		</p>
		<div class="cell grid-x acym__wysid__context__modal__container">
			<label for="acym__wysid__context__image__link" class="cell small-3"><?php echo acym_translation('ACYM_LINK'); ?></label>
			<input type="text" name="image_link" value="" id="acym__wysid__context__image__link" placeholder="https://www.example.com" class="cell auto">
		</div>
	</div>
	<div id="acym__wysid__context__separator" class="grid-x padding-1 acym__wysid__context__modal" style="display: none">
		<p class="cell acym__wysid__right__toolbar__p__open acym__wysid__right__toolbar__p acym__title">
            <?php echo acym_translation('ACYM_STYLE'); ?><i class="acymicon-keyboard_arrow_up"></i>
		</p>
		<div class="cell grid-x acym__wysid__context__modal__container">
			<div class="cell grid-x grid-margin-x">
				<div class="acym__wysid__context__separator__kind cell small-3 separator-selected">
					<hr data-kind="solid" style="border-bottom: 3px solid black">
				</div>
				<div class="acym__wysid__context__separator__kind cell small-3">
					<hr data-kind="dotted" style="border-bottom: 3px dotted black">
				</div>
				<div class="acym__wysid__context__separator__kind cell small-3">
					<hr data-kind="dashed" style="border-bottom: 3px dashed black">
				</div>
				<div class="acym__wysid__context__separator__kind cell small-3">
					<hr data-kind="double" style="border-bottom: 3px double black">
				</div>
			</div>
			<label class="cell small-11 grid-x grid-margin-x margin-top-1">
				<label class="cell small-3 acym__color__light-blue"><?php echo acym_translation('ACYM_COLOR'); ?></label>
				<input type="text" id="acym__wysid__context__separator__color">
			</label>
		</div>
		<p class="cell acym__wysid__right__toolbar__p__open acym__wysid__right__toolbar__p acym__title">
            <?php echo acym_translation('ACYM_SIZE'); ?><i class="acymicon-keyboard_arrow_up"></i>
		</p>
		<div class="cell grid-x acym__wysid__context__modal__container">
			<div class="cell grid-x">
				<label class="cell small-3 acym_vcenter acym__color__light-blue"><?php echo acym_translation('ACYM_HEIGHT'); ?></label>
				<div class="small-6 padding-right-1 padding-left-1 cell" id="acym__wysid__context__separator__slide">
					<div class="slider" data-slider data-initial-start="3" data-start="1" data-e="2mf38c-e">
						<span class="slider-handle"
							  data-slider-handle
							  role="slider"
							  tabindex="0"
							  aria-controls="sliderOutput2"
							  aria-valuemax="20"
							  aria-valuemin="1"
							  aria-valuenow="3"
							  aria-orientation="horizontal"
							  style="left: 44%;"></span>
						<span class="slider-fill" data-slider-fill style="width: 44%;"></span>
					</div>
				</div>
				<div class="small-2 cell margin-left-1" id="acym__wysid__context__separator__input__height">
					<input type="number" id="sliderOutput2" max="20" min="1" step="1" value="3">
				</div>
			</div>
			<div class="cell grid-x">
				<label class="cell small-3 acym_vcenter acym__color__light-blue"><?php echo acym_translation('ACYM_WIDTH'); ?></label>
				<div class="small-6 padding-right-1 padding-left-1 cell" id="acym__wysid__context__separator__slide__width">
					<div class="slider" data-slider data-initial-start="100">
						<span class="slider-handle" data-slider-handle role="slider" tabindex="1" aria-controls="sliderOutput3" style="left: 100%;"></span>
						<span class="slider-fill" data-slider-fill style="width: 100%;"></span>
					</div>
				</div>
				<div class="small-2 cell margin-left-1" id="acym__wysid__context__separator__input__width">
					<input type="number" id="sliderOutput3" max="100" min="0" step="1" value="100">
				</div>
			</div>
			<div class="cell grid-x">
				<label class="cell small-3 acym_vcenter acym__color__light-blue"><?php echo acym_translation('ACYM_SPACE'); ?></label>
				<div class="small-6 padding-right-1 padding-left-1 cell" id="acym__wysid__context__separator__slide__space">
					<div class="slider" data-slider data-initial-start="10" data-options="end:50;">
						<span class="slider-handle" data-slider-handle role="slider" tabindex="1" aria-controls="sliderOutput4" style="left: 100%;"></span>
						<span class="slider-fill" data-slider-fill style="width: 100%;"></span>
					</div>
				</div>
				<div class="small-2 cell margin-left-1" id="acym__wysid__context__separator__input__space">
					<input type="number" id="sliderOutput4" max="50" min="0" step="1" value="10">
				</div>
			</div>
		</div>
	</div>
	<div id="acym__wysid__context__follow" class="grid-x padding-1 acym__wysid__context__modal" style="display: none">
		<p class="cell acym__wysid__right__toolbar__p__open acym__wysid__right__toolbar__p acym__title">
            <?php echo acym_translation('ACYM_LINKS'); ?><i class="acymicon-keyboard_arrow_up"></i>
		</p>
		<div class="cell grid-x acym__wysid__context__modal__container">
			<div class="grid-x cell margin-bottom-1">
				<label class="cell small-3"><?php echo acym_translation('ACYM_ADD_NEW'); ?></label>
				<div class="small-2 cell">
					<select name="acym__wysid__context__follow__select" id="acym__wysid__context__follow__select">
					</select>
				</div>
			</div>
			<div id="acym__wysid__context__follow__list" class="grid-x small-12 cell">
			</div>
		</div>
		<p class="cell acym__wysid__right__toolbar__p__open acym__wysid__right__toolbar__p acym__title">
            <?php echo acym_translation('ACYM_OTHER'); ?><i class="acymicon-keyboard_arrow_up"></i>
		</p>
		<div class="cell grid-x acym__wysid__context__modal__container">
			<div class="cell grid-x">
				<label class="small-3 cell"><?php echo acym_translation('ACYM_WIDTH'); ?></label>
				<div class="small-6 padding-right-1 padding-left-1 cell" id="acym__wysid__context__social__width__slider">
					<div class="slider" data-slider data-initial-start="40" data-options="start:30;end:80;">
						<span class="slider-handle" data-slider-handle role="slider" tabindex="1" aria-controls="acym__wysid__context__social__width" style="left: 100%;"></span>
						<span class="slider-fill" data-slider-fill style="width: 100%;"></span>
					</div>
				</div>
				<div class="small-2 cell margin-left-1">
					<input type="number" id="acym__wysid__context__social__width" max="80" min="30" step="1" value="30">
				</div>
			</div>
			<div class="grid-x cell margin-top-1">
				<div class="cell grid-x grid-margin-x">
					<label class="cell small-3"><?php echo acym_translation('ACYM_ALIGNMENT'); ?></label>
					<div class="cell auto grid-x">
						<i class="acymicon-format_align_left cell shrink acym__wysid__context__follow__align" id="acym__wysid__context__follow__align__left" data-align="left"></i>
						<i class="acymicon-format_align_center cell shrink acym__wysid__context__follow__align"
						   id="acym__wysid__context__follow__align__center"
						   data-align="center"></i>
						<i class="acymicon-format_align_right cell shrink acym__wysid__context__follow__align"
						   id="acym__wysid__context__follow__align__right"
						   data-align="right"></i>
					</div>
				</div>
			</div>
		</div>
	</div>
    <?php if ($this->config->get('display_built_by', 0) == 1) { ?>
		<div id="acym__wysid__context__poweredby" style="display: none" class="grid-x padding-1 acym__wysid__context__modal">
            <?php if (!acym_level(ACYM_ESSENTIAL)) { ?>
				<h3 class="cell acym__title text-center"><?php echo acym_translation('ACYM_WANT_TO_REMOVE_THIS'); ?></h3>
				<div class="cell grid-x grid-margin-y text-center margin-bottom-1">
					<div class="cell"><?php echo acym_translation('ACYM_SEEING_THIS_AS_FREE_VERSION'); ?></div>
					<div class="cell"><?php echo acym_translation('ACYM_DISABLE_BY_GETTING_PRO'); ?></div>
					<div class="cell"><?php echo acym_translation('ACYM_PRICES_STARTS_AT'); ?></div>
				</div>
				<div class="cell text-center margin-bottom-1">
					<a class="button button-secondary"
					   target="_blank"
					   href="https://www.acymailing.com/pricing/?utm_source=acymailing_plugin&utm_campaign=purchase&utm_medium=built_with_footer">
                        <?php echo acym_translation('ACYM_SEE_PRO_VERSION_FEATURES'); ?>
					</a>
				</div>
				<hr data-kind="solid" style="border-bottom: 1px solid black" class="cell margin-1">
            <?php } ?>
			<div class="grid-x small-12 cell">
				<label class="middle large-6 small-8 cell" for="acym__wysid__built-with__text__color"><?php echo acym_translation('ACYM_BUILT_WITH_IMAGE_TEXT_COLOR'); ?></label>
				<div class="cell large-6 small-4">
                    <?php
                    $brightness = [
                        'black' => acym_translation('ACYM_BLACK'),
                        'white' => acym_translation('ACYM_WHITE'),
                    ];
                    echo acym_select($brightness, 'acym__wysid__built-with__text__color', 'black', 'class="acym__select"'); ?>
				</div>
			</div>
		</div>
    <?php } ?>

	<div id="acym__wysid__context__plugins" class="grid-x padding-1 acym__wysid__context__modal margin-top-1" style="display: none">
	</div>
</div>
