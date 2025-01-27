<?php

namespace AcyMailing\Params;

include_once __DIR__.DIRECTORY_SEPARATOR.'AcymJFormField.php';

class JFormFieldArticle extends AcymJFormField
{
    var $type = 'article';

    public function getInput()
    {
        $modalId = 'acym_article_'.$this->id;
        $callback = 'jSelectArticle_'.$this->id;

        $title = '';
        $value = intval($this->value);
        if (!empty($value)) {
            $title = acym_CMSArticleTitle($value);
        }

        // Callback function from the modal to the main window
        acym_addScript(
            true,
            "
            function $callback(id, title, catid, object, url, language) {
                window.processModalSelect('Article', '".$this->id."', id, title, catid, object, url, language);
                toggle_$callback(id);
                jQuery('#".$modalId."').modal('hide');
            }
            
            function toggle_$callback(selection) {
                if (selection && selection > 0) {
                    jQuery('#button_$modalId').hide();
                    jQuery('#clear_$modalId').show();
                } else {
                    jQuery('#".$this->id."_name').val('');
                    jQuery('#".$this->id."_id').val('');
                    
                    jQuery('#button_$modalId').show();
                    jQuery('#clear_$modalId').hide();
                }
            }
            
            jQuery(function($){
                toggle_$callback($value);
            });"
        );

        $html = '<span class="input-append">';
        $html .= '<input class="input-medium" id="'.$this->id.'_name" type="text" value="'.acym_escape($title).'" disabled="disabled" size="35" />';
        $urlSelect = acym_articleSelectionPage().'&function='.$callback;
        $html .= acym_cmsModal(true, $urlSelect, 'ACYM_SELECT', true, acym_translation('ACYM_SELECT_AN_ARTICLE'), $modalId);
        $html .= '<a id="clear_'.$modalId.'" class="btn hasTooltip" data-toggle="modal" role="button" onclick="toggle_'.$callback.'(0);">'.acym_translation('ACYM_CLEAR').'</a>';
        $html .= '</span>';

        $html .= '<input type="hidden" id="'.$this->id.'_id" name="'.$this->name.'" value="'.$value.'" />';

        return $html;
    }

    public function getLabel()
    {
        return str_replace($this->id, $this->id.'_id', parent::getLabel());
    }
}
