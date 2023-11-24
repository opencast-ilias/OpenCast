<?php

declare(strict_types=1);

use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\Plugins\Opencast\API\API;

/**
 * Class xoctGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
abstract class xoctGUI
{
    public const CMD_STANDARD = 'index';
    public const CMD_ADD = 'add';
    public const CMD_SAVE = 'save';
    public const CMD_CREATE = 'create';
    public const CMD_EDIT = 'edit';
    public const CMD_UPDATE = 'update';
    public const CMD_CONFIRM = 'confirmDelete';
    public const CMD_DELETE = 'delete';
    public const CMD_CANCEL = 'cancel';
    public const CMD_VIEW = 'view';
    /**
     * @var \ILIAS\DI\HTTPServices
     */
    protected $http;
    /**
     * @var ilGlobalTemplateInterface
     */
    protected $main_tpl;
    /**
     * @var API
     */
    protected $api;
    /**
     * @var ilOpenCastPlugin
     */
    protected $plugin;
    /**
     * @var OpencastDIC
     */
    protected $container;
    /**
     * @var \ilCtrl
     */
    protected $ctrl;

    public function __construct()
    {
        global $DIC, $opencastContainer;
        $this->api = $opencastContainer[API::class];
        $this->ctrl = $DIC->ctrl();
        $this->container = OpencastDIC::getInstance();
        $this->plugin = $this->container->plugin();
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->http = $DIC->http();
    }

    public function executeCommand(): void
    {
        $this->ctrl->getNextClass();
        $cmd = $this->ctrl->getCmd(self::CMD_STANDARD);
        $this->performCommand($cmd);
    }


    protected function performCommand(string $cmd): void
    {
        $this->{$cmd}();
    }

    abstract protected function index(): void;

    abstract protected function add(): void;

    abstract protected function create(): void;

    abstract protected function edit(): void;

    abstract protected function update(): void;

    abstract protected function confirmDelete(): void;

    abstract protected function delete(): void;

    protected function cancel()
    {
        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     * @param $a
     * @param $b
     *
     * @return int
     */
    protected function compareStdClassByName($a, $b)
    {
        return strcasecmp($a->name, $b->name);
    }
}
