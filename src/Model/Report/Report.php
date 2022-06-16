<?php

namespace srag\Plugins\Opencast\Model\Report;

use ActiveRecord;
use ilException;
use ilMail;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use xoct;
use const ANONYMOUS_USER_ID;

/**
 * Class xoctReport
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class Report extends ActiveRecord
{
    public const DB_TABLE = 'xoct_report';

    // Types
    public const TYPE_DATE = 1;
    public const TYPE_QUALITY = 2;

    /**
     * @return string
     */
    public function getConnectorContainerName()
    {
        return self::DB_TABLE;
    }

    /**
     * @param bool $omit_send_mail
     * @throws ilException
     */
    public function create($omit_send_mail = false)
    {
        $this->setCreatedAt(date('Y-m-d H:i:s', time()));
        parent::create();

        if (!$omit_send_mail) {
            $mail = new ilMail($this->user_id);
            $type = ['system'];

            $mail->setSaveInSentbox(false);
            $mail->appendInstallationSignature(true);
            $mail->sendMail(
                $this->getRecipientForType($this->getType()),
                '',
                '',
                $this->getSubject(),
                $this->getMessage(),
                [],
                xoct::isIlias6() ? false : $type
            );
        }
    }

    /**
     * @param $type
     * @return mixed
     * @throws ilException
     */
    protected function getRecipientForType($type)
    {
        switch ($type) {
            case self::TYPE_DATE:
                return PluginConfig::getConfig(PluginConfig::F_REPORT_DATE_EMAIL);
            case self::TYPE_QUALITY:
                return PluginConfig::getConfig(PluginConfig::F_REPORT_QUALITY_EMAIL);
            default:
                throw new ilException('Missing Report Type for xoctReport');
        }
    }

    /**
     * @var int
     *
     * @con_has_field    true
     * @con_fieldtype    integer
     * @con_length       8
     * @con_is_primary   true
     * @con_sequence     true
     */
    protected $id;
    /**
     * @var int
     *
     * @con_has_field    true
     * @con_fieldtype    integer
     * @con_length       8
     * @con_is_notnull   true
     */
    protected $user_id;
    /**
     * @var int
     *
     * @con_has_field    true
     * @con_fieldtype    timestamp
     * @con_is_notnull   true
     */
    protected $created_at = 0;
    /**
     * @var int
     *
     * @con_has_field    true
     * @con_fieldtype    integer
     * @con_length       8
     * @con_is_notnull   true
     */
    protected $type = 0;
    /**
     * @var int
     *
     * @con_has_field    true
     * @con_fieldtype    integer
     * @con_length       8
     * @con_is_notnull   true
     */
    protected $ref_id = 0;
    /**
     * @var string
     *
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_length       256
     */
    protected $event_id = "";
    /**
     * @var string
     *
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_length       256
     * @con_is_notnull   true
     */
    protected $subject = "";
    /**
     * @var string
     *
     * @con_has_field    true
     * @con_fieldtype    clob
     * @con_is_notnull   true
     */
    protected $message = "";


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return static
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     * @return static
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param int $created_at
     * @return static
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return static
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getRefId()
    {
        return $this->ref_id;
    }

    /**
     * @param int $ref_id
     * @return static
     */
    public function setRefId($ref_id)
    {
        $this->ref_id = $ref_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getEventId()
    {
        return $this->event_id;
    }

    /**
     * @param string $event_id
     * @return static
     */
    public function setEventId($event_id)
    {
        $this->event_id = $event_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     * @return static
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return static
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }
}
