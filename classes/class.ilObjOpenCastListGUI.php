<?php
use srag\DIC\OpenCast\DICTrait;
/**
 * ListGUI implementation for OpenCast object plugin. This one
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
class ilObjOpenCastListGUI extends ilObjectPluginListGUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	/**
	 * @var ilOpenCastPlugin
	 */
	public $plugin;


	public function initType() {
		$this->setType(ilOpenCastPlugin::PLUGIN_ID);
	}


	/**
	 * @return string
	 */
	public function getGuiClass() {
		return 'ilObjOpenCastGUI';
	}


	/**
	 * @return array
	 */
	public function initCommands() {

		// Always set
		$this->timings_enabled = true;
		$this->subscribe_enabled = true;
		$this->payment_enabled = false;
		$this->link_enabled = false;
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
				'cmd' => ilObjOpenCastGUI::CMD_SHOW_CONTENT,
				'default' => true,
			),
			array(
				'permission' => 'write',
				'cmd' => ilObjOpenCastGUI::CMD_REDIRECT_SETTING,
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
				self::dic()->ctrl()->setParameterByClass("ilObjOpenCastGUI",'item_ref_id',$this->getCommandId());
				$cmd_link = self::dic()->ctrl()->getLinkTargetByClass("ilObjOpenCastGUI", "delete");
				$this->insertCommand($cmd_link, self::dic()->language()->txt("delete"));
				$this->adm_commands_included = true;
				return true;
			}
			return false;
		}

		if($this->checkCommandAccess('delete','',$this->ref_id,$this->type))
		{
			self::dic()->ctrl()->setParameterByClass("ilObjOpenCastGUI", "ref_id",
				$this->container_obj->object->getRefId());
			self::dic()->ctrl()->setParameterByClass("ilObjOpenCastGUI", "item_ref_id", $this->getCommandId());
			$cmd_link = self::dic()->ctrl()->getLinkTargetByClass("ilObjOpenCastGUI", "deleteObject");
			$this->insertCommand($cmd_link, self::dic()->language()->txt("delete"), "",
				"");
			$this->adm_commands_included = true;
		}
	}


	/**
	 * @param bool $get_exceoptions
	 * @return xoctSeries
	 * @throws Exception
	 */
	protected function getSeries($get_exceoptions = false) {
		$xoctSeries = new xoctSeries();
		try {
			$xoctOpenCast = $this->getOpenCast($get_exceoptions);
			if ($xoctOpenCast instanceof xoctOpenCast) {
				$xoctSeries = $xoctOpenCast->getSeries();
			}
		} catch (xoctException $e) {
			if ($get_exceoptions) {
				throw $e;
			}
		}

		return $xoctSeries;
	}


	/**
	 * @param bool $get_exceoptions
	 * @return ActiveRecord|xoctOpenCast
	 * @throws xoctException
	 */
	protected function getOpenCast($get_exceoptions = false) {
		$xoctOpenCast = new xoctOpenCast();
		try {
			xoctConf::setApiSettings();
			$xoctOpenCast = xoctOpenCast::find($this->obj_id);
		} catch (xoctException $e) {
			if ($get_exceoptions) {
				throw $e;
			}
		}

		return $xoctOpenCast;
	}


	/**
	 * @return string
	 * @throws xoctException
	 */
	public function getTitle() {
		$title = $this->getSeries()->getTitle();
		return $title ? $title : parent::getTitle();
	}


	/**
	 * @return string
	 * @throws xoctException
	 */
	function getDescription() {
		$description = $this->getSeries()->getDescription();
		return $description ? $description : parent::getDescription();
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
			$xoctOpenCast = $this->getOpenCast(true);
			if (!$xoctOpenCast instanceof xoctOpenCast) {
				return $props;
			}
			$xoctOpenCast->getSeries();

			if (!$xoctOpenCast->isOnline()) {
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
