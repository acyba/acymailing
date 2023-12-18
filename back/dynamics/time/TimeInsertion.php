<?php

trait TimeInsertion
{
    public function dynamicText($mailId)
    {
        return $this->pluginDescription;
    }

    public function textPopup()
    {
        $text = '<div class="acym__popup__listing text-center grid-x">
                    <h1 class="acym__title acym__title__secondary text-center cell">'.acym_translation('ACYM_TIME_FORMAT').'</h1>';

        $others = [];
        $others['{date:1}'] = 'ACYM_DATE_FORMAT_LC1';
        $others['{date:2}'] = 'ACYM_DATE_FORMAT_LC2';
        $others['{date:3}'] = 'ACYM_DATE_FORMAT_LC3';
        $others['{date:4}'] = 'ACYM_DATE_FORMAT_LC4';
        $others['{date:m/d/Y}'] = 'm/d/Y';
        $others['{date:d/m/y}'] = 'd/m/y';
        $others['{date:l}'] = 'l';
        $others['{date:F}'] = 'F';


        $k = 0;
        foreach ($others as $tagname => $tag) {
            $text .= '<div class="grid-x medium-12 cell acym__row__no-listing acym__listing__row__popup text-left" onclick="setTag(\''.$tagname.'\', jQuery(this));" >
                        <div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.acym_translation($tag).'</div>
                        <div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.acym_getDate(time(), acym_translation($tag)).'</div>
                     </div>';
            $k = 1 - $k;
        }

        $text .= '</div>';

        echo $text;
    }

    public function replaceContent(&$email, $send = true)
    {
        $extractedTags = $this->pluginHelper->extractTags($email, 'date');
        if (empty($extractedTags)) {
            return;
        }

        $tags = [];
        foreach ($extractedTags as $i => $oneTag) {
            if (isset($tags[$i])) {
                continue;
            }

            $time = time();
            if (!empty($oneTag->senddate) && !empty($email->sending_date)) {
                $time = strtotime($email->sending_date);
            }
            if (!empty($oneTag->add)) {
                $time += intval($oneTag->add);
            }
            if (!empty($oneTag->remove)) {
                $time -= intval($oneTag->remove);
            }

            if (empty($oneTag->id) || is_numeric($oneTag->id)) {
                $oneTag->id = acym_translation('ACYM_DATE_FORMAT_LC'.$oneTag->id);
            }
            $oneTag->id = str_replace(
                ['%A', '%d', '%B', '%m', '%Y', '%y', '%H', '%M', '%S', '%a', '%I', '%p', '%w'],
                ['l', 'd', 'F', 'm', 'Y', 'y', 'H', 'i', 's', 'D', 'h', 'a', 'w'],
                $oneTag->id
            );
            $tags[$i] = acym_date($time, $oneTag->id, true);
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }
}
