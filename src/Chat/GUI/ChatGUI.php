<?php

namespace srag\Plugins\Opencast\Chat\GUI;

use ilOpenCastPlugin;
use ilTemplate;
use ilTemplateException;
use srag\Plugins\Opencast\Chat\Model\ChatroomAR;
use srag\Plugins\Opencast\Chat\Model\ConfigAR;
use srag\Plugins\Opencast\Chat\Model\TokenAR;

/**
 * Class ChatGUI
 *
 * @package srag\Plugins\Opencast\Chat
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ChatGUI
{
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    /**
     * @var ChatroomAR
     */
    private $token;
    /**
     * @var ilTemplate
     */
    private $template;
    /**
     * @var ilOpenCastPlugin
     */
    private $plugin;

    /**
     * ChatGUI constructor.
     *
     *
     */
    public function __construct(TokenAR $token)
    {
        global $opencastContainer;
        $this->plugin = $opencastContainer[ilOpenCastPlugin::class];
        $this->token = $token;
    }

    /**
     * @param bool $async
     *
     * @return string
     * @throws DICException
     * @throws ilTemplateException
     */
    public function render($async = false)
    {
        $port = ConfigAR::getConfig(ConfigAR::C_PORT);
        $protocol = ConfigAR::getConfig(ConfigAR::C_PROTOCOL);
        $host = ConfigAR::getConfig(ConfigAR::C_HOST);

        $script_open_chat = ILIAS_HTTP_PATH . '/' . ltrim(__DIR__, ILIAS_ABSOLUTE_PATH) . '/open_chat.php';
        $url = $script_open_chat .
            '?port=' . $port .
            '&token=' . $this->token->getToken()->toString() .
            '&protocol=' . $protocol;
        if (is_string($host) && $host !== '0.0.0.0') {
            $url .= '&host=' . $host;
        }

        $template = new ilTemplate($this->plugin->getDirectory() . '/src/Chat/GUI/templates/iframe.html', true, true);
        $template->setVariable('URL', $url);
        $template->setVariable(
            'REFRESH_ICON',
            $this->plugin->getDirectory() . '/src/Chat/node/public/images/refresh_icon.png'
        );
        $chat_css_path = $this->plugin->getDirectory() . '/src/Chat/node/public/css/chat.css';
        if (!$async) {
            $this->template->addCss($chat_css_path);
        } else {
            $template->setCurrentBlock('css');
            $template->setVariable('CSS_PATH', $chat_css_path);
            $template->parseCurrentBlock();
        }
        return $template->get();
    }
}
