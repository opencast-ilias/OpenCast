<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Chat\GUI;

use ilObjUser;
use ilOpenCastPlugin;
use ilTemplate;
use srag\Plugins\Opencast\Chat\Model\MessageAR;
use srag\Plugins\Opencast\Container\Init;

/**
 * Class ChatHistoryGUI
 * @package srag\Plugins\Opencast\Chat
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ChatHistoryGUI
{
    private ?int $chat_room_id = null;
    private \ilGlobalTemplateInterface $main_tpl;

    private ilOpenCastPlugin $plugin;

    /**
     * ChatHistoryGUI constructor.
     *
     * @param $chat_room_id
     */
    public function __construct(?int $chat_room_id)
    {
        $opencastContainer = Init::init();
        $this->plugin = $opencastContainer[ilOpenCastPlugin::class];
        $this->main_tpl = $opencastContainer->ilias()->ui()->mainTemplate();
        $this->chat_room_id = $chat_room_id;
    }


    public function render(bool $async = false): string
    {
        $template = new ilTemplate($this->plugin->getDirectory() . '/templates/default/Chat/history.html', true, true);
        $users = [];
        foreach (
            MessageAR::where(['chat_room_id' => $this->chat_room_id])->orderBy('sent_at', 'ASC')->get() as $message
        ) {
            $template->setCurrentBlock('message');
            /** @var $message MessageAR */
            $template->setVariable('USER_ID', $message->getUsrId());
            $template->setVariable('MESSAGE', $message->getMessage());
            $user = $users[$message->getUsrId()] ?: ($users[$message->getUsrId()] = new ilObjUser(
                $message->getUsrId()
            ));
            $template->setVariable('PUBLIC_NAME', $user->hasPublicProfile() ? $user->getFullname() : $user->getLogin());
            $template->setVariable('SENT_AT', date('H:i', strtotime($message->getSentAt())));
            $profile_picture_path = './data/' . CLIENT_ID . '/usr_images/usr_' . $message->getUsrId() . '_xsmall.jpg';
            $picture_path = is_file(
                $profile_picture_path
            ) ? $profile_picture_path : './templates/default/images/no_photo_xsmall.jpg';
            $template->setVariable('PROFILE_PICTURE_PATH', $picture_path);
            $template->parseCurrentBlock();
        }

        $chat_css_path = $this->plugin->getDirectory() . '/src/Chat/node/public/css/chat.css';
        if (!$async) {
            $this->main_tpl->addCss($chat_css_path);
        } else {
            $template->setCurrentBlock('css');
            $template->setVariable('CSS_PATH', $chat_css_path);
            $template->parseCurrentBlock();
        }

        return $template->get();
    }
}
