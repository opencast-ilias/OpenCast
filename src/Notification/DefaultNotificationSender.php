<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 */

namespace srag\Plugins\Opencast\Notification;

use ILIAS\Mail\Service\MimeMailService;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class DefaultNotificationSender implements NotificationSender
{
    public function __construct(
        private MimeMailService $mail_service,
    ) {
    }

    public function sendMail(
        string $to,
        string $subject,
        string $body,
        ?int $from_user_id = null,
    ): bool {
        if ($from_user_id !== null) {
            $sender = $this->mail_service->senderFactory()->user($from_user_id);
        } else {
            $sender = $this->mail_service->senderFactory()->system();
        }

        $mailer = new \ilMimeMail();
        $mailer->From($sender);
        $mailer->To($to);
        $mailer->Subject($subject);
        $mailer->Body($body);

        return $mailer->Send();
    }
}
