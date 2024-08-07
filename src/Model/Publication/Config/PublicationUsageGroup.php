<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Publication\Config;

use ActiveRecord;

/**
 * Class PublicationUsageGroup
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class PublicationUsageGroup extends ActiveRecord
{
    public const TABLE_NAME = 'xoct_publication_group';
    public const DISPLAY_NAME_LANG_MODULE = 'pug_display_name';
    public const SORT_BY = 'name';

    /**
     * @return string
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
     * @con_length     512
     * @con_is_notnull true
     */
    protected $name;
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
     * @return int
     */
    public function getId(): int
    {
        return (int)$this->id;
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
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
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

    public function delete()
    {
        /**
         * @var $pu PublicationUsage
         */
        foreach (PublicationUsage::where(['group_id' => intval($this->getId())])->get() as $pu) {
            $pu->setGroupId(null);
            $pu->update();
        }
        foreach (PublicationSubUsage::where(['group_id' => intval($this->getId())])->get() as $psu) {
            $psu->setGroupId(null);
            $psu->update();
        }
        parent::delete();
    }
}
