<?php

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
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

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
     * @var \ilCtrlInterface
     */
    protected $ctrl;

    public function __construct()
    {
        global $DIC, $opencastContainer;
        $this->api = $opencastContainer[API::class];
        $this->ctrl = $DIC->ctrl();
        $this->container = OpencastDIC::getInstance();
        $this->plugin = $this->container->plugin();
    }

    public function executeCommand(): void
    {
        $this->ctrl->getNextClass();
        $cmd = $this->ctrl->getCmd(self::CMD_STANDARD);
        $this->performCommand($cmd);
    }

    /**
     * @param $cmd
     */
    protected function performCommand($cmd)
    {
        $this->{$cmd}();
    }

    abstract protected function index();

    abstract protected function add();

    abstract protected function create();

    abstract protected function edit();

    abstract protected function update();

    abstract protected function confirmDelete();

    abstract protected function delete();

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
