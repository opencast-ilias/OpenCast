<?php

namespace srag\Plugins\Opencast\Chat\GUI;

use ilOpenCastPlugin;
use ilTemplate;
use ilTemplateException;
use srag\DIC\OpenCast\DICTrait;
use srag\DIC\OpenCast\Exception\DICException;
use ilObjUser;

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

	const PORT = 3000;

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
	 * @throws DICException
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
		$url = ILIAS_HTTP_PATH . ':' . self::PORT . '/srchat/open_chat/' . $this->token->getToken()->toString();
		$template = new ilTemplate(self::plugin()->directory() . '/src/Chat/iframe.html', false, false);
		$template->setVariable('URL', $url);
//		$template->setVariable('TOKEN', $token->getToken()->toString());
		$template->addcss(self::plugin()->directory() . '/src/Chat/chat.css');
		return $template->get();
	}

}