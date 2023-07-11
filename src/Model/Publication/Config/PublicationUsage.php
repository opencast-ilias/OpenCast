<?php

namespace srag\Plugins\Opencast\Model\Publication\Config;

use ActiveRecord;

/**
 * Class PublicationUsage
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class PublicationUsage extends ActiveRecord
{
    public const TABLE_NAME = 'xoct_publication_usage';
    public const USAGE_ANNOTATE = 'annotate';
    public const USAGE_PLAYER = 'player';
    public const USAGE_THUMBNAIL = 'thumbnail';
    public const USAGE_THUMBNAIL_FALLBACK = 'thumbnail_fallback';
    public const USAGE_THUMBNAIL_FALLBACK_2 = 'thumbnail_fallback_2';
    public const USAGE_DOWNLOAD = 'download';
    public const USAGE_DOWNLOAD_FALLBACK = 'download_fallback';
    public const USAGE_CUTTING = 'cutting';
    public const USAGE_SEGMENTS = 'segments';
    public const USAGE_PREVIEW = 'preview';
    public const USAGE_DUAL_IMAGE_SOURCE = "dual-image-source";
    public const USAGE_LIVE_EVENT = 'live_event';
    public const USAGE_UNPROTECTED_LINK = 'unprotected_link';
    public const MD_TYPE_ATTACHMENT = 1;
    public const MD_TYPE_MEDIA = 2;
    public const MD_TYPE_PUBLICATION_ITSELF = 0;
    public const SEARCH_KEY_FLAVOR = 'flavor';
    public const SEARCH_KEY_TAG = 'tag';
    public const USAGE_CAPTIONS = 'captions';
    public const USAGE_CAPTIONS_FALLBACK = 'captions_fallback';
    /**
     * @var array
     */
    public static $usage_ids
        = [
            self::USAGE_ANNOTATE,
            self::USAGE_PLAYER,
            self::USAGE_THUMBNAIL,
            self::USAGE_THUMBNAIL_FALLBACK,
            self::USAGE_THUMBNAIL_FALLBACK_2,
            self::USAGE_DOWNLOAD,
            self::USAGE_DOWNLOAD_FALLBACK,
            self::USAGE_CUTTING,
            self::USAGE_SEGMENTS,
            self::USAGE_PREVIEW,
            self::USAGE_LIVE_EVENT,
            self::USAGE_UNPROTECTED_LINK,
            self::USAGE_CAPTIONS,
            self::USAGE_CAPTIONS_FALLBACK,
        ];


    /**
     * @return string
     * @deprecated
     */
    public static function returnDbTableName()
    {
        return self::TABLE_NAME;
    }


    /**
     * @return string
     */
    public function getConnectorContainerName()
    {
        return self::TABLE_NAME;
    }


    /**
     * @var string
     *
     * @con_is_primary true
     * @con_is_unique  true
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     64
     */
    protected $usage_id = '';
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     512
     */
    protected $title;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     4000
     */
    protected $description;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     512
     */
    protected $channel;
    /**
     * @var bool
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     1
     */
    protected $status;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     512
     */
    protected $search_key;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     512
     */
    protected $flavor;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     512
     */
    protected $tag;
    /**
     * @var int
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     1
     */
    protected $md_type = null;
    /**
     * @var bool
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     1
     */
    protected $allow_multiple = false;


    /**
     * @return string
     */
    public function getUsageId(): string
    {
        return $this->usage_id ?? '';
    }


    /**
     * @param string $usage_id
     */
    public function setUsageId($usage_id)
    {
        $this->usage_id = $usage_id;
    }


    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title ?? '';
    }


    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }


    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }


    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }


    /**
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel ?? '';
    }


    /**
     * @param string $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }


    /**
     * @return boolean
     */
    public function isStatus()
    {
        return $this->status;
    }


    /**
     * @param boolean $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function isAllowMultiple(): bool
    {
        return (bool) $this->allow_multiple;
    }

    /**
     * @param bool $allow_multiple
     */
    public function setAllowMultiple(bool $allow_multiple)
    {
        $this->allow_multiple = $allow_multiple;
    }

    /**
     * @return string
     */
    public function getFlavor(): string
    {
        return $this->flavor ?? '';
    }


    /**
     * @param string $flavor
     */
    public function setFlavor(string $flavor)
    {
        $this->flavor = $flavor;
    }


    /**
     * @return string
     */
    public function getSearchKey(): string
    {
        return $this->search_key ?? '';
    }


    /**
     * @param string $search_key
     */
    public function setSearchKey(string $search_key)
    {
        $this->search_key = $search_key;
    }


    /**
     * @return string
     */
    public function getTag(): string
    {
        return $this->tag ?? '';
    }


    /**
     * @param string $tag
     */
    public function setTag(string $tag)
    {
        $this->tag = $tag;
    }


    /**
     * @return int
     */
    public function getMdType(): int
    {
        return (int) $this->md_type;
    }


    /**
     * @param int $md_type
     */
    public function setMdType($md_type)
    {
        $this->md_type = $md_type;
    }
}
