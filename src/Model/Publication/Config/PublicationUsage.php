<?php

declare(strict_types=1);

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
    public const DISPLAY_NAME_LANG_MODULE = 'pu_display_name';
    public const USAGE_TYPE_ORG = 'org';
    public const USAGE_TYPE_SUB = 'sub';
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
     * INFO: the capability can be used for other usages, but it needs to be implemented when needed.
     * @var array
     */
    public static $sub_allowed_usage_ids
        = [
            self::USAGE_DOWNLOAD
        ];

    /**
     * @return string
     * @deprecated
     */
    public static function returnDbTableName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @return string
     */
    public function getConnectorContainerName(): string
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
     * @con_length     512
     */
    protected $display_name;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     4000
     */
    protected $description;
    /**
     * @var int
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     8
     */
    protected $group_id;
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
    protected $md_type;
    /**
     * @var bool
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     1
     */
    protected $allow_multiple = false;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     512
     */
    protected $mediatype;
    /**
     * @var bool
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     1
     */
    protected $ignore_object_setting = false;
    /**
     * @var bool
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     1
     */
    protected $ext_dl_source = false;

    /**
     * An indicator flag to determine if the usage is a sub-usage or not.
     * @var bool
     */
    protected $is_sub = false;

    /**
     * A variable that works as an id holder, for when the usage is a sub-usage.
     */
    protected $sub_id = 0;

    public function getUsageId(): string
    {
        return (string) $this->usage_id;
    }


    public function setUsageId(string $usage_id): void
    {
        $this->usage_id = $usage_id;
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    /**
     * @param string $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->display_name ?? '';
    }

    /**
     * @param string $description
     */
    public function setDisplayName($display_name)
    {
        $this->display_name = $display_name;
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
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return ?int
     */
    public function getGroupId(): ?int
    {
        return (!is_null($this->group_id) ? intval($this->group_id) : null);
    }

    /**
     * @param $group_id
     */
    public function setGroupId($group_id)
    {
        $this->group_id = ($group_id == '' || is_null($group_id) ? null : intval($group_id));
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
    public function setChannel($channel): void
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
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    public function isAllowMultiple(): bool
    {
        return (bool) $this->allow_multiple;
    }

    public function setAllowMultiple(bool $allow_multiple): void
    {
        $this->allow_multiple = $allow_multiple;
    }

    public function getFlavor(): string
    {
        return $this->flavor ?? '';
    }

    public function setFlavor(string $flavor): void
    {
        $this->flavor = $flavor;
    }

    public function getSearchKey(): string
    {
        return $this->search_key ?? '';
    }

    public function setSearchKey(string $search_key): void
    {
        $this->search_key = $search_key;
    }

    public function getTag(): string
    {
        return $this->tag ?? '';
    }

    public function setTag(string $tag): void
    {
        $this->tag = $tag;
    }

    public function getMdType(): int
    {
        return (int) $this->md_type;
    }

    /**
     * @param int $md_type
     */
    public function setMdType($md_type): void
    {
        $this->md_type = $md_type;
    }

    /**
     * @return string
     */
    public function getMediaType(): string
    {
        return $this->mediatype ?? '';
    }

    /**
     * @return array
     */
    public function getArrayMediaTypes(): array
    {
        $mediatype = $this->getMediaType();
        $mediatypes = $mediatype ? explode(',', $mediatype) : [];
        $mediatypes = array_map('trim', $mediatypes);
        return $mediatypes;
    }

    /**
     * @param string $mediatype
     */
    public function setMediaType(string $mediatype)
    {
        $this->mediatype = $mediatype;
    }

    /**
     * @return bool
     */
    public function ignoreObjectSettings(): bool
    {
        return (bool) $this->ignore_object_setting;
    }

    /**
     * @param bool $ignore_object_setting
     */
    public function setIgnoreObjectSettings(bool $ignore_object_setting)
    {
        $this->ignore_object_setting = $ignore_object_setting;
    }

    /**
     * @return bool
     */
    public function isExternalDownloadSource(): bool
    {
        return (bool) $this->ext_dl_source;
    }

    /**
     * @param bool $ext_dl_source
     */
    public function setExternalDownloadSource(bool $ext_dl_source)
    {
        $this->ext_dl_source = $ext_dl_source;
    }

    /**
     * @return bool
     */
    public function isSub(): bool
    {
        return (bool) $this->is_sub;
    }

    /**
     * @param bool $is_sub
     */
    public function setAsSub(bool $is_sub)
    {
        $this->is_sub = $is_sub;
    }

    /**
     * @return int
     */
    public function getSubId(): int
    {
        return (int) $this->sub_id;
    }

    /**
     * @param int $sub_id
     */
    public function setSubId(int $sub_id): void
    {
        $this->sub_id = $sub_id;
    }
}
