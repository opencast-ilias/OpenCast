<?php

namespace srag\Plugins\Opencast\Model\Object;

use ActiveRecord;
use ilException;
use ilObjOpenCast;
use ilOpenCastPlugin;
use srag\DIC\OpenCast\DICTrait;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGroup;
use srag\Plugins\Opencast\Model\UserSettings\UserSettingsRepository;
use xoctException;

/**
 * Class ObjectSettings
 */
class ObjectSettings extends ActiveRecord {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	const TABLE_NAME = 'xoct_data';

	const PAELLA_OPTION_DEFAULT = 'default';
	const PAELLA_OPTION_FILE = 'file';
	const PAELLA_OPTION_URL = 'url';
	const DEFAULT_PATH = 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/js/paella_player/config.json';
	const DEFAULT_PATH_LIVE = 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/js/paella_player/config_live.json';


	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
		return self::TABLE_NAME;
	}


	/**
	 * @param $series_identifier
	 *
	 * @return int
	 */
	public static function lookupObjId($series_identifier) {
		$objectSettings = ObjectSettings::where(array( 'series_identifier' => $series_identifier ))->last();
		if ($objectSettings instanceof ObjectSettings) {
			return $objectSettings->getObjId();
		}

		return false;
	}


	/**
	 * @param $obj_id
	 *
	 * @return int
	 */
	public static function lookupSeriesIdentifier($obj_id) {
		$objetSettings = ObjectSettings::where(array( 'obj_id' => $obj_id ))->last();
		if ($objetSettings instanceof ObjectSettings) {
			return $objetSettings->getSeriesIdentifier();
		}

		return false;
	}


    public function create() {
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
	public function getDuplicatesOnSystem() : array
	{
		if (!$this->getObjId() || !$this->getSeriesIdentifier())
		{
			return [];
		}

		$duplicates_ar = self::where(array( 'series_identifier' => $this->getSeriesIdentifier() ))->where(array( 'obj_id' => 0 ), '!=');
		if ($duplicates_ar->count() < 2) {
			return [];
		}

		$duplicates_ids = array();
		// check if duplicates are actually deleted
		foreach ($duplicates_ar->get() as $oc) {
			/** @var ObjectSettings $oc */
			if ($oc->getObjId() != $this->getObjId()) {
				$query = "SELECT ref_id FROM object_reference" . " WHERE deleted is null and obj_id = " . self::dic()->database()->quote($oc->getObjId(), "integer");
				$set = self::dic()->database()->query($query);
				$rec = self::dic()->database()->fetchAssoc($set);

				if ($rec['ref_id']) {
					$duplicates_ids[] = $rec['ref_id'];
				}
			}
		}

		if (!empty($duplicates_ids)) {
			return $duplicates_ids;
		}

		return [];
	}

    /**
     * @return mixed|string
     */
	public function getVideoPortalLink() {
		if ($link_template = PluginConfig::getConfig(PluginConfig::F_VIDEO_PORTAL_LINK)) {
			$link = str_replace('{series_id}', $this->getSeriesIdentifier(), $link_template);
			return '<a target="_blank" href="' . $link . '">' . $link . '</a>';
		}
		return '';
	}


	/**
	 * @return ilObjOpenCast
	 */
	public function getILIASObject() {
	    static $object;
	    if (is_null($object[$this->getObjId()])) {
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
	protected $streaming_only = false;
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
	protected $chat_active = true;
	/**
	 * @var string
	 * @con_has_field  true
	 * @con_fieldtype  text
	 * @con_length     56
	 * @con_is_notnull true
	 */
	protected $paella_player_option = self::PAELLA_OPTION_DEFAULT;
	/**
	 * @var string
	 * @con_has_field  true
	 * @con_fieldtype  text
	 */
	protected $paella_player_file_id = '';
	/**
	 * @var string
	 * @con_has_field true
	 * @con_fieldtype text
	 */
	protected $paella_player_url = '';
	/**
	 * @var string
	 * @con_has_field  true
	 * @con_fieldtype  text
	 * @con_length     56
	 * @con_is_notnull true
	 */
	protected $paella_player_live_option = self::PAELLA_OPTION_DEFAULT;
	/**
	 * @var string
	 * @con_has_field  true
	 * @con_fieldtype  text
	 */
	protected $paella_player_live_file_id = '';
	/**
	 * @var string
	 * @con_has_field true
	 * @con_fieldtype text
	 */
	protected $paella_player_live_url = '';

	public function getObjId() : int
	{
		return $this->obj_id;
	}

	public function setObjId(int $obj_id) : void
	{
		$this->obj_id = $obj_id;
	}

	public function getSeriesIdentifier() : string
	{
		return $this->series_identifier;
	}

	public function setSeriesIdentifier(string $series_identifier) : void
	{
		$this->series_identifier = $series_identifier;
	}

	public function getUseAnnotations() : bool
	{
		return (bool) $this->use_annotations;
	}


	public function setUseAnnotations(bool $use_annotations) : void
	{
		$this->use_annotations = $use_annotations;
	}


	public function getStreamingOnly() : bool
	{
		return (bool) $this->streaming_only;
	}

	public function setStreamingOnly(bool $streaming_only) : void
	{
		$this->streaming_only = $streaming_only;
	}


	public function getPermissionPerClip() : bool
	{
		return (bool) $this->permission_per_clip;
	}


	public function setPermissionPerClip(bool $permission_per_clip) : void
	{
		$this->permission_per_clip = $permission_per_clip;
	}


	public function getAgreementAccepted() : bool
	{
		return (bool) $this->agreement_accepted;
	}


	public function setAgreementAccepted(bool $agreement_accepted) : void
	{
		$this->agreement_accepted = $agreement_accepted;
	}

	public function isOnline(): bool {
		return (bool) $this->obj_online;
	}


	public function setOnline(bool $obj_online) : void
	{
		$this->obj_online = $obj_online;
	}


	public function getIntroductionText() : string
	{
		return $this->intro_text;
	}


	public function setIntroductionText(string $intro_text) : void
	{
		$this->intro_text = $intro_text;
	}


	public function getPermissionAllowSetOwn() : bool
	{
		return (bool) ($this->permission_allow_set_own && $this->getPermissionPerClip());
	}


	public function setPermissionAllowSetOwn(bool $permission_allow_set_own) : void
	{
		$this->permission_allow_set_own = $permission_allow_set_own;
	}

	public function getDefaultView() : int
	{
		return $this->default_view;
	}

	public function setDefaultView(int $default_view) : void
	{
		$this->default_view = $default_view;
	}

	public function isViewChangeable() : bool
	{
		return (bool) $this->view_changeable;
	}

	public function setViewChangeable(bool $view_changeable) : void
	{
		$this->view_changeable = $view_changeable;
	}

    public function setChatActive(bool $chat_active) : void
    {
        $this->chat_active = $chat_active;
    }

    public function isChatActive() : bool
    {
        return (bool) $this->chat_active;
    }

	/**
	 * @return string
	 */
	public function getIntroText(): string
	{
		return $this->intro_text;
	}

	/**
	 * @param string $intro_text
	 */
	public function setIntroText(string $intro_text): void
	{
		$this->intro_text = $intro_text;
	}

	/**
	 * @return bool
	 */
	public function isObjOnline(): bool
	{
		return $this->obj_online;
	}

	/**
	 * @param bool $obj_online
	 */
	public function setObjOnline(bool $obj_online): void
	{
		$this->obj_online = $obj_online;
	}

	/**
	 * @return string
	 */
	public function getPaellaPlayerOption(): string
	{
		return $this->paella_player_option;
	}

	/**
	 * @param string $paella_player_option
	 */
	public function setPaellaPlayerOption(string $paella_player_option): void
	{
		$this->paella_player_option = $paella_player_option;
	}

	/**
	 * @return string
	 */
	public function getPaellaPlayerFileId(): string
	{
		return $this->paella_player_file_id ?? '';
	}

	/**
	 * @param string $paella_player_file_id
	 */
	public function setPaellaPlayerFileId(string $paella_player_file_id): void
	{
		$this->paella_player_file_id = $paella_player_file_id;
	}

	/**
	 * @return string
	 */
	public function getPaellaPlayerUrl(): string
	{
		return $this->paella_player_url ?? '';
	}

	/**
	 * @param string $paella_player_url
	 */
	public function setPaellaPlayerUrl(string $paella_player_url): void
	{
		$this->paella_player_url = $paella_player_url;
	}

	/**
	 * @return string
	 */
	public function getPaellaPlayerLiveOption(): string
	{
		return $this->paella_player_live_option;
	}

	/**
	 * @param string $paella_player_live_option
	 */
	public function setPaellaPlayerLiveOption(string $paella_player_live_option): void
	{
		$this->paella_player_live_option = $paella_player_live_option;
	}

	/**
	 * @return string
	 */
	public function getPaellaPlayerLiveFileId(): string
	{
		return $this->paella_player_live_file_id ?? '';
	}

	/**
	 * @param string $paella_player_live_file_id
	 */
	public function setPaellaPlayerLiveFileId(string $paella_player_live_file_id): void
	{
		$this->paella_player_live_file_id = $paella_player_live_file_id;
	}

	/**
	 * @return string
	 */
	public function getPaellaPlayerLiveUrl(): string
	{
		return $this->paella_player_live_url ?? '';
	}

	/**
	 * @param string $paella_player_live_url
	 */
	public function setPaellaPlayerLiveUrl(string $paella_player_live_url): void
	{
		$this->paella_player_live_url = $paella_player_live_url;
	}


    /**
     * @throws xoctException|ilException
     */
    public function updateAllDuplicates(Metadata $metadata)
    {
		$title = $metadata->getField(MDFieldDefinition::F_TITLE)->getValue();
		$description = $metadata->getField(MDFieldDefinition::F_DESCRIPTION)->getValue();
        foreach ($this->getDuplicatesOnSystem() as $ref_id) {
            $object = new ilObjOpencast($ref_id);
            $object->setTitle($title);
            $object->setDescription($description);
            $object->update();
        }
    }

}
?>
