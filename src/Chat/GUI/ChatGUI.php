<?php

namespace srag\Plugins\Opencast\Chat\GUI;

use ilOpenCastPlugin;
use ilTemplate;
use ilTemplateException;
use srag\DIC\OpenCast\DICTrait;
use srag\DIC\OpenCast\Exception\DICException;
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
class ChatGUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	/**
	 * @var ChatroomAR
	 */
	private $token;
	/**
	 * @var ilTemplate
	 */
	private $template;


    /**
     * ChatGUI constructor.
     *
     * @param TokenAR $token
     *
     */
	public function __construct(TokenAR $token) {
		$this->token = $token;
	}


	/**
	 * @param bool $async
	 *
	 * @return string
	 * @throws DICException
	 * @throws ilTemplateException
	 */
	public function render($async = false) {
        $ip = ConfigAR::getConfig(ConfigAR::C_IP);
        $port = ConfigAR::getConfig(ConfigAR::C_PORT);
        $protocol = ConfigAR::getConfig(ConfigAR::C_PROTOCOL);

        $script_open_chat = ILIAS_HTTP_PATH . '/' . ltrim(__DIR__, ILIAS_ABSOLUTE_PATH) . '/open_chat.php';
        $url = $script_open_chat .
            '?port=' . $port .
            '&token=' . $this->token->getToken()->toString() .
            '&protocol=' . $protocol;
        if (is_string($ip) && $ip !== '0.0.0.0') {
            $url .= '&ip=' . $ip;
        }
		// TODO: get rid of self::plugin() to be independent
		$template = new ilTemplate(self::plugin()->directory() . '/src/Chat/GUI/templates/iframe.html', true, true);
		$template->setVariable('URL', $url);
        $template->setVariable('REFRESH_ICON', self::plugin()->directory() . '/src/Chat/node/public/images/refresh_icon.png');
        $chat_css_path = self::plugin()->directory() . '/src/Chat/node/public/css/chat.css';
        if (!$async) {
            self::dic()->mainTemplate()->addCss($chat_css_path);
        } else {
            $template->setCurrentBlock('css');
            $template->setVariable('CSS_PATH', $chat_css_path);
            $template->parseCurrentBlock();
        }
		return $template->get();
	}

}