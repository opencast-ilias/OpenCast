<?php

use srag\Plugins\Opencast\Chat\GUI\ChatGUI;
use srag\Plugins\Opencast\Chat\GUI\ChatHistoryGUI;
use srag\Plugins\Opencast\Chat\Model\ChatroomAR;
use srag\Plugins\Opencast\Chat\Model\MessageAR;
use srag\Plugins\Opencast\Chat\Model\TokenAR;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Event\EventRepository;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;
use srag\Plugins\Opencast\Util\Player\PaellaConfigService;
use srag\Plugins\Opencast\Util\Player\PaellaConfigServiceFactory;
use srag\Plugins\Opencast\Util\Player\PlayerDataBuilderFactory;
use srag\Plugins\Opencast\Util\FileTransfer\PaellaConfigStorageService;

/**
 * Class xoctPlayerGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctPlayerGUI extends xoctGUI
{
    public const CMD_STREAM_VIDEO = 'streamVideo';

    public const IDENTIFIER = 'eid';

    public const ROLE_MASTER = "presenter";
    public const ROLE_SLAVE = "presentation";
    /**
     * @var ObjectSettings
     */
    protected $objectSettings;
    /**
     * @var PublicationUsageRepository
     */
    protected $publication_usage_repository;
    /**
     * @var EventRepository
     */
    private $event_repository;
    /**
     * @var PaellaConfigService
     */
    private $paellaConfigService;
    /**
     * @var \ilObjUser
     */
    private $user;

    public function __construct(
        EventRepository $event_repository,
        PaellaConfigStorageService $paellaConfigStorageService,
        PaellaConfigServiceFactory $paellaConfigServiceFactory,
        ?ObjectSettings $objectSettings = null
    ) {
        global $DIC;
        parent::__construct();
        $this->user = $DIC->user();
        $this->publication_usage_repository = new PublicationUsageRepository();
        $this->objectSettings = $objectSettings instanceof ObjectSettings ? $objectSettings : new ObjectSettings();
        $this->event_repository = $event_repository;
        $this->paellaConfigService = $paellaConfigServiceFactory->get();
    }

    /**
     * @throws DICException
     * @throws arException
     * @throws ilTemplateException
     */
    public function streamVideo(): void
    {
        $event = $this->event_repository->find(filter_input(INPUT_GET, self::IDENTIFIER));
        if (!PluginConfig::getConfig(PluginConfig::F_INTERNAL_VIDEO_PLAYER) && !$event->isLiveEvent()) {
            // redirect to opencast
            header('Location: ' . $event->publications()->getPlayerLink());
            exit;
        }

        try {
            $data = PlayerDataBuilderFactory::getInstance()->getBuilder($event)->buildStreamingData();
        } catch (xoctException $e) {
            xoctLog::getInstance()->logError($e->getCode(), $e->getMessage());
            xoctLog::getInstance()->logStack($e->getTraceAsString());
            echo $e->getMessage();
            exit;
        }

        $tpl = $this->plugin->getTemplate("paella_player.html", true, true);
        $tpl->setVariable("TITLE", $event->getTitle());
        $tpl->setVariable("DATA", json_encode($data));
        $tpl->setVariable("JS_CONFIG", json_encode($this->buildJSConfig($event)));

        if ($event->isLiveEvent()) {
            $tpl->setVariable(
                'LIVE_WAITING_TEXT',
                self::plugin()->translate(
                    'live_waiting_text',
                    'event',
                    [date('H:i', $event->getScheduling()->getStart()->getTimestamp())]
                )
            );
            $tpl->setVariable('LIVE_INTERRUPTED_TEXT', self::plugin()->translate('live_interrupted_text', 'event'));
            $tpl->setVariable('LIVE_OVER_TEXT', self::plugin()->translate('live_over_text', 'event'));
        }

        if ($this->isChatVisible()) {
            $this->initChat($event, $tpl);
        } else {
            $tpl->setVariable(
                "STYLE_SHEET_LOCATION",
                $this->plugin->getDirectory() . "/templates/default/player.css"
            );
        }

        setcookie('lastProfile', null, ['expires' => -1]);
        echo $tpl->get();
        exit();
    }

    protected function buildJSConfig(Event $event): stdClass
    {
        $js_config = new stdClass();
        $paella_config = $this->paellaConfigService->getEffectivePaellaPlayerUrl();
        $js_config->paella_config_file = $paella_config['url'];
        $js_config->paella_config_livestream_type =  PluginConfig::getConfig(PluginConfig::F_LIVESTREAM_TYPE) ?? 'hls';
        $js_config->paella_config_resources_path = PluginConfig::PAELLA_RESOURCES_PATH;
        $js_config->paella_config_fallback_captions = PluginConfig::getConfig(PluginConfig::F_PAELLA_FALLBACK_CAPTIONS) ?? [];
        $js_config->paella_config_fallback_langs = PluginConfig::getConfig(PluginConfig::F_PAELLA_FALLBACK_LANGS) ?? [] ;

        $js_config->paella_config_info = $paella_config['info'];
        $js_config->paella_config_is_warning = $paella_config['warn'];

        $paella_themes = $this->paellaConfigService->getPaellaPlayerThemeUrl($event->isLiveEvent());
        $js_config->paella_theme = $paella_themes['theme_url'];
        $js_config->paella_theme_live = $paella_themes['theme_live_url'];
        $js_config->paella_theme_info = $paella_themes['info'];

        $js_config->paella_preview_fallback = $this->paellaConfigService->getPaellaPlayerPreviewFallback();

        if ($event->isLiveEvent()) {
            // script to check live stream availability
            $js_config->check_script_hls = $this->plugin->getDirectory() . '/src/Util/check_hls_status.php';
            $js_config->is_live_stream = true;
            $js_config->event_start = $event->getScheduling()->getStart()->getTimestamp();
            $js_config->event_end = $event->getScheduling()->getEnd()->getTimestamp();
        }
        return $js_config;
    }

    protected function isChatVisible(): bool
    {
        return !filter_input(INPUT_GET, 'force_no_chat')
            && PluginConfig::getConfig(PluginConfig::F_ENABLE_CHAT)
            && $this->objectSettings->isChatActive();
    }

    /**
     * @throws DICException
     * @throws arException
     * @throws ilTemplateException
     */
    protected function initChat(Event $event, ilTemplate $tpl)
    {
        $ChatroomAR = ChatroomAR::findBy($event->getIdentifier(), $this->objectSettings->getObjId());
        if ($event->isLiveEvent()) {
            $tpl->setVariable(
                "STYLE_SHEET_LOCATION", $this->plugin->getDirectory() . "/templates/default/player_w_chat.css"
            );
            $ChatroomAR = ChatroomAR::findOrCreate($event->getIdentifier(), $this->objectSettings->getObjId());
            $public_name = $this->user->hasPublicProfile() ?
                $this->user->getFirstname() . " " . $this->user->getLastname()
                : $this->user->getLogin();
            $TokenAR = TokenAR::getNewFrom($ChatroomAR->getId(), $this->user->getId(), $public_name);
            $ChatGUI = new ChatGUI($TokenAR);
            $tpl->setVariable('CHAT', $ChatGUI->render(true));
        } elseif ($ChatroomAR && MessageAR::where(["chat_room_id" => $ChatroomAR->getId()])->hasSets()) {
            // show chat history for past live events
            $tpl->setVariable(
                "STYLE_SHEET_LOCATION",
                $this->plugin->getDirectory() . "/templates/default/player_w_chat.css"
            );
            $ChatHistoryGUI = new ChatHistoryGUI($ChatroomAR->getId());
            $tpl->setVariable('CHAT', $ChatHistoryGUI->render(true));
        }
    }

    /**
     * @param $key
     *
     * @return string
     * @throws DICException
     */
    public function txt($key)
    {
        return self::plugin()->translate('event_' . $key);
    }

    protected function index()
    {
    }

    protected function add()
    {
    }

    protected function create()
    {
    }

    protected function edit()
    {
    }

    protected function update()
    {
    }

    protected function confirmDelete()
    {
    }

    protected function delete()
    {
    }
}
