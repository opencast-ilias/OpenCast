<?php

declare(strict_types=1);

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
use srag\Plugins\Opencast\LegacyHelpers\TranslatorTrait;
use ILIAS\DI\HTTPServices;
use srag\Plugins\Opencast\Util\OutputResponse;

/**
 * Class xoctPlayerGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctPlayerGUI extends xoctGUI
{
    use TranslatorTrait;
    use OutputResponse;

    public const CMD_STREAM_VIDEO = 'streamVideo';

    public const IDENTIFIER = 'eid';

    public const ROLE_MASTER = "presenter";
    public const ROLE_SLAVE = "presentation";
    /**
     * @var bool
     */
    private $force_no_chat;
    /**
     * @var string|null
     */
    private $identifier;
    /**
     * @var ObjectSettings
     */
    protected $object_settings;
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
        ?ObjectSettings $object_settings = null
    ) {
        global $DIC;
        parent::__construct();
        $this->user = $DIC->user();
        $this->publication_usage_repository = new PublicationUsageRepository();
        $this->object_settings = $object_settings instanceof ObjectSettings ? $object_settings : new ObjectSettings();
        $this->event_repository = $event_repository;
        $this->paellaConfigService = $paellaConfigServiceFactory->get();
        $this->identifier = $this->http->request()->getQueryParams()[self::IDENTIFIER] ?? null;
        $this->force_no_chat = (bool) ($this->http->request()->getQueryParams()['force_no_chat'] ?? false);
    }

    /**
     * @throws arException
     * @throws ilTemplateException
     */
    public function streamVideo(): void
    {
        if (empty($this->identifier)) {
            $this->sendReponse("Error: invalid identifier");
        }
        $event = $this->event_repository->find($this->identifier);
        if (!PluginConfig::getConfig(PluginConfig::F_INTERNAL_VIDEO_PLAYER) && !$event->isLiveEvent()) {
            // redirect to opencast
            header('Location: ' . $event->publications()->getPlayerLink());
            $this->closeResponse();
        }

        try {
            $data = PlayerDataBuilderFactory::getInstance()->getBuilder($event)->buildStreamingData();
        } catch (xoctException $e) {
            xoctLog::getInstance()->logError($e->getCode(), $e->getMessage());
            xoctLog::getInstance()->logStack($e->getTraceAsString());
            $this->sendReponse("Error: " . $e->getMessage());
        }

        $jquery_path = iljQueryUtil::getLocaljQueryPath();
        $ilias_basic_js_path = './Services/JavaScript/js/Basic.js';
        $tpl = $this->plugin->getTemplate("paella_player.html", true, true);

        $tpl->setVariable("JQUERY_PATH", $jquery_path);
        $tpl->setVariable("ILIAS_BASIC_JS_PATH", $ilias_basic_js_path);

        $tpl->setVariable("TITLE", $event->getTitle());
        $tpl->setVariable("DATA", json_encode($data));
        $tpl->setVariable("JS_CONFIG", json_encode($this->buildJSConfig($event)));

        if ($event->isLiveEvent()) {
            $tpl->setVariable(
                'LIVE_WAITING_TEXT',
                $this->translate(
                    'live_waiting_text',
                    'event',
                    [date('H:i', $event->getScheduling()->getStart()->getTimestamp())]
                )
            );
            $tpl->setVariable('LIVE_INTERRUPTED_TEXT', $this->translate('live_interrupted_text', 'event'));
            $tpl->setVariable('LIVE_OVER_TEXT', $this->translate('live_over_text', 'event'));
        }

        $prev_chatroom_id = $this->eventHasChatRecord($event);
        if ($this->isChatVisible($event, !is_null($prev_chatroom_id))) {
            $this->initChat($event, $tpl, $prev_chatroom_id);
        } else {
            $tpl->setVariable(
                "STYLE_SHEET_LOCATION",
                $this->plugin->getDirectory() . "/templates/default/player.css"
            );
        }

        setcookie('lastProfile', '', ['expires' => -1]);
        $this->sendReponse($tpl->get());
    }

    /**
     * Function to determine whether the event has chat records.
     *
     * @param Event $event the event
     * @return int|null if true the chat room id is returned, otherwise null
     */
    private function eventHasChatRecord(Event $event): ?int
    {
        $ChatroomAR = ChatroomAR::findBy($event->getIdentifier(), $this->object_settings->getObjId());
        $has_chat_history = $ChatroomAR && MessageAR::where(["chat_room_id" => $ChatroomAR->getId()])->hasSets();
        return $has_chat_history ? (int) $ChatroomAR->getId() : null;
    }

    protected function buildJSConfig(Event $event): stdClass
    {
        $js_config = new stdClass();
        $paella_config = $this->paellaConfigService->getEffectivePaellaPlayerUrl();
        $js_config->paella_config_file = $paella_config['url'];
        $js_config->paella_config_livestream_type = PluginConfig::getConfig(PluginConfig::F_LIVESTREAM_TYPE) ?? 'hls';
        $js_config->paella_config_livestream_buffered =
            PluginConfig::getConfig(PluginConfig::F_LIVESTREAM_BUFFERED) ?? false;
        $js_config->paella_config_resources_path = PluginConfig::PAELLA_RESOURCES_PATH;
        $js_config->paella_config_fallback_captions = PluginConfig::getConfig(
            PluginConfig::F_PAELLA_FALLBACK_CAPTIONS
        ) ?? [];
        $js_config->paella_config_fallback_langs = PluginConfig::getConfig(PluginConfig::F_PAELLA_FALLBACK_LANGS) ?? [];

        $js_config->paella_config_info = $paella_config['info'];
        $js_config->paella_config_is_warning = $paella_config['warn'];

        $paella_themes = $this->paellaConfigService->getPaellaPlayerThemeUrl($event->isLiveEvent());
        $js_config->paella_theme = $paella_themes['theme_url'];
        $js_config->paella_theme_live = $paella_themes['theme_live_url'];
        $js_config->paella_theme_info = $paella_themes['info'];

        $js_config->paella_preview_fallback = $this->paellaConfigService->getPaellaPlayerPreviewFallback();

        $js_config->prevent_video_download = (bool) PluginConfig::getConfig(PluginConfig::F_PAELLA_PREVENT_VIDEO_DOWNLOAD) ?? false;

        if ($event->isLiveEvent()) {
            // script to check live stream availability
            $js_config->check_script_hls = $this->plugin->getDirectory() . '/src/Util/check_hls_status.php';
            $js_config->is_live_stream = true;
            $js_config->event_start = $event->getScheduling()->getStart()->getTimestamp();
            $js_config->event_end = $event->getScheduling()->getEnd()->getTimestamp();
        }
        return $js_config;
    }

    /**
     * Function to check whether the chat must be provided to the user based on the following conditions:
     * - Event must be identified as Live or has chat records already (was live before)
     * - The "Live Streams" config must be activated.
     * - The "Activate Chat" config must be activated.
     * - The "Chat for live events" in series object settings must be activated.
     *
     * @param Event $event the event object to check whether the event is live or not.
     * @param bool $has_chat_history whether the event has a chat history
     * @return bool whether the chat should be visible.
     */
    protected function isChatVisible(Event $event, bool $has_chat_history = false): bool
    {
        return !$this->force_no_chat
            && ($event->isLiveEvent() || $has_chat_history) // The event must be either live or has chat history!
            && PluginConfig::getConfig(PluginConfig::F_ENABLE_LIVE_STREAMS) // The Live Streams config must be activated.
            && PluginConfig::getConfig(PluginConfig::F_ENABLE_CHAT) // The Chat config must be activated.
            && $this->object_settings->isChatActive(); // The series object settings must allow the chat.
    }

    /**
     * @throws arException
     * @throws ilTemplateException
     */
    protected function initChat(Event $event, ilTemplate $tpl, ?int $prev_chatroom_id = null)
    {
        if ($event->isLiveEvent()) {
            // For running live events, provide a clean chat!
            $tpl->setVariable(
                "STYLE_SHEET_LOCATION",
                $this->plugin->getDirectory() . "/templates/default/player_w_chat.css"
            );
            $ChatroomAR = ChatroomAR::findOrCreate($event->getIdentifier(), $this->object_settings->getObjId());
            $public_name = $this->user->hasPublicProfile() ?
                $this->user->getFirstname() . " " . $this->user->getLastname()
                : $this->user->getLogin();
            $TokenAR = TokenAR::getNewFrom($ChatroomAR->getId(), $this->user->getId(), $public_name);
            $ChatGUI = new ChatGUI($TokenAR);
            $tpl->setVariable('CHAT', $ChatGUI->render(true));
        } elseif (!is_null($prev_chatroom_id)) {
            // Show chat history for past live events!
            $tpl->setVariable(
                "STYLE_SHEET_LOCATION",
                $this->plugin->getDirectory() . "/templates/default/player_w_chat.css"
            );
            $ChatHistoryGUI = new ChatHistoryGUI($prev_chatroom_id);
            $tpl->setVariable('CHAT', $ChatHistoryGUI->render(true));
        }
    }


    public function txt(string $key): string
    {
        return $this->translate('event_' . $key);
    }

    protected function index(): void
    {
    }

    protected function add(): void
    {
    }

    protected function create(): void
    {
    }

    protected function edit(): void
    {
    }

    protected function update(): void
    {
    }

    protected function confirmDelete(): void
    {
    }

    protected function delete(): void
    {
    }
}
