<?php

use srag\DIC\OpencastObject\DICTrait;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;

/**
 * ListGUI implementation for OpencastObject object plugin. This one
 * handles the presentation in container items (categories, courses, ...)
 * together with the corresponfing ...Access class.
 *
 * PLEASE do not create instances of larger classes here. Use the
 * ...Access class to get DB data and keep it small.
 *
 * @author        Fabian Schmid <fs@studer-raimann.ch>
 * @author        Gabriel Comte <gc@studer-raimann.ch>
 *
 *
 * @version       1.0.00
 */
class ilObjOpencastObjectListGUI extends ilObjectPluginListGUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpencastObjectPlugin::class;

	/**
	 * @var ilOpencastObjectPlugin
	 */
	public $plugin;


	public function initType() {
		$this->setType(ilOpencastObjectPlugin::PLUGIN_ID);
	}


	/**
	 * @return string
	 */
	public function getGuiClass() {
		return 'ilObjOpencastObjectGUI';
	}


	/**
	 * @return array
	 */
	public function initCommands() {

		// Always set
		$this->timings_enabled = true;
		$this->subscribe_enabled = true;
		$this->payment_enabled = false;
		$this->link_enabled = true;
		$this->info_screen_enabled = true;
		$this->delete_enabled = true;
		$this->notes_enabled = true;
		$this->comments_enabled = true;

		// Should be overwritten according to status
		$this->cut_enabled = true;
		$this->copy_enabled = true;

		$commands = array(
			array(
				'permission' => 'read',
				'cmd' => ilObjOpencastObjectGUI::CMD_SHOW_CONTENT,
				'default' => true,
			),
			array(
				'permission' => 'write',
				'cmd' => ilObjOpencastObjectGUI::CMD_REDIRECT_SETTING,
				'lang_var' => 'edit'
			)
		);

		return $commands;
	}


	function insertDeleteCommand() {
		if ($this->std_cmd_only)
		{
			return;
		}

		if(is_object($this->getContainerObject()) and
			$this->getContainerObject() instanceof ilAdministrationCommandHandling)
		{
			if($this->checkCommandAccess('delete','',$this->ref_id,$this->type))
			{
				self::dic()->ctrl()->setParameterByClass("ilObjOpencastObjectGUI",'item_ref_id',$this->getCommandId());
				$cmd_link = self::dic()->ctrl()->getLinkTargetByClass("ilObjOpencastObjectGUI", "delete");
				$this->insertCommand($cmd_link, self::dic()->language()->txt("delete"));
				$this->adm_commands_included = true;
				return true;
			}
			return false;
		}

		if($this->checkCommandAccess('delete','',$this->ref_id,$this->type))
		{
			self::dic()->ctrl()->setParameterByClass("ilObjOpencastObjectGUI", "ref_id",
				$this->container_obj->object->getRefId());
			self::dic()->ctrl()->setParameterByClass("ilObjOpencastObjectGUI", "item_ref_id", $this->getCommandId());
			$cmd_link = self::dic()->ctrl()->getLinkTargetByClass("ilObjOpencastObjectGUI", "deleteObject");
			$this->insertCommand($cmd_link, self::dic()->language()->txt("delete"), "",
				"");
			$this->adm_commands_included = true;
		}
	}


	protected function getObject() {
		return new ilObjOpencastObject($this->ref_id);
	}


	/**
	 * @param bool $get_exceoptions
	 * @return ActiveRecord|ObjectSettings
	 * @throws xoctException
	 */
	protected function getOpencastObject($get_exceoptions = false) {
		$objectSettings = new ObjectSettings();
		try {
			PluginConfig::setApiSettings();
			$objectSettings = ObjectSettings::find($this->obj_id);
		} catch (xoctException $e) {
			if ($get_exceoptions) {
				throw $e;
			}
		}

		return $objectSettings;
	}


	/**
	 * Get item properties
	 *
	 * @return    array        array of property arrays:
	 *                        'alert' (boolean) => display as an alert property (usually in red)
	 *                        'property' (string) => property name
	 *                        'value' (string) => property value
	 */
	public function getCustomProperties($a_prop) {

		$props = parent::getCustomProperties(array());
		try {
			$objectSettings = $this->getOpencastObject(true);
			if (!$objectSettings instanceof ObjectSettings) {
				return $props;
			}

			if (!$objectSettings->isOnline()) {
				$props[] = array(
					'alert' => true,
					'newline' => true,
					'property' => 'Status',
					'value' => 'Offline',
					'propertyNameVisible' => true
				);
			}
		} catch (xoctException $e) {
			$props[] = array(
				'alert' => true,
				'newline' => true,
				'property' => 'API',
				'value' => $e->getMessage(),
				'propertyNameVisible' => false
			);
		}

		return $props;
	}


	/**
	 * get all alert properties
	 *
	 * @return array
	 */
	public function getAlertProperties() {
		$alert = array();
		foreach ((array)$this->getCustomProperties(array()) as $prop) {
			if ($prop['alert'] == true) {
				$alert[] = $prop;
			}
		}

		return $alert;
	}


	/**
	 * @param $unix_timestamp
	 *
	 * @return string formatted date
	 */

	public static function format_date_time($unix_timestamp) {
		$now = time();
		$today = $now - $now % (60 * 60 * 24);
		$yesterday = $today - 60 * 60 * 24;

		if ($unix_timestamp < $yesterday) {
			// given date is older than two days
			$date = date('d. M Y', $unix_timestamp);
		} elseif ($unix_timestamp < $today) {
			// given date yesterday
			$date = self::dic()->language()->txt('yesterday');
		} else {
			// given date is today
			$date = self::dic()->language()->txt('today');
		}

		return $date . ', ' . date('H:i', $unix_timestamp);
	}
}

?>
