<?php

namespace srag\Plugins\Opencast\Model\Publication;

use srag\Plugins\Opencast\Model\API\APIObject;
use stdClass;

/**
 * Class publication
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class Publication extends APIObject
{

    /**
     * @param string $id
     */
    public function __construct($id = '')
    {
        $this->setId($id);
        if ($id) {
            $this->read();
        }
    }


    public function read()
    {
    }


    /**
     * @param \stdClass $class
     * @throws \xoctException
     */
    public function loadFromStdClass(stdClass $class)
    {
        parent::loadFromStdClass($class);
    }


    /**
     * @param $array
     * @throws \xoctException
     */
    public function loadFromArray(array $array)
    {
        parent::loadFromArray($array);
        $attachments = array();
        foreach ($this->getAttachments() as $attachment) {
            $xoctAttachment = new Attachment();
            $xoctAttachment->loadFromStdClass($attachment);
            $attachments[] = $xoctAttachment;
        }
        $this->setAttachments($attachments);

        $medias = array();
        foreach ($this->getMedia() as $media) {
            $xoctMedia = new Media();
            $xoctMedia->loadFromStdClass($media);
            $medias[] = $xoctMedia;
        }
        $this->setMedia($medias);
    }


    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $channel;
    /**
     * @var string
     */
    protected $mediatype;
    /**
     * @var string
     */
    protected $url;
    /**
     * @var Media[]
     */
    protected $media;
    /**
     * @var Attachment[]
     */
    protected $attachments;


    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }


    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }


    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }


    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }


    /**
     * @param string $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }


    /**
     * @return string
     */
    public function getMediatype()
    {
        return $this->mediatype;
    }


    /**
     * @param string $mediatype
     */
    public function setMediatype($mediatype)
    {
        $this->mediatype = $mediatype;
    }


    /**
     * @return Media[]
     */
    public function getMedia()
    {
        return $this->media;
    }


    /**
     * @param Media[] $media
     */
    public function setMedia($media)
    {
        $this->media = $media;
    }


    /**
     * @return Attachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }


    /**
     * @param Attachment[] $attachments
     */
    public function setAttachments($attachments)
    {
        $this->attachments = $attachments;
    }
}