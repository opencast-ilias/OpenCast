<?php

namespace srag\Plugins\Opencast\Chat;

use ilOpenCastPlugin;
use ilTemplate;
use srag\DIC\OpenCast\DICTrait;
use srag\DIC\OpenCast\Exception\DICException;

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
		$this->template = new ilTemplate(self::plugin()->directory() . '/src/Chat/iframe.html', false, false);
//		$this->template = new ilTemplate(self::plugin()->directory() . '/src/Chat/index.html', false, false);
	}


	/**
	 * @param bool $async
	 *
	 * @return string
	 * @throws \ilTemplateException
	 */
	public function render($async = false) {
		$url = ILIAS_HTTP_PATH . ':' . self::PORT . '/srchat/' . $this->token->getToken()->toString();
		$this->template->setVariable('URL', $url);
//		$this->template->setVariable('TOKEN', $this->token->getToken()->toString());
		$this->template->addInlineCss(self::plugin()->directory() . '/src/Chat/chat.css');
		return $this->template->get();
	}
}