<?php

use AcyMailing\Classes\FieldClass;
use AcyMailing\Core\AcymPlugin;
use AcyMailing\Classes\ListClass;

class plgAcymContactform7 extends AcymPlugin
{
    private const BLOCK_IDENTIFIER = 'acymsub';
    private const HANDLED_CUSTOM_FIELD_TYPES = [
        'text',
        'textarea',
        'select',
        'phone',
        'date',
        'single_dropdown',
        'multiple_dropdown',
        'radio',
        'checkbox',
    ];

    private array $propertyLabels;

    public function __construct()
    {
        parent::__construct();

        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('contact-form-7/wp-contact-form-7.php');

        $this->pluginDescription->name = 'Contact Form 7';
        $this->pluginDescription->category = 'Subscription system';
        $this->pluginDescription->description = '- Add subscription to AcyMailing lists on contact forms';

        $this->propertyLabels = [
            'displayLists' => acym_translation('ACYM_DISPLAYED_LISTS'),
            'defaultLists' => acym_translation('ACYM_LISTS_CHECKED_DEFAULT'),
            'autoLists' => acym_translation('ACYM_AUTO_SUBSCRIBE_TO'),
        ];
    }

    public function onAcymInitWordpressAddons()
    {
        add_action('wpcf7_admin_init', [$this, 'addAcymBlockType'], 100, 0);
        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts'], 20, 1);

        add_action('wpcf7_init', [$this, 'addAcymBlockHandler']);
        add_filter('wpcf7_validate_'.self::BLOCK_IDENTIFIER, [$this, 'validationFilter'], 10, 2);
        add_filter('wpcf7_validate_'.self::BLOCK_IDENTIFIER.'*', [$this, 'validationFilter'], 10, 2);
    }

    /**
     * Defines the AcyMailing block for CF7 form edition
     */
    public function addAcymBlockType()
    {
        $tagGenerator = WPCF7_TagGenerator::get_instance();
        $tagGenerator->add(
            self::BLOCK_IDENTIFIER,
            acym_translation('ACYM_ACYMAILING_LISTS'),
            [$this, 'displayBlockOptions'],
            [
                'version' => '2',
            ]
        );
    }

    /**
     * Loads JS needed by the block when editing forms
     */
    public function adminEnqueueScripts($hook_suffix)
    {
        $this->loadJavascript('formEdition', false, ACYM_PLUGINS_URL.'/'.basename(__DIR__));
    }

    /**
     * Adds a handler that displays the block content in forms
     */
    public function addAcymBlockHandler()
    {
        wpcf7_add_form_tag(
            [
                self::BLOCK_IDENTIFIER,
                self::BLOCK_IDENTIFIER.'*',
            ],
            [$this, 'displayBlockInForm'],
            [
                'name-attr' => true,
            ]
        );
    }

    /**
     * Blocks form submission when selecting lists is required and not done
     */
    public function validationFilter($result, $tag)
    {
        $name = $tag->name;
        if (!$tag->is_required()) {
            return $result;
        }

        $value = acym_getVar('array', $name, []);
        $hiddenValue = acym_getVar('string', 'acymhiddenlists_'.$name, '');
        if (empty($value) && empty($hiddenValue)) {
            $result->invalidate($tag, wpcf7_get_message('invalid_required'));
        }

        return $result;
    }

    public function displayBlockOptions($contact_form, $options)
    {
        $listClass = new ListClass();
        $lists = $listClass->getAllWithoutManagement();
        foreach ($lists as $i => $oneList) {
            if ($oneList->active == 0) {
                unset($lists[$i]);
            }
        }

        $data = [
            'lists' => $lists,
            'propertyLabels' => $this->propertyLabels,
            'customFields' => [],
        ];

        if (acym_level(ACYM_ENTERPRISE)) {
            $fieldClass = new FieldClass();
            $data['customFields'] = $fieldClass->getFieldsByType(
                self::HANDLED_CUSTOM_FIELD_TYPES
            );
        }

        $documentation = '<a href="https://docs.acymailing.com/addons/wordpress-add-ons/contact-form-7" target="_blank">';
        $documentation .= esc_html(acym_translation('ACYM_SEE_DOCUMENTATION'));
        $documentation .= '</a>';

        $fieldTypes = [
            'acymailing' => [
                'display_name' => acym_translation('ACYM_ACYMAILING_LISTS'),
                'heading' => acym_translation('ACYM_ACYMAILING_LISTS'),
                'description' => acym_translation('ACYM_INSERT_CONTACTFORM_TAG').' '.$documentation,
            ],
        ];

        $tgg = new WPCF7_TagGeneratorGenerator($options['content']);
        $formatter = new WPCF7_HTMLFormatter();

        $formatter->append_start_tag('header', [
            'class' => 'description-box',
        ]);

        $formatter->append_start_tag('h3');
        $formatter->append_preformatted(
            esc_html($fieldTypes['acymailing']['heading'])
        );
        $formatter->end_tag('h3');

        $formatter->append_start_tag('p');
        $formatter->append_preformatted(
            wp_kses_data($fieldTypes['acymailing']['description'])
        );
        $formatter->end_tag('p');

        $formatter->end_tag('header');

        $formatter->append_start_tag('div', [
            'class' => 'control-box',
        ]);
        $formatter->call_user_func(static function () use ($tgg, $fieldTypes, $data) {
            $tgg->print('field_type', [
                'with_required' => true,
                'select_options' => [
                    self::BLOCK_IDENTIFIER => acym_translation('ACYM_ACYMAILING_LISTS'),
                ],
            ]);

            // Subscription block name in the form
            $tgg->print('field_name');

            // Main block class
            $tgg->print('class_attr');
            ?>
			<fieldset>
				<legend id="<?php echo esc_attr($tgg->ref('acymmail-legend')); ?>">
                    <?php echo esc_html(acym_translation('ACYM_MAIL_FIELD_CONTACT')); ?>
				</legend>
				<input type="text"
				       data-tag-part="option"
				       data-tag-option="acymmail:"
				       pattern="[A-Za-z0-9_\-\s]*"
				       placeholder="your-email"
				       aria-labelledby="<?php echo esc_attr($tgg->ref('acymmail-legend')); ?>" />
			</fieldset>

			<fieldset>
				<legend id="<?php echo esc_attr($tgg->ref('acymname-legend')); ?>">
                    <?php echo esc_html(acym_translation('ACYM_NAME_FIELD_CONTACT')); ?>
				</legend>
				<input type="text"
				       data-tag-part="option"
				       data-tag-option="acymname:"
				       pattern="[A-Za-z0-9_\-\s]*"
				       placeholder="your-name"
				       aria-labelledby="<?php echo esc_attr($tgg->ref('acymname-legend')); ?>" />
			</fieldset>
            <?php
            foreach ($data['propertyLabels'] as $key => $label) {
                ?>
				<fieldset>
					<legend id="<?php echo esc_attr($tgg->ref($key.'-legend')); ?>">
                        <?php echo esc_html($label); ?>
					</legend>
                    <?php
                    acym_selectMultiple(
                        $data['lists'],
                        $key,
                        [],
                        [
                            'class' => 'acym-cf7',
                        ],
                        'id',
                        'name',
                        true
                    );
                    ?>
					<input type="hidden"
					       aria-labelledby="<?php echo esc_attr($tgg->ref($key.'-legend')); ?>"
					       data-tag-part="option"
					       data-tag-option="<?php echo esc_attr($key); ?>:" />
				</fieldset>
                <?php
            }

            foreach ($data['customFields'] as $field) {
                if (!empty($field->core) || empty($field->active)) {
                    continue;
                }
                ?>
				<fieldset>
					<legend id="<?php echo esc_attr($tgg->ref('acycf'.$field->id.'-legend')); ?>">
                        <?php echo esc_html(acym_translation($field->name)); ?>
					</legend>
					<input type="text"
					       data-tag-part="option"
					       data-tag-option="acycf<?php echo intval($field->id); ?>:"
					       pattern="[A-Za-z0-9_\-\s]*"
					       placeholder="form-field-name"
					       aria-labelledby="<?php echo esc_attr($tgg->ref('acycf'.$field->id.'-legend')); ?>" />
				</fieldset>
                <?php
            }
        });
        $formatter->end_tag('div');

        $formatter->append_start_tag('footer', [
            'class' => 'insert-box',
        ]);
        $formatter->call_user_func(static function () use ($tgg, $fieldTypes) {
            $tgg->print('insert_box_content');
        });
        $formatter->end_tag('footer');

        $formatter->print();
    }

    public function displayBlockInForm(object $tag): string
    {
        if (empty($tag->name)) {
            return '';
        }

        $submitted = acym_getVar('string', 'acymaction_'.$tag->name, '');
        if (!empty($submitted)) {
            return '';
        }

        $listClass = new ListClass();
        $fieldClass = new FieldClass();
        $pluginUrl = ACYM_PLUGINS_URL.'/'.basename(__DIR__);

        // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
        $style = '<link rel="stylesheet" href="'.esc_url($pluginUrl.'/css/contactForm.css').'" type="text/css">';
        $this->loadJavascript('contactForm', false, $pluginUrl);

        $class = wpcf7_form_controls_class($tag->type);
        $validationError = wpcf7_get_validation_error($tag->name);
        if ($validationError) {
            $class .= ' wpcf7-not-valid';
        }

        $emailField = $tag->get_option('acymmail', '.*');
        $nameField = $tag->get_option('acymname', '.*');

        $customFields = [];
        foreach ($tag->options as $option) {
            if (strpos($option, 'acycf') !== 0) {
                continue;
            }

            $fieldOptions = explode(':', $option);
            $fieldId = substr($fieldOptions[0], 5);
            $field = $fieldClass->getOneById($fieldId);
            if (empty($field)) {
                continue;
            }

            $customFields[$fieldId] = [
                'type' => $field->type,
                'values' => empty($field->value) ? [] : json_decode($field->value, true),
                'matchingFieldName' => $fieldOptions[1],
            ];
        }

        $securityKey = '';
        if (!empty($this->config->get('recaptcha_secretkey', ''))) {
            $securityKey = '&seckey='.$this->config->get('security_key', '');
        }

        $data = [
            'identifier' => $tag->name,
            'class' => $tag->get_class_option($class),
            'emailField' => is_array($emailField) ? array_pop($emailField) : 'your-email',
            'nameField' => is_array($nameField) ? array_pop($nameField) : 'your-name',
            'customFields' => $customFields,
            'listNames' => $listClass->getAllForSelect(true, 0, true, true),
            'listsToDisplay' => $this->prepareLists($tag, 'displayLists'),
            'listsToCheckByDefault' => $this->prepareLists($tag, 'defaultLists'),
            'listsSubbedOnSubmit' => $this->prepareLists($tag, 'autoLists'),
            'submitUrl' => htmlspecialchars_decode(acym_frontendLink('frontusers&task=subscribe'.$securityKey)),
            'validationError' => $validationError,
        ];

        // Don't show lists that will automatically be subscribed
        $data['listsToDisplay'] = array_diff($data['listsToDisplay'], $data['listsSubbedOnSubmit']);

        return $style.$this->includeView('display', $data, __DIR__);
    }

    private function prepareLists(object $tag, string $option): array
    {
        $lists = $tag->get_option($option, '\\d+(-\\d+)*');
        if (empty($lists)) {
            // Handling previous format
            foreach ($tag->values as $oneValue) {
                if (strpos($oneValue, $option) === 0) {
                    $lists = substr($oneValue, strlen($option) + 1);
                    if (empty($lists)) {
                        return [];
                    }

                    $listIds = explode(',', $lists);
                    acym_arrayToInteger($listIds);

                    return $listIds;
                }
            }

            return [];
        }

        $listIds = explode('-', array_pop($lists));
        acym_arrayToInteger($listIds);

        return $listIds;
    }
}
