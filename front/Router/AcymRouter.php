<?php

namespace AcyMailing\Router;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\Router\RouterBase;

class AcymRouter extends RouterBase
{
    private array $pagesNotSef;
    private array $paramsNotSef;
    private string $separator = ':';

    public function __construct($app = null, $menu = null)
    {
        parent::__construct($app, $menu);
        require_once JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_acym'.DIRECTORY_SEPARATOR.'Core'.DIRECTORY_SEPARATOR.'init.php';

        $this->pagesNotSef = [
            'api',
            'cron',
            'fronturl',
            'frontmails',
            'frontdynamics',
            'frontservices',
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
            'listId',
            'userId',
            'campaignId',
            'userKey',
            'user_id',
            'user_key',
        ];
    }

    public function build(&$query): array
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

            unset($query['view']);
            unset($query['layout']);
        } elseif (isset($query['view'])) {
            // Joomla builds menus like this
            $ctrl = $query['view'];
            unset($query['view']);

            if (isset($query['layout'])) {
                $task = $query['layout'];
                unset($query['layout']);
            }
        }

        if (!empty($ctrl)) {
            $segments[] = $ctrl;
        }

        if ($ctrl === 'archive' && $task === 'view' && !empty($query['id'])) {
            $segments[] = $this->getMailSEF((int)$query['id']);
            unset($query['id']);
        } else {
            if (!empty($task)) $segments[] = $task;
            if (!empty($step)) $segments[] = $step;
        }

        if (empty($query)) {
            return $segments;
        }

        if ($ctrl === 'frontlists' && !empty($query['listId'])) {
            $segments[] = $this->getListSEF((int)$query['listId']);
            unset($query['listId']);
        }

        if ($ctrl === 'frontusers' && $task === 'unsubscribe' && !empty($query['mail_id'])) {
            $segments[] = $this->getMailSEF((int)$query['mail_id']);
            unset($query['mail_id']);
        }

        foreach ($query as $name => $value) {
            if (in_array($name, $this->paramsNotSef)) {
                continue;
            }

            $segments[] = $name.$this->separator.$value;
            unset($query[$name]);
        }

        return $segments;
    }

    public function parse(&$segments)
    {
        if (empty($segments)) {
            return [];
        }

        foreach ($segments as &$segment) {
            $segment = preg_replace('/-/', ':', $segment, 1);
        }
        unset($segment);

        if (strpos(current($segments), $this->separator) === false) {
            $vars = [];
            $vars['ctrl'] = array_shift($segments);
            $vars['task'] = '';
            $vars['step'] = '';
        } else {
            $jsite = Factory::getApplication('site');
            $menus = $jsite->getMenu();
            $menu = $menus->getActive();
            if (!empty($menu) && !empty($menu->query)) {
                $vars = $menu->query;
            } else {
                $vars = [];
            }

            if (!isset($vars['ctrl'])) {
                $vars['ctrl'] = $vars['view'] ?? '';
            }
            if (!isset($vars['task'])) {
                $vars['task'] = $vars['layout'] ?? '';
            }
            if (!isset($vars['step'])) {
                $vars['step'] = '';
            }
        }

        if (!empty($segments)) {
            if (strpos(current($segments), $this->separator) === false) {
                $vars['task'] = array_shift($segments);
                if (!empty($segments) && strpos(current($segments), $this->separator) === false) {
                    $vars['step'] = array_shift($segments);
                }
            } elseif ($vars['ctrl'] === 'archive' && empty($vars['task'])) {
                $vars['task'] = 'view';
                $mail = array_shift($segments);
                [$id, $alias] = explode($this->separator, $mail, 2);
                $vars['id'] = $id;
            }
        }

        if ($vars['ctrl'] === 'frontlists' && $vars['task'] === 'settings') {
            $list = array_shift($segments);
            [$id, $alias] = explode($this->separator, $list, 2);
            $vars['listId'] = $id;
        }

        if ($vars['ctrl'] === 'frontusers' && $vars['task'] === 'unsubscribe') {
            $mail = array_shift($segments);
            [$id, $alias] = explode($this->separator, $mail, 2);
            $vars['mail_id'] = $id;
        }

        foreach ($segments as $position => $name) {
            if (strpos($name, $this->separator) === false) continue;

            [$arg, $val] = explode($this->separator, $name, 2);
            $vars[$arg] = $val;
            unset($segments[$position]);
        }

        return $vars;
    }

    private function getMailSEF(int $id): string
    {
        $mailClass = new MailClass();
        $mail = $mailClass->getOneById($id);

        return $id.$this->separator.acym_getAlias($mail->subject);
    }

    private function getListSEF(int $id): string
    {
        $listClass = new ListClass();
        $list = $listClass->getOneById($id);

        return $id.$this->separator.acym_getAlias($list->name);
    }
}
