<?php

use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\UI\Renderer;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\UI\PaellaConfig\PaellaConfigFormBuilder;
use srag\Plugins\Opencast\LegacyHelpers\TranslatorTrait;

/**
 * Class xoctConfGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctConfGUI : xoctMainGUI
 */
class xoctConfGUI extends xoctGUI
{
    use TranslatorTrait;

    public const CMD_PLAYER = 'player';
    public const CMD_UPDATE_PLAYER = 'updatePlayer';

    /**
     * @var Renderer
     */
    private $renderer;
    /**
     * @var UploadHandler
     */
    private $fileUploadHandler;
    /**
     * @var PaellaConfigFormBuilder
     */
    private $paellConfigFormBuilder;
    /**
     * @var \ilTabsGUI
     */
    private $tabs;
    /**
     * @var \ilGlobalTemplateInterface
     */
    private $main_tpl;
    /**
     * @var \ILIAS\HTTP\Services
     */
    private $http;

    /**
     * @param Renderer                $renderer
     * @param UploadHandler           $fileUploadHandler
     * @param PaellaConfigFormBuilder $paellConfigFormBuilder
     */
    public function __construct(
        Renderer $renderer,
        UploadHandler $fileUploadHandler,
        PaellaConfigFormBuilder $paellConfigFormBuilder
    ) {
        global $DIC;
        parent::__construct();
        $this->tabs = $DIC->tabs();
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->http = $DIC->http();
        $this->renderer = $renderer;
        $this->fileUploadHandler = $fileUploadHandler;
        $this->paellConfigFormBuilder = $paellConfigFormBuilder;
    }

    public function executeCommand()
    {
        $nextClass = $this->ctrl->getNextClass();

        switch ($nextClass) {
            case strtolower(xoctFileUploadHandler::class):
                if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
                    ilUtil::sendFailure($this->plugin->txt("msg_no_access"), true);
                    $this->cancel();
                }
                $this->ctrl->forwardCommand($this->fileUploadHandler);
                break;
            default:
                $cmd = $this->ctrl->getCmd(self::CMD_STANDARD);
                $this->performCommand($cmd);
                break;
        }
    }

    public function txt(
        string $key,
        string $module = "",
        array $placeholders = [],
        bool $plugin = true,
        string $lang = "",
        string $default = "MISSING %s"
    ) {
        return $this->translate('config_' . $key, $module, $placeholders, $plugin, $lang, $default);
    }

    /**
     *
     */
    public function index()
    {
        $this->ctrl->saveParameter($this, 'subtab_active');
        $subtab_active = $_GET['subtab_active'] ?: xoctMainGUI::SUBTAB_API;
        $this->tabs->setSubTabActive($subtab_active);
        $xoctConfFormGUI = new xoctConfFormGUI($this, $subtab_active);
        $xoctConfFormGUI->fillForm();
        $this->main_tpl->setContent($xoctConfFormGUI->getHTML());
    }

    /**
     * Subtab Player has an own method, since it is rendered with the UI service and not with xoctConfFormGUI
     * @return void
     */
    protected function player()
    {
        $this->ctrl->saveParameter($this, 'subtab_active');
        $subtab_active = $_GET['subtab_active'] ?: xoctMainGUI::SUBTAB_API;
        $this->tabs->setSubTabActive($subtab_active);
        $form = $this->paellConfigFormBuilder->buildForm($this->ctrl->getFormAction($this, self::CMD_UPDATE_PLAYER));
        $this->main_tpl->setContent($this->renderer->render($form));
    }

    protected function updatePlayer()
    {
        $this->ctrl->saveParameter($this, 'subtab_active');
        $form = $this->paellConfigFormBuilder->buildForm($this->ctrl->getFormAction($this, self::CMD_UPDATE_PLAYER))
                                             ->withRequest($this->http->request());
        $data = $form->getData();
        if (!$data) {
            $this->main_tpl->setContent($this->renderer->render($form));
            return;
        }

        if (isset($data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_OPTION])) {
            $paella_player_option = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_OPTION][0];
            PluginConfig::set(PluginConfig::F_PAELLA_OPTION, $paella_player_option);
            if ($paella_player_option === PluginConfig::PAELLA_OPTION_URL) {
                PluginConfig::set(
                    PluginConfig::F_PAELLA_URL,
                    $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_OPTION][1]['url']
                );
            }
        }
        if (isset($data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_LIVE_OPTION])) {
            $paella_player_option = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_LIVE_OPTION][0];
            PluginConfig::set(PluginConfig::F_PAELLA_OPTION_LIVE, $paella_player_option);
            if ($paella_player_option === PluginConfig::PAELLA_OPTION_URL) {
                PluginConfig::set(
                    PluginConfig::F_PAELLA_URL_LIVE,
                    $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_LIVE_OPTION][1]['url']
                );
            }
        }

        $this->ctrl->redirect($this, self::CMD_PLAYER);
    }

    /**
     *
     */
    protected function update()
    {
        $this->ctrl->saveParameter($this, 'subtab_active');
        $subtab_active = $_GET['subtab_active'] ? $_GET['subtab_active'] : xoctMainGUI::SUBTAB_API;
        $xoctConfFormGUI = new xoctConfFormGUI($this, $subtab_active);
        $xoctConfFormGUI->setValuesByPost();
        if ($xoctConfFormGUI->saveObject()) {
            ilUtil::sendSuccess($this->txt('msg_success'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        }
        $this->main_tpl->setContent($xoctConfFormGUI->getHTML());
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
