<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Chat\GUI;

use ilOpenCastPlugin;
use ilTemplate;
use ilTemplateException;
use srag\Plugins\Opencast\Chat\Model\ChatroomAR;
use srag\Plugins\Opencast\Chat\Model\ConfigAR;
use srag\Plugins\Opencast\Chat\Model\TokenAR;
use srag\Plugins\Opencast\Container\Init;

/**
 * Class ChatGUI
 *
 * @package srag\Plugins\Opencast\Chat
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ChatGUI
{
    private TokenAR $token;
    private ?ilTemplate $template = null;
    /**
     * @var
     */
    private ilOpenCastPlugin $plugin;

    /**
     * ChatGUI constructor.
     */
    public function __construct(TokenAR $token)
    {
        $opencastContainer = Init::init();
        $this->plugin = $opencastContainer[ilOpenCastPlugin::class];
        $this->token = $token;
    }


    public function render(bool $async = false): string
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

        $template = new ilTemplate($this->plugin->getDirectory() . '/templates/default/Chat/iframe.html', true, true);
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
