<?php
require_once('./Services/ActiveRecord/class.ActiveRecord.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Group/class.xoctGroupParticipant.php');

/**
 * Class xoctGroup
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctGroup extends ActiveRecord {

	/**
	 * @return string
	 * @description Return the Name of your Database Table
	 * @deprecated
	 */
	static function returnDbTableName() {
		return 'xoct_group';
	}


	/**
	 * @param $obj_id
	 *
	 * @return xoctGroup[]
	 */
	public static function getAllForObjId($obj_id) {
		return self::where(array( 'serie_id' => $obj_id ))->orderBy('title')->get();
	}


	/**
	 * @var array
	 */
	protected static $series_id_to_groups_map = array();
	/**
	 * @var int
	 *
	 * @con_is_primary true
	 * @con_is_unique  true
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     8
	 * @con_sequence   true
	 */
	protected $id = 0;



	/**
	 * @param          $series_identifier
	 * @param xoctUser $xoctUser
	 *
	 * @return xoctGroupParticipant[]
	 */
	public static function getAllGroupParticipantsOfUser($series_identifier, xoctUser $xoctUser) {
		if (! isset(self::$series_id_to_groups_map[$series_identifier])) {
			$xoctOpenCast = xoctOpenCast::where(array(
				'series_identifier' => $series_identifier,
				'obj_id' => ilObject2::_lookupObjectId($_GET['ref_id'])
				// TODO refoctor to series_id direct in group
			))->last();
			if (! $xoctOpenCast instanceof xoctOpenCast) {
				return array();
			}
			$array = self::where(array( 'serie_id' => $xoctOpenCast->getObjId(), ))->getArray(NULL, 'id');

			self::$series_id_to_groups_map[$series_identifier] = $array;
		}
		$group_id = self::$series_id_to_groups_map[$series_identifier];

		if (count($group_id) == 0) {
			return array();
		}

		$my_groups = xoctGroupParticipant::where(array(
			'user_id' => $xoctUser->getIliasUserId(),
			'group_id' => $group_id
		))->getArray(NULL, 'group_id');
		if (count($my_groups) == 0) {
			return array();
		}

		return xoctGroupParticipant::where(array( 'group_id' => $my_groups ))->get();
	}


	/**
	 * @var int
	 * @con_has_field  true
	 * @con_length     8
	 * @con_fieldtype  integer
	 */
	protected $serie_id;
	/**
	 * @var string
	 * @con_has_field  true
	 * @con_length     1024
	 * @con_fieldtype  text
	 */
	protected $title;
	/**
	 * @var string
	 * @con_has_field  true
	 * @con_length     4000
	 * @con_fieldtype  text
	 */
	protected $description;
	/**
	 * @var int
	 * @con_has_field  true
	 * @con_length     1
	 * @con_fieldtype  integer
	 */
	protected $status;
	/**
	 * @var int
	 */
	protected $user_count = 0;


	public function delete() {
		/**
		 * @var $gp xoctGroupParticipant
		 */
		foreach (xoctGroupParticipant::where(array( 'group_id' => $this->getId() ))->get() as $gp) {
			$gp->delete();
		}
		parent::delete();
	}


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @return int
	 */
	public function getSerieId() {
		return $this->serie_id;
	}


	/**
	 * @param int $serie_id
	 */
	public function setSerieId($serie_id) {
		$this->serie_id = $serie_id;
	}


	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}


	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}


	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}


	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}


	/**
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}


	/**
	 * @param int $status
	 */
	public function setStatus($status) {
		$this->status = $status;
	}
}