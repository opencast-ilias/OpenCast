<?php

use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Chat\GUI\ChatGUI;
use srag\Plugins\Opencast\Chat\GUI\ChatHistoryGUI;
use srag\Plugins\Opencast\Chat\Model\ChatroomAR;
use srag\Plugins\Opencast\Chat\Model\MessageAR;
use srag\Plugins\Opencast\Chat\Model\TokenAR;
use srag\Plugins\Opencast\Model\Event\EventAPIRepository;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;
use srag\Plugins\Opencast\Util\Player\PlayerDataBuilderFactory;

/**
 * Class xoctPlayerGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctPlayerGUI extends xoctGUI
{
    const CMD_STREAM_VIDEO = 'streamVideo';

    const IDENTIFIER = 'eid';

    const ROLE_MASTER = "presenter";
    const ROLE_SLAVE = "presentation";
    /**
     * @var xoctOpenCast
     */
    protected $xoctOpenCast;
    /**
     * @var PublicationUsageRepository
     */
    protected $publication_usage_repository;
    /**
     * @var EventAPIRepository
     */
    private $event_repository;


    /**
     * @param EventAPIRepository $event_repository
     * @param xoctOpenCast|null $xoctOpenCast $xoctOpenCast
     */
    public function __construct(EventAPIRepository $event_repository, xoctOpenCast $xoctOpenCast = NULL) {
        $this->publication_usage_repository = new PublicationUsageRepository();
        $this->xoctOpenCast = $xoctOpenCast instanceof xoctOpenCast ? $xoctOpenCast : new xoctOpenCast();
        $this->event_repository = $event_repository;
    }

    /**
     * @throws DICException
     * @throws arException
     * @throws ilTemplateException
     */
    public function streamVideo() {
        $event = $this->event_repository->find(filter_input(INPUT_GET, self::IDENTIFIER));
        if (!xoctConf::getConfig(xoctConf::F_INTERNAL_VIDEO_PLAYER) && !$event->isLiveEvent()) {
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

        $tpl = self::plugin()->getPluginObject()->getTemplate("paella_player.html", true, true);
        $tpl->setVariable("TITLE", $event->getTitle());
        $tpl->setVariable("PAELLA_PLAYER_FOLDER", self::plugin()->getPluginObject()->getDirectory()
            . "/node_modules/paellaplayer/build/player");
        $tpl->setVariable("DATA", json_encode($data));
        $tpl->setVariable("JS_CONFIG", json_encode($this->buildJSConfig($event)));

        if ($event->isLiveEvent()) {
            $tpl->setVariable('LIVE_WAITING_TEXT', self::plugin()->translate('live_waiting_text', 'event',
                [date('H:i', $event->getScheduling()->getStart()->getTimestamp())]));
            $tpl->setVariable('LIVE_INTERRUPTED_TEXT', self::plugin()->translate('live_interrupted_text', 'event'));
            $tpl->setVariable('LIVE_OVER_TEXT', self::plugin()->translate('live_over_text', 'event'));
        }

        if ($this->isChatVisible()) {
            $this->initChat($event, $tpl);
        } else {
            $tpl->setVariable("STYLE_SHEET_LOCATION", ILIAS_HTTP_PATH . '/' . self::plugin()->getPluginObject()->getDirectory() . "/templates/default/player.css");
        }

        setcookie('lastProfile', null, -1);
        echo $tpl->get();
        exit();
    }

    protected function buildJSConfig(xoctEvent $event) : stdClass
    {
        $js_config = new stdClass();
        $js_config->paella_config_file = self::plugin()->getPluginObject()->getDirectory() . "/js/paella_player/config"
            . ($event->isLiveEvent() ? "_live" : "") . ".json";
        $js_config->paella_player_folder = self::plugin()->getPluginObject()->getDirectory() . "/node_modules/paellaplayer/build/player";

        if ($event->isLiveEvent()) {
            $js_config->check_script_hls = self::plugin()->directory() . '/src/Util/check_hls_status.php'; // script to check live stream availability
            $js_config->is_live_stream = true;
            $js_config->event_start = $event->getScheduling()->getStart()->getTimestamp();
            $js_config->event_end = $event->getScheduling()->getEnd()->getTimestamp();
        }
        return $js_config;
    }

    /**
     * @return bool
     */
    protected function isChatVisible() : bool
    {
        return !filter_input(INPUT_GET, 'force_no_chat')
            && xoctConf::getConfig(xoctConf::F_ENABLE_CHAT)
            && $this->xoctOpenCast->isChatActive();
    }

    /**
     * @param xoctEvent  $event
     * @param ilTemplate $tpl
     * @throws DICException
     * @throws arException
     * @throws ilTemplateException
     */
    protected function initChat(xoctEvent $event, ilTemplate $tpl)
    {
        $ChatroomAR = ChatroomAR::findBy($event->getIdentifier(), $this->xoctOpenCast->getObjId());
        if ($event->isLiveEvent()) {
            $tpl->setVariable("STYLE_SHEET_LOCATION",
                ILIAS_HTTP_PATH . '/' . self::plugin()->getPluginObject()->getDirectory() . "/templates/default/player_w_chat.css");
            $ChatroomAR = ChatroomAR::findOrCreate($event->getIdentifier(), $this->xoctOpenCast->getObjId());
            $public_name = self::dic()->user()->hasPublicProfile() ?
                self::dic()->user()->getFirstname() . " " . self::dic()->user()->getLastname()
                : self::dic()->user()->getLogin();
            $TokenAR = TokenAR::getNewFrom($ChatroomAR->getId(), self::dic()->user()->getId(), $public_name);
            $ChatGUI = new ChatGUI($TokenAR);
            $tpl->setVariable('CHAT', $ChatGUI->render(true));
        } elseif ($ChatroomAR && MessageAR::where(["chat_room_id" => $ChatroomAR->getId()])->hasSets()) {
            // show chat history for past live events
            $tpl->setVariable("STYLE_SHEET_LOCATION",
                ILIAS_HTTP_PATH . '/' . self::plugin()->getPluginObject()->getDirectory() . "/templates/default/player_w_chat.css");
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
    public function txt($key) {
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
