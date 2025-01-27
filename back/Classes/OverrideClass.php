<?php

namespace AcyMailing\Classes;

use AcyMailing\Core\AcymClass;
use AcyMailing\Helpers\PaginationHelper;

class OverrideClass extends AcymClass
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'mail_override';
        $this->pkey = 'id';
    }

    /**
     * Get mails depending on filters (search, ordering, pagination)
     *
     * @param $settings
     *
     * @return mixed
     */
    public function getMatchingElements(array $settings = []): array
    {
        $query = 'SELECT override.*, mail.name, mail.subject FROM #__acym_mail_override AS override JOIN #__acym_mail AS mail ON override.mail_id = mail.id';
        $queryCount = 'SELECT COUNT(override.mail_id) as total, SUM(override.active) as totalActive FROM #__acym_mail_override AS override INNER JOIN #__acym_mail AS mail ON override.mail_id = mail.id';

        $filters = [];

        if (!empty($settings['search'])) {
            $filters[] = 'mail.name LIKE '.acym_escapeDB('%'.$settings['search'].'%');
        }

        if (!empty($settings['source'])) {
            $filters[] = 'override.source LIKE '.acym_escapeDB('%'.$settings['source'].'%');
        }

        $filters[] = 'mail.parent_id IS NULL';

        if (!empty($filters)) {
            $query .= ' WHERE ('.implode(') AND (', $filters).')';
            $queryCount .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        if (!empty($settings['status'])) {
            $query .= empty($filters) ? ' WHERE ' : ' AND ';
            $query .= 'active = '.($settings['status'] == 'active' ? '1' : '0');
        }

        if (!empty($settings['ordering']) && !empty($settings['ordering_sort_order'])) {
            $query .= ' ORDER BY override.'.acym_secureDBColumn($settings['ordering']).' '.acym_secureDBColumn(strtoupper($settings['ordering_sort_order']));
            $query .= ', override.mail_id ASC';
        }

        if (empty($settings['offset']) || $settings['offset'] < 0) {
            $settings['offset'] = 0;
        }

        if (empty($settings['elementsPerPage']) || $settings['elementsPerPage'] < 1) {
            $pagination = new PaginationHelper();
            $settings['elementsPerPage'] = $pagination->getListLimit();
        }

        $mailClass = new MailClass();
        $results['elements'] = $mailClass->decode(acym_loadObjectList($query, '', $settings['offset'], $settings['elementsPerPage']));
        $results['total'] = acym_loadObject($queryCount);

        $results['status'] = [];

        return $results;
    }

    public function cleanEmailsOverride()
    {
        $overrideId = acym_loadResultArray('SELECT id FROM #__acym_mail_override');
        $this->delete($overrideId);
    }

    public function areOverrideMailsInstalled()
    {
        return (bool)acym_loadResult('SELECT COUNT(id) FROM #__acym_mail_override');
    }

    public function getAllSources()
    {
        return acym_loadResultArray('SELECT DISTINCT `source` FROM #__acym_mail_override');
    }

    public function getActiveOverrides($key = '')
    {
        $query = 'SELECT override.*, mail.name FROM #__acym_mail_override AS override JOIN #__acym_mail AS mail ON mail.id = override.mail_id WHERE `active` = 1';

        return acym_loadObjectList($query, $key);
    }

    /**
     * Returns the override email corresponding to the base email content provided
     *
     * @param string $subject The content of the email before the override
     *
     * @param string $body
     *
     * @return object|false
     */
    public function getMailByBaseContent($subject, $body)
    {
        $translatepressIsActive = acym_isExtensionActive('translatepress-multilingual/index.php');
        $activeOverrides = $this->getActiveOverrides('name');

        $joomlaMailStyle = 'plaintext';
        if ('joomla' === ACYM_CMS) {
            acym_loadLanguageFile('com_contact');
            if (ACYM_J40) {
                $params = \Joomla\CMS\Component\ComponentHelper::getParams('com_mails');
                $joomlaMailStyle = $params->get('mail_style', 'plaintext');
            }
        }
       
        foreach ($activeOverrides as $oneOverride) {
            $parameters = [];
            $matches = true;
            foreach (['subject', 'body'] as $part) {
                $identifier = 'base_'.$part;
                if (empty($oneOverride->$identifier)) continue;

                // The identifier is the default email's text, for example "User coucou registered to the site https://www.acymailing.com"
                // We use it to check if the current email matches
                $decodedValue = json_decode($oneOverride->$identifier, true);
                if (empty($decodedValue)) continue;

                $oneOverride->$identifier = '';
                foreach ($decodedValue as $partialTrad) {
                    $oneOverride->$identifier .= acym_translation($partialTrad, false, true, '');
                    if ($oneOverride->source === 'woocommerce') {
                        $oneOverride->$identifier = acym_translation($oneOverride->$identifier, false, true, 'woocommerce');
                        if ($translatepressIsActive) {
                            $oneOverride->$identifier = preg_replace('/#!trpst#trp-gettext data-trpgettextoriginal=\d+#!trpen#/i', '', $oneOverride->$identifier);
                            $oneOverride->$identifier = preg_replace('/#!trpst#\/trp-gettext#!trpen#/i', '', $oneOverride->$identifier);
                        }
                    }
                }

                // Replace the %s / %1$s by (.*) to get the params from the email content
                // So: User %1$s registered to the site %2$s
                // Becomes: User (.*) registered to the site (.*)
                $oneOverride->$identifier = preg_replace(
                    [
                        '/%([0-9].?\$)?s/',
                        '/\\\{[A-Z_]+\\\}/i',
                        '/###[A-Z_-]+###/i',
                        '/\\\#\\\#\\\#[A-Z_-]+\\\#\\\#\\\#/i',
                    ],
                    '(.*)',
                    preg_quote($oneOverride->$identifier, '/')
                );

                $oneOverride->$identifier = str_replace('&amp;', '&', $oneOverride->$identifier);

                if ('plaintext' !== $joomlaMailStyle) {
                    $$part = str_replace('<br>', '', $$part);
                }
                $$part = str_replace("\r\n", "\n", $$part);

                $matches = preg_match('/'.trim($oneOverride->$identifier).'/', $$part, $params) === 1 && $matches;

                if (empty($parameters)) {
                    $parameters = $params;
                } else {
                    for ($i = 1 ; $i < count($params) ; $i++) {
                        $parameters[] = trim($params[$i]);
                    }
                }
            }

            // The content didn't match the identifier
            if (!$matches) continue;

            // We found the override
            $mailClass = new MailClass();
            $mail = $mailClass->getOneById($oneOverride->mail_id);

            if ($oneOverride->source == 'woocommerce' && $translatepressIsActive) {
                $subjectTranslated = $params[0];
                for ($i = 1 ; $i < count($params) ; $i++) {
                    $pattern = '/'.$params[$i].'/i';
                    $param = '{param'.$i.'}';
                    $subjectTranslated = preg_replace($pattern, $param, $subjectTranslated);
                }
                $mail->subject = $subjectTranslated;
            }

            // Include the found parameters
            $mail->parameters = $parameters;

            if (empty($oneOverride->base_body)) $mail->body = $body;

            return $mail;
        }

        return false;
    }

    public function delete($elements)
    {
        if (empty($elements)) return 0;
        if (!is_array($elements)) $elements = [$elements];

        acym_arrayToInteger($elements);
        $mailIds = acym_loadResultArray('SELECT `mail_id` FROM #__acym_mail_override WHERE `id` IN ('.implode(',', $elements).')');

        $result = parent::delete($elements);

        $mailClass = new MailClass();
        $mailClass->delete($mailIds);

        return $result;
    }

    public function getParamsByMailId($mailId)
    {
        $mailClass = new MailClass();
        $mail = $mailClass->getOneById($mailId);

        if (empty($mail)) return [];

        return acym_getOverrideParamsByName($mail->name);
    }
}
