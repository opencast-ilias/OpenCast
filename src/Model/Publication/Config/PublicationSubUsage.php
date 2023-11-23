<?php

namespace srag\Plugins\Opencast\Model\Publication\Config;

use ActiveRecord;
use xoctException;

/**
 * Class PublicationSubUsage
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class PublicationSubUsage extends ActiveRecord
{
    public const TABLE_NAME = 'xoct_pub_sub_usage';
    public const DISPLAY_NAME_LANG_MODULE = 'pus_display_name';

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
     * @var int
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     4
     * @con_is_notnull true
     * @con_is_primary true
     * @con_is_unique  true
     * @con_sequence   true
     */
    protected $id;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     64
     * @con_is_notnull true
     */
    protected $parent_usage_id = '';
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
     * @return int
     */
    public function getId(): int
    {
        return intval($this->id);
    }


    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }


    /**
     * @return string
     */
    public function getParentUsageId(): string
    {
        return $this->parent_usage_id;
    }


    /**
     * @param string $parent_usage_id
     */
    public function setParentUsageId($parent_usage_id)
    {
        $this->parent_usage_id = $parent_usage_id;
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
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return int|null
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
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }


    /**
     * @return boolean
     */
    public function isStatus(): bool
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
     * Create the object, but we check if it is allowed!
     * @throws xoctException
     */
    public function create()
    {
        if (!in_array($this->getParentUsageId(), PublicationUsage::$sub_allowed_usage_ids)) {
            throw new xoctException('Unable to have sub-usage for publication usage: ' . $this->getParentUsageId());
        }
        parent::create();
    }

    /**
     * Updates the object, but we check if it is allowed!
     * @throws xoctException
     */
    public function update()
    {
        if (!in_array($this->getParentUsageId(), PublicationUsage::$sub_allowed_usage_ids)) {
            throw new xoctException('Unable to have sub-usage for publication usage: ' . $this->getParentUsageId());
        }
        parent::update();
    }
}
