<?php

namespace AcyMailing\Helpers;

use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\ZoneClass;
use AcyMailing\Core\AcymObject;
use AcyMailing\Types\DtextType;
use Joomla\CMS\Editor\Editor as Editor;
use Joomla\CMS\Factory;

class EditorHelper extends AcymObject
{
    private string $width = '95%';
    private string $height = '600';

    private int $cols = 100;
    private int $rows = 30;

    private $myEditor;
    public string $editor = '';

    private string $editorContent = '';
    private array $editorConfig = [];
    private array $emailsTest;
    private string $name = 'editor_content';

    public string $settings = 'editor_settings';
    public string $stylesheet = 'editor_stylesheet';
    public string $content = '';
    public int $mailId = 0;
    public bool $automation = false;
    public bool $walkThrough = false;

    // Used in the d&d
    public array $data = [];
    private string $defaultTemplate = '';

    public string $autoSave;

    public function display(): void
    {
        if ($this->isDragAndDrop()) {
            ob_start();
            include acym_getPartial('editor', 'default_template');
            $this->defaultTemplate = ob_get_clean();

            acym_disableCmsEditor();
            $currentEmail = acym_currentUserEmail();
            $this->emailsTest = [$currentEmail => $currentEmail];
            acym_addScript(false, ACYM_JS.'tinymce/tinymce.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'tinymce/tinymce.min.js'));

            $data = $this->data;
            $mailId = empty($data['mail']->mail_id) ? (empty($data['mail']->id) ? 0 : $data['mail']->id) : $data['mail']->mail_id;
            $data['tabHelper'] = new TabHelper();
            $data['plugins'] = acym_trigger('dynamicText', [$mailId]);
            usort(
                $data['plugins'],
                function ($a, $b) {
                    return strtolower($a->name ?? '') > strtolower($b->name ?? '') ? 1 : -1;
                }
            );

            $zoneClass = new ZoneClass();
            $data['custom_zones'] = $zoneClass->getAll();
            usort($data['custom_zones'], function ($a, $b) {
                return strtolower($a->name) > strtolower($b->name) ? 1 : -1;
            });

            acym_setVar('mail_id', $mailId);
            acym_setVar('mail_type', empty($data['mail']->type) ? '' : $data['mail']->type);
            acym_setVar('automation', $this->automation ? 1 : 0);

            include acym_getPartial('editor', 'editor_wysid');
        } else {
            // Outside of the Acy wrapper to prevent foundation from breaking the 100 different editors we could have here

            // Close acym_content div
            if (acym_isLeftMenuNecessary()) echo '</div>';

            // Close acym_wrapper div and open the no foundation div
            echo '</div><div class="acym_no_foundation">';

            // Display the editor
            $dtextType = new DtextType();
            $dtextType->display(['withButton' => false]);

            $method = 'display{__CMS__}';
            $this->$method();

            // The view renderer will close the acym_content div, but since we already closed it, we open a fake one
            if (acym_isLeftMenuNecessary()) echo '<div>';
            // The view renderer will close the acym_wrapper div, which will be the acym_no_foundation div in this case
        }
    }

    public function isDragAndDrop(): bool
    {
        return strpos($this->content, 'acym__wysid__template') !== false || $this->editor === 'acyEditor';
    }

    private function displayJoomla(): void
    {
        $dtextType = new DtextType();
        $dtextType->displayButton(
            [
                'icon' => 'acymicon-chevrons',
                'text' => acym_translation('ACYM_INSERT_DYNAMIC_TEXT'),
                'class' => 'button button-secondary margin-bottom-0 margin-top-1',
            ]
        );

        $this->editor = acym_getCMSConfig('editor', 'tinymce');

        if (!class_exists('Joomla\CMS\Editor\Editor')) {
            $this->myEditor = Factory::getEditor($this->editor);
        } else {
            $this->myEditor = Editor::getInstance($this->editor);
        }
        $this->myEditor->initialise();

        // We allow the background parameter on tr,table and td.
        $this->editorConfig['extended_elements'] = 'table[background|cellspacing|cellpadding|width|align|bgcolor|border|style|class|id],tr[background|width|bgcolor|style|class|id|valign],td[background|width|align|bgcolor|valign|colspan|rowspan|height|style|class|id|nowrap]';

        if (!empty($this->mailId)) {
            //Load Css editor
            $cssurl = acym_completeLink((acym_isAdmin() ? '' : 'front').'mails&task=loadCSS&id='.$this->mailId.'&time='.time());
            $classMail = new MailClass();
            $filepath = $classMail->createTemplateFile($this->mailId);

            if ($this->editor === 'tinymce') {
                $this->editorConfig['content_css_custom'] = $cssurl.'&local=http';
                $this->editorConfig['content_css'] = '0';

                // We disable this for Joomla 4 because it might do a fatal due to Joomla 4 TinyMCE params not well formatted
                if (!ACYM_J40) {
                    // Joomla broke the custom css feature in the v3.9.21 so we have to do this
                    $access = [];
                    for ($i = 1; $i < 20; $i++) {
                        $access[] = $i;
                    }

                    $this->editorConfig['configuration'] = (object)[
                        'toolbars' => (object)['AcyCustomCSS' => []],
                        'setoptions' => [
                            'AcyCustomCSS' => (object)[
                                'access' => $access,
                                'content_css' => '0',
                                'content_css_custom' => $cssurl.'&local=http',
                            ],
                        ],
                    ];
                }
            } elseif ($this->editor === 'jckeditor' || $this->editor === 'fckeditor') {
                //For jckeditor, we need to create a fake template.css file... lets do that on the template/css/folder
                $this->editorConfig['content_css_custom'] = $filepath;
                $this->editorConfig['content_css'] = '0';
                $this->editorConfig['editor_css'] = '0';
            } else {
                //We still create the file so that the user can link to it manually
                $fileurl = str_replace(DS, '/', ACYM_MEDIA_FOLDER).'templates/css/template_'.$this->mailId.'.css?time='.time();
                $this->editorConfig['custom_css_url'] = $cssurl;
                $this->editorConfig['custom_css_file'] = $fileurl;
                $this->editorConfig['custom_css_path'] = $filepath;
                //Can be useful for JCE integration for example.
                acym_setVar('acycssfile', $fileurl);
            }
        }

        if (empty($this->editorContent)) {
            $this->content = acym_escape($this->content);
            ob_start();

            echo $this->myEditor->display(
                $this->name,
                $this->content,
                $this->width,
                $this->height,
                $this->cols,
                $this->rows,
                ['pagebreak', 'readmore'],
                null,
                'com_content',
                null,
                $this->editorConfig
            );

            $this->editorContent = ob_get_clean();
        }

        if (method_exists($this->myEditor, 'save')) {
            acym_addScript(true, 'function acyOnSaveEditor(){'.$this->myEditor->save($this->name).'}');
        }

        echo $this->editorContent;
    }

    private function displayWordPress(): void
    {
        add_filter('mce_external_plugins', [$this, 'addPlugins']);
        add_filter('mce_buttons', [$this, 'addButtons']);
        add_filter('mce_buttons_2', [$this, 'addButtonsToolbar']);
        add_action('media_buttons', [$this, 'addDtextButton']);

        $mailClass = new MailClass();

        $mail = $mailClass->getOneById($this->mailId);
        $stylesheet = empty($mail) ? '' : trim(preg_replace('/\s\s+/', ' ', $mailClass->buildCSS($mail->stylesheet)));
        $stylesheet = str_replace(['"', "\r\n", "\n"], ['\"', '', ''], $stylesheet);

        $options = [
            'editor_css' => '<style type="text/css">
                                .alignleft{float:left;margin:0.5em 1em 0.5em 0;}
                                .aligncenter{display: block;margin-left: auto;margin-right: auto;}
                                .alignright{float: right;margin: 0.5em 0 0.5em 1em;}
                             </style>',
            'editor_height' => $this->height,
            'textarea_rows' => $this->rows,
            'wpautop' => false,
            'tinymce' => [
                'content_css' => '',
                'content_style' => '.alignleft{float:left;margin:0.5em 1em 0.5em 0;} .aligncenter{display: block;margin-left: auto;margin-right: auto;} .alignright{float: right;margin: 0.5em 0 0.5em 1em;}'.$stylesheet,
            ],
        ];

        wp_editor($this->content, $this->name, $options);
    }

    public function addDtextButton(string $editor_id = 'content'): void
    {
        static $instance = 0;
        ++$instance;

        $img = '<i class="acymicon-chevrons"></i> ';

        printf(
            '<button type="button" class="button" id="acym__dtext__button" data-editor="%s">%s</button>',
            esc_attr($editor_id),
            $img.acym_translation('ACYM_INSERT_DYNAMIC_TEXT')
        );
    }

    // Used in partial editor
    private function getWYSIDSettings(): string
    {
        $ctrl = acym_getVar('string', 'ctrl');
        if ($this->isResetCampaign() || !in_array($ctrl, ['dashboard', 'mails', 'campaigns', 'frontmails', 'frontcampaigns'])) {
            return '{}';
        }

        $mailId = acym_getVar('int', 'from', 0);
        if (empty($mailId)) {
            if ($this->settings !== 'editor_settings') {
                return $this->settings;
            }

            $mailId = acym_getVar('int', 'id');
            $campaignId = acym_getVar('int', 'campaignId');
            if (!empty($campaignId) && $ctrl === 'campaigns') {
                $mailId = acym_loadResult('SELECT mail_id FROM #__acym_campaign WHERE id = '.intval($campaignId));
            }

            if (empty($mailId)) {
                return '{}';
            }
        }

        $settings = acym_loadResult('SELECT settings FROM #__acym_mail WHERE id = '.intval($mailId));

        return empty($settings) ? '{}' : $settings;
    }

    // Used in partial editor
    private function getWYSIDStylesheet(): string
    {
        $ctrl = acym_getVar('string', 'ctrl');
        if ($this->isResetCampaign() || !in_array($ctrl, ['mails', 'campaigns', 'frontmails', 'frontcampaigns'])) {
            return '';
        }

        $mailId = acym_getVar('int', 'from', 0);
        if (empty($mailId)) {
            if ($this->stylesheet !== 'editor_stylesheet') {
                return $this->stylesheet;
            }

            $mailId = acym_getVar('int', 'id');
            $campaignId = acym_getVar('int', 'campaignId');
            if (!empty($campaignId) && in_array($ctrl, ['campaigns', 'frontcampaigns'])) {
                $mailId = acym_loadResult('SELECT mail_id FROM #__acym_campaign WHERE id = '.intval($campaignId));
            }
        }

        $notification = acym_getVar('string', 'notification');
        if (!empty($mailId)) {
            $stylesheet = acym_loadResult('SELECT stylesheet FROM #__acym_mail WHERE id = '.intval($mailId));
        } elseif (!empty($notification)) {
            $stylesheet = acym_loadResult(
                'SELECT stylesheet 
                FROM #__acym_mail 
                WHERE `type` = '.acym_escapeDB(MailClass::TYPE_NOTIFICATION).' 
                    AND `name` = '.acym_escapeDB($notification)
            );
        }

        return empty($stylesheet) ? '' : $stylesheet;
    }

    private function isResetCampaign(): bool
    {
        $fromId = acym_getVar('int', 'from', 0);

        return -1 === $fromId;
    }

    /**
     * Methods used to add buttons to the WordPress editor
     */
    private function addButtonAtPosition(array &$buttons, string $newButton, string $after): void
    {
        $position = array_search($after, $buttons);

        if ($position === false) {
            array_push($buttons, 'separator', $newButton);
        } else {
            array_splice($buttons, $position + 1, 0, $newButton);
        }
    }

    public function addPlugins(array $plugins): array
    {
        $plugins['table'] = ACYM_JS.'tinymce/table.min.js';

        return $plugins;
    }

    public function addButtons(array $buttons): array
    {
        $position = array_search('wp_more', $buttons);
        if ($position !== false) {
            $buttons[$position] = '';
        }

        array_unshift($buttons, 'separator', 'fontsizeselect');
        array_unshift($buttons, 'separator', 'fontselect');
        array_push($buttons, 'separator', 'table');

        $this->addButtonAtPosition($buttons, 'alignjustify', 'alignright');
        $this->addButtonAtPosition($buttons, 'underline', 'italic');
        $this->addButtonAtPosition($buttons, 'strikethrough', 'underline');

        return $buttons;
    }

    public function addButtonsToolbar(array $buttons): array
    {
        $position = array_search('strikethrough', $buttons);
        if ($position !== false) {
            $buttons[$position] = '';
        }
        $this->addButtonAtPosition($buttons, 'backcolor', 'forecolor');

        return $buttons;
    }

    public function getSettingsStyle($settings): string
    {
        if (empty($settings)) {
            return '';
        }

        if (is_string($settings) && substr($settings, 0, 2) === '{"') {
            $settings = json_decode($settings, true);
        }

        if (!is_array($settings)) {
            return '';
        }

        $styles = '';
        foreach ($settings as $element => $rules) {
            $values = '';
            foreach ($rules as $ruleName => $value) {
                if ($ruleName == 'overridden') continue;
                $values .= $ruleName.': '.$value.';';
            }
            $styles .= '#acym__wysid__template .acym__wysid__column__element__td .acym__wysid__tinymce--text '.$element.':not(.acym__wysid__content-no-settings-style){';
            $styles .= $values;
            $styles .= '}';

            $styles .= '#acym__wysid__template .acym__wysid__column__element__td .acymailing_content '.$element.':not(.acym__wysid__content-no-settings-style){';
            $styles .= $values;
            $styles .= '}';
        }

        return $styles;
    }

    private function getDefaultColors(): string
    {
        $mailSettings = new \stdClass();
        if (isset($this->data['mail']->mail_settings) && !empty($this->data['mail']->mail_settings)) {
            $mailSettings = json_decode($this->data['mail']->mail_settings, false);
        } elseif (isset($this->data['mailInformation']->mail_settings) && !empty($this->data['mailInformation']->mail_settings)) {
            $mailSettings = json_decode($this->data['mailInformation']->mail_settings, false);
        }

        if (!empty($mailSettings->mainColors)) {
            return $mailSettings->mainColors;
        }

        return '';
    }
}
