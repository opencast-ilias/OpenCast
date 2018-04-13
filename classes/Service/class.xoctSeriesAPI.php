<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xoctSeriesAPI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctSeriesAPI {

	/**
	 * @var self
	 */
	protected static $instance;
	/**
	 * @var ilTree
	 */
	protected $tree;
	/**
	 * @var
	 */
	protected $objDefinition;


	/**
	 * xoctSeriesAPI constructor.
	 */
	public function __construct() {
		global $tree, $objDefinition;
		$this->tree = $tree;
		$this->objDefinition = $objDefinition;
	}


	/**
	 * @return xoctSeriesAPI
	 */
	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * possible additional data:
	 *
	 *  description => text
	 *  online => boolean
	 *  introduction_text => text
	 *  license => text
	 *  use_annotations => boolean
	 *  streaming_only => boolean
	 *  permission_per_clip => boolean
	 *  member_upload => boolean
	 *
	 *
	 * @param       $parent_ref_id
	 * @param       $title
	 * @param array $additional_data
	 *
	 * @return ilObjOpencast
	 * @throws xoctInternalApiException
	 */
	public function create($parent_ref_id, $title, $additional_data = array()) {
		$parent_type = ilObject2::_lookupType($parent_ref_id);
		if (!$this->objDefinition->isContainer($parent_type)) {
			throw new xoctInternalApiException("object with parent_ref_id $parent_ref_id is of type $parent_type but should be a container");
		}

		$object = new ilObjOpenCast();
		$object->setTitle($title);
		$object->setDescription(isset($additional_data['description']) ? $additional_data['description'] : '');

	}


	/**
	 * @param $ref_id
	 *
	 * @return ilObjOpencast
	 */
	public function read($ref_id) {

	}


	/**
	 * @param $ref_id
	 */
	public function delete($ref_id) {

	}


	/**
	 * possible data:
	 *
	 *  title => text
	 *  description => text
	 *  online => boolean
	 *  introduction_text => text
	 *  license => text
	 *  use_annotations => boolean
	 *  streaming_only => boolean
	 *  permission_per_clip => boolean
	 *  member_upload => boolean
	 *
	 * @param $ref_id
	 */
	public function update($ref_id, $data) {

	}
}