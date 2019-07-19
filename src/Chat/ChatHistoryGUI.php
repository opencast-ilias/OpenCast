<?php

namespace srag\Plugins\Opencast\Chat;

use ilObjUser;
use ilOpenCastPlugin;
use ilTemplate;
use ilTemplateException;
use srag\DIC\OpenCast\DICTrait;
use srag\DIC\OpenCast\Exception\DICException;

/**
 * Class ChatHistoryGUI
 * @package srag\Plugins\Opencast\Chat
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class ChatHistoryGUI {
	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	const PORT = 3000;

	/**
	 * @var integer
	 */
	private $chat_room_id;


	/**
	 * ChatHistoryGUI constructor.
	 *
	 * @param $chat_room_id
	 */
	public function __construct($chat_room_id) {
		$this->chat_room_id = $chat_room_id;
	}

	/**
	 * @param bool $async
	 * @return string
	 * @throws DICException
	 * @throws ilTemplateException
	 */
	public function render($async = false) {
		$template = new ilTemplate(self::plugin()->directory() . '/src/Chat/history.html', false, false);
		$users = [];
		foreach (MessageAR::where(['chat_room_id' => $this->chat_room_id])->orderBy('sent_at', 'ASC')->get() as $message) {
			$template->setCurrentBlock('message');
			/** @var $message MessageAR */
			$template->setVariable('USER_ID', $message->getUsrId());
			$template->setVariable('MESSAGE', $message->getMessage());
			$user = $users[$message->getUsrId()] ?: ($users[$message->getUsrId()] = new ilObjUser($message->getUsrId()));
			$template->setVariable('PUBLIC_NAME', $user->getPublicName());
			$template->setVariable('CLIENT_ID', CLIENT_ID);
			$template->parseCurrentBlock();
		}
		return $template->get();
	}
}