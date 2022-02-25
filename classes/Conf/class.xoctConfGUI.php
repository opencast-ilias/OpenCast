<?php

use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\UI\Renderer;
use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\UI\PaellaConfig\PaellaConfigFormBuilder;

/**
 * Class xoctConfGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctConfGUI : xoctMainGUI
 */
class xoctConfGUI extends xoctGUI
{

    const CMD_PLAYER = 'player';
    const CMD_UPDATE_PLAYER = 'updatePlayer';

    /**
     * @var Renderer
     */
    private $renderer;
    /**
     * @var ilCtrl
     */
    private $ctrl;
    /**
     * @var UploadHandler
     */
    private $fileUploadHandler;
    /**
     * @var PaellaConfigFormBuilder
     */
    private $paellConfigFormBuilder;

    /**
     * @param Renderer $renderer
     * @param ilCtrl $ctrl
     * @param UploadHandler $fileUploadHandler
     * @param PaellaConfigFormBuilder $paellConfigFormBuilder
     */
    public function __construct(Renderer $renderer, ilCtrl $ctrl, UploadHandler $fileUploadHandler, PaellaConfigFormBuilder $paellConfigFormBuilder)
    {
        $this->renderer = $renderer;
        $this->ctrl = $ctrl;
        $this->fileUploadHandler = $fileUploadHandler;
        $this->paellConfigFormBuilder = $paellConfigFormBuilder;
    }


    public function executeCommand()
    {
        $nextClass = self::dic()->ctrl()->getNextClass();

        switch ($nextClass) {
            case strtolower(xoctFileUploadHandler::class):
                if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
                    ilUtil::sendFailure(self::plugin()->getPluginObject()->txt("msg_no_access"), true);
                    $this->cancel();
                }
                self::dic()->ctrl()->forwardCommand($this->fileUploadHandler);
                break;
            default:
                $cmd = self::dic()->ctrl()->getCmd(self::CMD_STANDARD);
                $this->performCommand($cmd);
                break;
        }
    }


    public function txt(string $key, string $module = "", array $placeholders = [], bool $plugin = true, string $lang = "", string $default = "MISSING %s")
    {
        return self::plugin()->translate('config_' . $key, $module, $placeholders, $plugin, $lang, $default);
    }

    /**
     *
     */
    public function index()
    {
        self::dic()->ctrl()->saveParameter($this, 'subtab_active');
        $subtab_active = $_GET['subtab_active'] ?: xoctMainGUI::SUBTAB_API;
        self::dic()->tabs()->setSubTabActive($subtab_active);
        $xoctConfFormGUI = new xoctConfFormGUI($this, $subtab_active);
        $xoctConfFormGUI->fillForm();
        self::dic()->ui()->mainTemplate()->setContent($xoctConfFormGUI->getHTML());
    }

    /**
     * Subtab Player has an own method, since it is rendered with the UI service and not with xoctConfFormGUI
     * @return void
     */
    protected function player()
    {
        self::dic()->ctrl()->saveParameter($this, 'subtab_active');
        $subtab_active = $_GET['subtab_active'] ?: xoctMainGUI::SUBTAB_API;
        self::dic()->tabs()->setSubTabActive($subtab_active);
        $form = $this->paellConfigFormBuilder->buildForm($this->ctrl->getFormAction($this, self::CMD_UPDATE_PLAYER));
        self::dic()->ui()->mainTemplate()->setContent($this->renderer->render($form));
    }

    protected function updatePlayer()
    {
        self::dic()->ctrl()->saveParameter($this, 'subtab_active');
        $form = $this->paellConfigFormBuilder->buildForm($this->ctrl->getFormAction($this, self::CMD_UPDATE_PLAYER))
            ->withRequest(self::dic()->http()->request());
        $data = $form->getData();
        if (!$data) {
            self::dic()->ui()->mainTemplate()->setContent($this->renderer->render($form));
            return;
        }

        if (isset($data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_OPTION])) {
            $paella_player_option = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_OPTION][0];
            PluginConfig::set(PluginConfig::F_PAELLA_OPTION, $paella_player_option);
            if ($paella_player_option === PluginConfig::PAELLA_OPTION_URL) {
                PluginConfig::set(PluginConfig::F_PAELLA_URL, $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_OPTION][1]['url']);
            } else if ($paella_player_option === PluginConfig::PAELLA_OPTION_FILE) {
                if ($file_id = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_OPTION][1]['file'][0]) {
                    PluginConfig::set(PluginConfig::F_PAELLA_FILE_ID, $file_id);
                }
            }
        }
        if (isset($data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_LIVE_OPTION])) {
            $paella_player_option = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_LIVE_OPTION][0];
            PluginConfig::set(PluginConfig::F_PAELLA_OPTION_LIVE, $paella_player_option);
            if ($paella_player_option === PluginConfig::PAELLA_OPTION_URL) {
                PluginConfig::set(PluginConfig::F_PAELLA_URL_LIVE, $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_LIVE_OPTION][1]['url']);
            } else if ($paella_player_option === PluginConfig::PAELLA_OPTION_FILE) {
                if ($file_id = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_LIVE_OPTION][1]['file'][0]) {
                    PluginConfig::set(PluginConfig::F_PAELLA_FILE_ID_LIVE, $file_id);
                }
            }
        }

        self::dic()->ctrl()->redirect($this, self::CMD_PLAYER);
    }


    /**
     *
     */
    protected function update()
    {
        self::dic()->ctrl()->saveParameter($this, 'subtab_active');
        $subtab_active = $_GET['subtab_active'] ? $_GET['subtab_active'] : xoctMainGUI::SUBTAB_API;
        $xoctConfFormGUI = new xoctConfFormGUI($this, $subtab_active);
        $xoctConfFormGUI->setValuesByPost();
        if ($xoctConfFormGUI->saveObject()) {
            ilUtil::sendSuccess($this->txt('msg_success'), true);
            self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
        }
        self::dic()->ui()->mainTemplate()->setContent($xoctConfFormGUI->getHTML());
    }



    /**
     *
     */
    protected function confirmDelete()
    {
    }


    /**
     *
     */
    protected function delete()
    {
    }


    /**
     *
     */
    protected function add()
    {
    }


    /**
     *
     */
    protected function create()
    {
    }


    /**
     *
     */
    protected function edit()
    {
    }
}
