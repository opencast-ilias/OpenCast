<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Object;

use ActiveRecord;
use ilException;
use ilObjOpenCast;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\UserSettings\UserSettingsRepository;
use xoctException;

/**
 * Class ObjectSettings
 */
class ObjectSettings extends ActiveRecord
{
    public $paella_player_option;
    public $paella_player_file_id;
    public $paella_player_url;
    public $paella_player_live_option;
    public $paella_player_live_file_id;
    public $paella_player_live_url;

    public const TABLE_NAME = 'xoct_data';

    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }

    public static function lookupObjId(string $series_identifier): ?int
    {
        $objectSettings = ObjectSettings::where(['series_identifier' => $series_identifier])->last();
        if ($objectSettings instanceof self) {
            return $objectSettings->getObjId();
        }

        return null;
    }

    public static function lookupSeriesIdentifier(int $obj_id): ?string
    {
        $objetSettings = ObjectSettings::where(['obj_id' => $obj_id])->last();
        if ($objetSettings instanceof self) {
            return $objetSettings->getSeriesIdentifier();
        }

        return null;
    }

    public function create(): void
    {
        if ($this->getObjId() === 0) {
            $this->update();
        } else {
            parent::create();
        }
    }

    /**
     * @return Int[]
     * @throws ilException
     */
    public function getDuplicatesOnSystem(): array
    {
        global $DIC; // we cannot add DIC to the constructor because of the ActiveRecord
        if (!$this->getObjId() || !$this->getSeriesIdentifier()) {
            return [];
        }

        $duplicates_ar = self::where(['series_identifier' => $this->getSeriesIdentifier()])->where(
            ['obj_id' => 0],
            '!='
        );
        if ($duplicates_ar->count() < 2) {
            return [];
        }

        $duplicates_ids = [];
        // check if duplicates are actually deleted
        foreach ($duplicates_ar->get() as $oc) {
            /** @var ObjectSettings $oc */
            if ($oc->getObjId() != $this->getObjId()) {
                $query = "SELECT ref_id FROM object_reference" . " WHERE deleted is null and obj_id = " . $DIC->database(
                )->quote($oc->getObjId(), "integer");
                $set = $DIC->database()->query($query);
                $rec = $DIC->database()->fetchAssoc($set);

                if ($rec['ref_id'] ?? false) {
                    $duplicates_ids[] = (int) $rec['ref_id'];
                }
            }
        }

        if ($duplicates_ids !== []) {
            return $duplicates_ids;
        }

        return [];
    }

    /**
     * @return mixed|string
     */
    public function getVideoPortalLink(): string
    {
        if ($link_template = PluginConfig::getConfig(PluginConfig::F_VIDEO_PORTAL_LINK)) {
            $link = str_replace('{series_id}', $this->getSeriesIdentifier(), $link_template);
            return '<a target="_blank" href="' . $link . '">' . $link . '</a>';
        }
        return '';
    }

    public function getILIASObject(): ilObjOpenCast
    {
        static $object;
        if (is_null($object[$this->getObjId()] ?? null)) {
            $references = ilObjOpenCast::_getAllReferences($this->getObjId());
            $object[$this->getObjId()] = new ilObjOpenCast(array_shift($references));
        }
        return $object[$this->getObjId()];
    }

    /**
     * @var
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     8
     * @con_is_notnull true
     * @con_is_primary true
     * @con_is_unique  true
     */
    protected $obj_id;
    /**
     * @var string
     *
     * @con_has_field true
     * @con_fieldtype text
     * @con_length    256
     */
    protected $series_identifier;
    /**
     * @var string
     *
     * @con_has_field true
     * @con_fieldtype text
     * @con_length    4000
     */
    protected $intro_text;
    /**
     * @var
     *
     * @con_has_field true
     * @con_fieldtype integer
     * @con_length    1
     */
    protected $use_annotations = false;
    /**
     * @var
     *
     * @con_has_field true
     * @con_fieldtype integer
     * @con_length    1
     */
    protected $permission_per_clip = false;
    /**
     * @var
     *
     * @con_has_field true
     * @con_fieldtype integer
     * @con_length    1
     */
    protected $permission_allow_set_own = false;
    /**
     * @var
     *
     * @con_has_field true
     * @con_fieldtype integer
     * @con_length    1
     */
    protected $agreement_accepted = false;
    /**
     * @var bool
     *
     * @con_has_field true
     * @con_fieldtype integer
     * @con_length    1
     */
    protected $obj_online = false;
    /**
     * @var integer
     *
     * @con_has_field true
     * @con_fieldtype integer
     * @con_length    8
     */
    protected $default_view = UserSettingsRepository::VIEW_TYPE_LIST;
    /**
     * @var bool
     *
     * @con_has_field true
     * @con_fieldtype integer
     * @con_length    1
     */
    protected $view_changeable = true;
    /**
     * @var bool
     *
     * @con_has_field true
     * @con_fieldtype integer
     * @con_length    1
     */
    protected $chat_active = false;

    public function getObjId(): int
    {
        return (int) $this->obj_id;
    }

    public function setObjId(int $obj_id): void
    {
        $this->obj_id = $obj_id;
    }

    public function getSeriesIdentifier(): ?string
    {
        return $this->series_identifier;
    }

    public function setSeriesIdentifier(string $series_identifier): void
    {
        $this->series_identifier = $series_identifier;
    }

    public function getUseAnnotations(): bool
    {
        return (bool) $this->use_annotations;
    }

    public function setUseAnnotations(bool $use_annotations): void
    {
        $this->use_annotations = $use_annotations;
    }

    public function getPermissionPerClip(): bool
    {
        return (bool) $this->permission_per_clip;
    }

    public function setPermissionPerClip(bool $permission_per_clip): void
    {
        $this->permission_per_clip = $permission_per_clip;
    }

    public function getAgreementAccepted(): bool
    {
        return (bool) $this->agreement_accepted;
    }

    public function setAgreementAccepted(bool $agreement_accepted): void
    {
        $this->agreement_accepted = $agreement_accepted;
    }

    public function isOnline(): bool
    {
        return (bool) $this->obj_online;
    }

    public function setOnline(bool $obj_online): void
    {
        $this->obj_online = $obj_online;
    }

    public function getIntroductionText(): string
    {
        return $this->intro_text ?? '';
    }

    public function setIntroductionText(string $intro_text): void
    {
        $this->intro_text = $intro_text;
    }

    public function getPermissionAllowSetOwn(): bool
    {
        return $this->permission_allow_set_own && $this->getPermissionPerClip();
    }

    public function setPermissionAllowSetOwn(bool $permission_allow_set_own): void
    {
        $this->permission_allow_set_own = $permission_allow_set_own;
    }

    public function getDefaultView(): int
    {
        return (int) $this->default_view;
    }

    public function setDefaultView(int $default_view): void
    {
        $this->default_view = $default_view;
    }

    public function isViewChangeable(): bool
    {
        return (bool) $this->view_changeable;
    }

    public function setViewChangeable(bool $view_changeable): void
    {
        $this->view_changeable = $view_changeable;
    }

    public function setChatActive(bool $chat_active): void
    {
        $this->chat_active = $chat_active;
    }

    public function isChatActive(): bool
    {
        return (bool) $this->chat_active;
    }

    public function getIntroText(): string
    {
        return $this->intro_text;
    }

    public function setIntroText(string $intro_text): void
    {
        $this->intro_text = $intro_text;
    }

    public function isObjOnline(): bool
    {
        return $this->obj_online;
    }

    public function setObjOnline(bool $obj_online): void
    {
        $this->obj_online = $obj_online;
    }

    public function getPaellaPlayerOption(): string
    {
        return $this->paella_player_option;
    }

    public function setPaellaPlayerOption(string $paella_player_option): void
    {
        $this->paella_player_option = $paella_player_option;
    }

    public function getPaellaPlayerFileId(): string
    {
        return $this->paella_player_file_id ?? '';
    }

    public function setPaellaPlayerFileId(string $paella_player_file_id): void
    {
        $this->paella_player_file_id = $paella_player_file_id;
    }

    public function getPaellaPlayerUrl(): string
    {
        return $this->paella_player_url ?? '';
    }

    public function setPaellaPlayerUrl(string $paella_player_url): void
    {
        $this->paella_player_url = $paella_player_url;
    }

    public function getPaellaPlayerLiveOption(): string
    {
        return $this->paella_player_live_option;
    }

    public function setPaellaPlayerLiveOption(string $paella_player_live_option): void
    {
        $this->paella_player_live_option = $paella_player_live_option;
    }

    public function getPaellaPlayerLiveFileId(): string
    {
        return $this->paella_player_live_file_id ?? '';
    }

    public function setPaellaPlayerLiveFileId(string $paella_player_live_file_id): void
    {
        $this->paella_player_live_file_id = $paella_player_live_file_id;
    }

    public function getPaellaPlayerLiveUrl(): string
    {
        return $this->paella_player_live_url ?? '';
    }

    public function setPaellaPlayerLiveUrl(string $paella_player_live_url): void
    {
        $this->paella_player_live_url = $paella_player_live_url;
    }

    /**
     * @throws xoctException|ilException
     */
    public function updateAllDuplicates(Metadata $metadata): void
    {
        $title = $metadata->getField(MDFieldDefinition::F_TITLE)->getValue();
        $description = $metadata->getField(MDFieldDefinition::F_DESCRIPTION)->getValue();
        foreach ($this->getDuplicatesOnSystem() as $ref_id) {
            $object = new ilObjOpencast($ref_id);
            $object->setTitle((string) $title);
            $object->setDescription((string) $description);
            $object->update();
        }
    }
}
