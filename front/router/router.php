<?php

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;

class AcymRouter extends AcymRouterBase
{
    private $pagesNotSef = [];
    private $paramsNotSef = [];
    private $separator = '-';

    public function __construct($app = null, $menu = null)
    {
        parent::__construct($app, $menu);
        require_once JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_acym'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';

        $this->pagesNotSef = [
            'cron',
            'fronturl',
            'frontmails',
            'frontdynamics',
        ];

        $this->paramsNotSef = [
            'acyformname',
            'format',
            'hiddenlists',
            'id',
            'Itemid',
            'key',
            'lang',
            'language',
            'limit',
            'limitstart',
            'mail_id',
            'no_html',
            'option',
            'start',
            'tmpl',
            'user',
            'userid',
            'val',
            'from',
            'campaign_type',
            'edition',
            'welcomemailid',
            'unsubmailid',
            'type_editor',
        ];
    }

    public function build(&$query)
    {
        $segments = [];

        // We don't SEF these links
        if (isset($query['ctrl']) && in_array($query['ctrl'], $this->pagesNotSef)) {
            return $segments;
        }

        $ctrl = '';
        $task = '';
        $step = '';

        if (isset($query['ctrl'])) {
            $ctrl = $query['ctrl'];
            unset($query['ctrl']);

            if (isset($query['task'])) {
                $task = $query['task'];
                unset($query['task']);
            }

            if (isset($query['step'])) {
                $step = $query['step'];
                unset($query['step']);
            }
        } elseif (isset($query['view'])) {
            // Joomla builds menus like this
            $ctrl = $query['view'];
            unset($query['view']);

            if (isset($query['layout'])) {
                $task = $query['layout'];
                unset($query['layout']);
            }
        }

        if (!empty($ctrl)) $segments[] = $ctrl;

        if ($ctrl === 'archive' && $task === 'view' && !empty($query['id'])) {
            $segments[] = $this->getMailSEF($query['id']);
            unset($query['id']);
        } else {
            if (!empty($task)) $segments[] = $task;
            if (!empty($step)) $segments[] = $step;
        }

        if (empty($query)) return $segments;

        if ($ctrl === 'frontlists' && !empty($query['id'])) {
            $segments[] = $this->getListSEF($query['id']);
            unset($query['id']);
        }

        if ($ctrl === 'frontusers' && $task === 'unsubscribe' && !empty($query['mail_id'])) {
            $segments[] = $this->getMailSEF($query['mail_id']);
            unset($query['mail_id']);
        }

        if ($ctrl === 'frontcampaigns' && isset($query['id'])) {
            $segments[] = $this->getCampaignSEF($query['id']);
            unset($query['id']);
        }

        foreach ($query as $name => $value) {
            // We don't add to the SEF url these elements
            if (in_array($name, $this->paramsNotSef)) continue;
            $segments[] = $name.$this->separator.$value;
            unset($query[$name]);
        }

        return $segments;
    }

    public function parse(&$segments)
    {
        if (empty($segments)) return [];

        if (strpos(current($segments), $this->separator) === false) {
            $vars = [];
            $vars['ctrl'] = array_shift($segments);
            $vars['task'] = '';
            $vars['step'] = '';
        } else {
            $jsite = JFactory::getApplication('site');
            $menus = $jsite->getMenu();
            $menu = $menus->getActive();
            if (!empty($menu) && !empty($menu->query)) {
                $vars = $menu->query;
            } else {
                $vars = [];
            }

            if (!isset($vars['ctrl'])) {
                $vars['ctrl'] = isset($vars['view']) ? $vars['view'] : '';
            }
            if (!isset($vars['task'])) {
                $vars['task'] = isset($vars['layout']) ? $vars['layout'] : '';
            }
            if (!isset($vars['step'])) $vars['step'] = '';
        }

        if (!empty($segments)) {
            if (strpos(current($segments), $this->separator) === false) {
                $vars['task'] = array_shift($segments);
                if (!empty($segments) && strpos(current($segments), $this->separator) === false) $vars['step'] = array_shift($segments);
            } elseif ($vars['ctrl'] === 'archive' && empty($vars['task'])) {
                $vars['task'] = 'view';
                $mail = array_shift($segments);
                list($id, $alias) = explode($this->separator, $mail, 2);
                $vars['id'] = $id;
            }
        }

        if ($vars['ctrl'] === 'frontlists' && $vars['task'] === 'settings') {
            $list = array_shift($segments);
            list($id, $alias) = explode($this->separator, $list, 2);
            $vars['id'] = $id;
        }

        if ($vars['ctrl'] === 'frontusers' && $vars['task'] === 'unsubscribe') {
            $mail = array_shift($segments);
            list($id, $alias) = explode($this->separator, $mail, 2);
            $vars['mail_id'] = $id;
        }

        if ($vars['ctrl'] === 'frontcampaigns' && $vars['task'] === 'edit' && !empty($segments)) {
            $campaign = array_shift($segments);
            list($id, $alias) = explode($this->separator, $campaign, 2);
            $vars['id'] = $id;
        }

        foreach ($segments as $name) {
            if (strpos($name, $this->separator) === false) continue;

            list($arg, $val) = explode($this->separator, $name, 2);
            $vars[$arg] = $val;
        }

        return $vars;
    }

    private function getMailSEF($id)
    {
        $mailClass = new MailClass();
        $mail = $mailClass->getOneById($id);

        return $id.$this->separator.acym_getAlias($mail->subject);
    }

    private function getCampaignSEF($campaignId)
    {
        if (empty($campaignId) || !is_numeric($campaignId)) return '0'.$this->separator.'new';

        $campaignClass = new CampaignClass();
        $campaign = $campaignClass->getOneByIdWithMail($campaignId);

        return $campaignId.$this->separator.acym_getAlias($campaign->subject);
    }

    private function getListSEF($id)
    {
        $listClass = new ListClass();
        $list = $listClass->getOneById($id);

        return $id.$this->separator.acym_getAlias($list->name);
    }
}
