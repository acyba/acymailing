<?php

namespace AcyMailing\Core;

use AcyMailing\Helpers\HeaderHelper;

abstract class AcymController extends AcymObject
{
    private $currentClass = null;
    private string $sessionName;

    protected array $breadcrumb = [];
    protected array $loadScripts = [];
    protected array $publicFrontTasks = [];
    protected array $allowedTasks = [];
    protected array $menuAlias = [];
    protected string $taskCalled = '';
    protected string $menuClass = '';

    public string $defaulttask = 'listing';
    public string $name = '';

    public function __construct()
    {
        parent::__construct();

        $classname = get_class($this);
        $classname = substr($classname, strrpos($classname, '\\') + 1);
        $ctrlpos = strpos($classname, 'Controller');
        $this->name = strtolower(substr($classname, 0, $ctrlpos));

        $currentClassName = 'AcyMailing\\Classes\\'.rtrim(ucfirst(str_replace(['Front', 'front'], '', $this->name)), 's').'Class';
        if (class_exists($currentClassName)) {
            $this->currentClass = new $currentClassName();
        }
        $this->sessionName = 'acym_filters_'.$this->name;
        $this->taskCalled = acym_getVar('string', 'task', '');

        $this->breadcrumb['AcyMailing'] = acym_completeLink('dashboard');
    }

    private function initSession(): void
    {
        acym_session();
        if (empty($_SESSION[$this->sessionName])) {
            $_SESSION[$this->sessionName] = [];
        }
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function getVarFiltersListing(string $type, string $varName, $default, bool $overrideIfNull = false)
    {
        if ($this->taskCalled === 'clearFilters') {
            return $default;
        }

        $this->initSession();
        $returnValue = acym_getVar($type, $varName);

        if (is_null($returnValue) && $overrideIfNull) $returnValue = $default;

        if (!is_null($returnValue)) {
            $_SESSION[$this->sessionName][$varName] = $returnValue;

            return $returnValue;
        }

        if (!empty($_SESSION[$this->sessionName][$varName])) {
            return $_SESSION[$this->sessionName][$varName];
        }

        return $default;
    }

    public function setVarFiltersListing(string $varName, int $value): void
    {
        acym_setVar($varName, $value);
        $this->initSession();
        $_SESSION[$this->sessionName][$varName] = $value;
    }

    /**
     * Called using data-task
     */
    public function clearFilters(): void
    {
        $this->initSession();
        $_SESSION[$this->sessionName] = [];

        $taskToCall = acym_getVar('string', 'cleartask', $this->defaulttask);
        $this->call($taskToCall);
    }

    public function call(string $task): void
    {
        // If not authorized, display message and redirect to dashboard
        if (!in_array($task, ['countResultsTotal', 'countGlobalBySegmentId', 'countResults']) && strpos($task, 'Ajax') === false && !acym_isAllowed($this->name)) {
            acym_enqueueMessage(acym_translation('ACYM_ACCESS_DENIED'), 'warning');
            acym_redirect(acym_completeLink('dashboard'));

            return;
        }

        // If task doesn't exist, redirect to default task + add message
        if (!method_exists($this, $task)) {
            acym_enqueueMessage(acym_translation('ACYM_NON_EXISTING_PAGE'), 'warning');
            $task = $this->defaulttask;
            acym_setVar('task', $task);
        }

        // Call the task
        $this->$task();
    }

    public function loadScripts(string $task): void
    {
        if (empty($this->loadScripts)) return;

        $scripts = [];
        if (!empty($this->loadScripts['all'])) {
            $scripts = $this->loadScripts['all'];
        }

        if (!empty($task) && !empty($this->loadScripts[$task])) {
            $scripts = array_merge($scripts, $this->loadScripts[$task]);
        }

        if (empty($scripts)) return;

        if (in_array('editor-wysid', $scripts)) {
            acym_addStyle(false, ACYM_CSS.'editorWYSID.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'editorWYSID.min.css'));
            acym_addScript(false, ACYM_JS.'editor_wysid_utils.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'editor_wysid_utils.min.js'));

            // Automatically add editor dependencies
            $scripts = array_merge($scripts, ['colorpicker', 'datepicker', 'thumbnail', 'foundation-email', 'parse-css', 'vue-prism-editor', 'masonry']);

            if (empty($scripts['vue-applications'])) {
                $scripts['vue-applications'] = ['code_editor'];
            } else {
                $scripts['vue-applications'][] = 'code_editor';
            }
        }

        if (in_array('sankey', $scripts)) {
            acym_addScript(false, ACYM_JS.'libraries/sankey.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'sankey.min.js'));
        }

        if (in_array('colorpicker', $scripts)) {
            acym_addScript(false, ACYM_JS.'libraries/spectrum.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'spectrum.min.js'));
            acym_addStyle(false, ACYM_CSS.'libraries/spectrum.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'libraries'.DS.'spectrum.min.css'));
        }

        if (in_array('datepicker', $scripts)) {
            // Must be loaded in the right order
            acym_addScript(false, ACYM_JS.'libraries/moment.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'moment.min.js'));
            acym_addScript(false, ACYM_JS.'libraries/rome.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'rome.min.js'));
            acym_addScript(false, ACYM_JS.'libraries/material-datetime-picker.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'material-datetime-picker.min.js'));
            acym_addStyle(false, ACYM_CSS.'libraries/material-datetime-picker.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'libraries'.DS.'material-datetime-picker.min.css'));
        }

        if (in_array('dtextPicker', $scripts)) {
            acym_addScript(false, ACYM_JS.'dtext_picker.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'dtext_picker.min.js'));
            acym_addStyle(false, ACYM_CSS.'dtext_picker.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'dtext_picker.min.css'));
        }

        if (in_array('thumbnail', $scripts)) {
            acym_addScript(false, ACYM_JS.'libraries/html2canvas.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'html2canvas.min.js'));
        }

        if (in_array('foundation-email', $scripts)) {
            acym_addStyle(false, ACYM_CSS.'libraries/foundation_email.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'libraries'.DS.'foundation_email.min.css'));
            acym_addStyle(true, acym_getEmailCssFixes());
        }

        if (in_array('parse-css', $scripts)) {
            acym_addScript(false, ACYM_JS.'libraries/parse-css.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'parse-css.min.js'));
        }

        if (in_array('masonry', $scripts)) {
            acym_addScript(false, ACYM_JS.'libraries/masonry.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'masonry.min.js'));
        }

        if (!empty($scripts['vue-applications'])) {
            //vuejs javascript library
            acym_addScript(false, ACYM_JS.'libraries/vuejs.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'vuejs.min.js'));
            acym_addScript(false, ACYM_JS.'libraries/vue-infinite-scroll.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'vue-infinite-scroll.min.js'));
            //All the component created to use in vue app (ex: select2 component)
            acym_addScript(false, ACYM_JS.'vue/vue_components.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'vue'.DS.'vue_components.min.js'));
            foreach ($scripts['vue-applications'] as $script) {
                acym_addScript(false, ACYM_JS.'vue/'.$script.'.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'vue'.DS.$script.'.min.js'));
            }
        }

        if (in_array('vue-prism-editor', $scripts)) {
            acym_addScript(false, ACYM_JS.'libraries/prism-editor.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'prism-editor.min.js'));
            acym_addScript(false, ACYM_JS.'libraries/prism.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'prism.min.js'));
            acym_addStyle(false, ACYM_CSS.'libraries/prism.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'libraries'.DS.'prism.min.css'));
            acym_addScript(false, ACYM_JS.'libraries/beautify-html.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'beautify-html.min.js'));
        }
    }

    public function setDefaultTask(string $task): void
    {
        $this->defaulttask = $task;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function display(array $data = []): void
    {
        if (acym_isAdmin()) {
            if (!acym_isNoTemplate()) {
                $header = new HeaderHelper();
                $data['header'] = $header->display($this->breadcrumb);
            }
            $viewNamespace = 'AcyMailing\\Views\\';
        } else {
            $viewNamespace = 'AcyMailing\\FrontViews\\';
        }

        $viewName = ucfirst($this->getName());
        $viewNamespace .= $viewName.'\\'.$viewName.'View';
        $view = new $viewNamespace();
        $view->display($this, $data);
    }

    /**
     * Called using data-task
     */
    public function cancel(): void
    {
        acym_setVar('layout', 'listing');
        $this->display();
    }

    public function listing(): void
    {
        acym_setVar('layout', 'listing');

        $this->display();
    }

    public function edit(): void
    {
        $nextstep = acym_getVar('string', 'nextstep', '');
        if (empty($nextstep)) {
            $nextstep = acym_getVar('string', 'step', '');
        }

        if (empty($nextstep)) {
            acym_setVar('layout', 'edit');

            $this->display();
        } else {
            acym_setVar('step', $nextstep);

            $this->$nextstep();
        }
    }

    public function apply(): void
    {
        if (method_exists($this, 'store')) {
            $this->store();
        }

        $this->edit();
    }

    public function add(): void
    {
        acym_setVar('cid', []);
        acym_setVar('layout', 'form');

        $this->display();
    }

    public function save(): void
    {
        $step = acym_getVar('string', 'step', '');

        if (!empty($step)) {
            $saveMethod = 'save'.ucfirst($step);
            if (!method_exists($this, $saveMethod)) {
                die('Save method '.acym_escape($saveMethod).' not found');
            }

            $this->$saveMethod();

            return;
        }

        if (method_exists($this, 'store')) {
            $this->store();
        }

        $this->listing();
    }

    public function delete(): void
    {
        acym_checkToken();
        $ids = acym_getVar('array', 'elements_checked', []);
        $allChecked = acym_getVar('string', 'checkbox_all');
        $currentPage = explode('_', acym_getVar('string', 'page', ''));
        $pageNumber = $this->getVarFiltersListing('int', end($currentPage).'_pagination_page', 1);

        if (!empty($ids) && !empty($this->currentClass)) {
            $this->currentClass->delete($ids);
            if ($allChecked === 'on') {
                $this->setVarFiltersListing(end($currentPage).'_pagination_page', $pageNumber - 1);
            }
        }

        if (!acym_getVar('bool', 'no_listing', false)) {
            $this->listing();
        }
    }

    public function setActive(): void
    {
        acym_checkToken();
        $ids = acym_getVar('array', 'elements_checked', []);

        if (!empty($ids) && !empty($this->currentClass)) {
            $this->currentClass->setActive($ids);
        }

        $this->listing();
    }

    public function setInactive(): void
    {
        acym_checkToken();
        $ids = acym_getVar('array', 'elements_checked', []);

        if (!empty($ids) && !empty($this->currentClass)) {
            $this->currentClass->setInactive($ids);
        }

        $this->listing();
    }

    public function getMatchingElementsFromData(array $requestData, string &$status, int &$page, string $class = ''): array
    {
        $className = 'AcyMailing\\Classes\\'.ucfirst(strtolower($class)).'Class';
        $classElement = empty($class) ? $this->currentClass : new $className();
        $matchingElement = $classElement->getMatchingElements($requestData);

        // No result and no search used, we revert to default listing (all)
        if (empty($matchingElement['elements'])) {
            if (!empty($status) && empty($requestData['search']) && empty($requestData['tag'])) {
                $status = '';
                $requestData['status'] = $status;
            } elseif (!empty($requestData['offset'])) {
                $page = 1;
                $requestData['offset'] = 0;
            } else {
                return $matchingElement;
            }

            $matchingElement = $classElement->getMatchingElements($requestData);
        }

        $matchingElement['total']->total = intval($matchingElement['total']->total);

        return $matchingElement;
    }

    /**
     * Used in front/acym.php
     */
    public function checkTaskFront(string $task = ''): void
    {
        // For cron tasks created by users on their own server, for Joomla 4
        if ($this->getName() === 'cron') {
            $task = 'cron';
        }

        if (empty($task) && !empty($this->defaulttask)) {
            $task = $this->defaulttask;
        }

        // Handle whitelisted tasks
        if (in_array($task, $this->publicFrontTasks)) {
            $this->$task();

            return;
        }

        // No front-end management for WordPress users
        if (empty(acym_currentUserId()) || ACYM_CMS !== 'joomla' || !acym_level(ACYM_ENTERPRISE)) {
            acym_redirect(acym_rootURI(), 'Front-end management not available');
        }

        // If there is no menu, someone typed the URL manually
        $currentMenu = acym_getMenu();
        if (empty($currentMenu)) {
            acym_redirect(acym_rootURI(), 'Direct access denied');
        }

        // We whitelist specific routes when having access to specific menus
        if ($this->isTaskAllowed($currentMenu->link, $task)) {
            $this->$task();

            return;
        }

        // Blacklist tasks by default
        acym_redirect(acym_rootURI(), 'Unknown route, access denied');
    }

    private function isTaskAllowed(string $menuUrl, string $task): bool
    {
        if (!empty($this->allowedTasks[$menuUrl]) && in_array($task, $this->allowedTasks[$menuUrl])) {
            return true;
        }

        if (!empty($this->menuAlias[$menuUrl])) {
            $menuUrl = $this->menuAlias[$menuUrl];

            return $this->isTaskAllowed($menuUrl, $task);
        }

        return false;
    }

    protected function prepareMultilingualOption(array &$data): void
    {
        if (!acym_isMultilingual()) return;

        $data['translation_languages'] = acym_getMultilingualLanguages();
    }
}
