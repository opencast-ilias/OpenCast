<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.ilOpenCastPlugin.php');
include_once('./Services/Repository/classes/class.ilObjectPluginListGUI.php');

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

	/**
	 * @var ilOpenCastPlugin
	 */
	public $plugin;


	public function initType() {
		$this->setType(ilOpenCastPlugin::XOCT);
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
	public function getCommands() {
		$this->commands = $this->initCommands();

		return parent::getCommands();
	}


	/**
	 * @return array
	 */
	public function initCommands() {
		$request = xoctRequest::getInstanceForOpenCastObjectId($this->obj_id);

		// Always set
		$this->timings_enabled = false;
		$this->subscribe_enabled = false;
		$this->payment_enabled = false;
		$this->link_enabled = false;
		$this->info_screen_enabled = true;
		$this->delete_enabled = false;

		// Should be overwritten according to status
		$this->cut_enabled = false;
		$this->copy_enabled = false;

		$commands = array(
			array(
				'permission' => 'read',
				'cmd' => 'showContent',
				'default' => true
			)
		);

		switch ($request->getStatus()) {
			case xoctRequest::STATUS_IN_PROGRRESS:
				break;
			case xoctRequest::STATUS_REFUSED:
			case xoctRequest::STATUS_COPY:

				$commands[] = array(
					'txt' => $this->plugin->txt('common_cmd_delete'),
					'permission' => 'delete',
					'cmd' => 'confirmDeleteObject',
					'default' => false
				);
				break;

			case xoctRequest::STATUS_NEW:
			case xoctRequest::STATUS_RELEASED:
				$commands[] = array(
					'txt' => $this->plugin->txt('common_cmd_delete'),
					'permission' => 'delete',
					'cmd' => 'confirmDeleteObject',
					'default' => false
				);

				$this->cut_enabled = true;
				$this->copy_enabled = true;
				break;
		}

		return $commands;
	}


	/**
	 * @param $title
	 *
	 * @return bool|void
	 */
	public function setTitle($title) {
		$xoctRequest = xoctRequest::getInstanceForOpenCastObjectId($this->obj_id);
		$this->title = $xoctRequest->getTitle() . ' / ' . $xoctRequest->getAuthor();
		parent::setTitle($this->title);
		$this->default_command = false;
	}


	/**
	 * Get item properties
	 *
	 * @return    array        array of property arrays:
	 *                        "alert" (boolean) => display as an alert property (usually in red)
	 *                        "property" (string) => property name
	 *                        "value" (string) => property value
	 */
	public function getProperties() {
		global $lng;

		$request = xoctRequest::getInstanceForOpenCastObjectId($this->obj_id);

		$info_string = '';
		$info_string .= $request->getBook() . ' ';
		$info_string .= '(' . $request->getPublishingYear() . '), ';
		// $info_string .= $this->plugin->txt('obj_list_page') . ' ';
		$info_string .= $request->getPages();

		$props[] = array(
			'alert' => false,
			'newline' => true,
			'property' => 'description',
			'value' => $info_string,
			'propertyNameVisible' => false
		);

		switch ($request->getStatus()) {
			case xoctRequest::STATUS_NEW:
				$props[] = array(
					'alert' => true,
					'newline' => true,
					'property' => $lng->txt('status'),
					'value' => $this->plugin->txt('request_status_' . xoctRequest::STATUS_NEW),
					'propertyNameVisible' => true
				);
				$props[] = array(
					'alert' => false,
					'newline' => true,
					'property' => $this->plugin->txt('request_creation_date'),
					'value' => self::format_date_time($request->getCreateDate()),
					'propertyNameVisible' => true
				);
				break;
			case xoctRequest::STATUS_IN_PROGRRESS:
				$props[] = array(
					'alert' => true,
					'newline' => true,
					'property' => $lng->txt('status'),
					'value' => $this->plugin->txt('request_status_' . xoctRequest::STATUS_IN_PROGRRESS),
					'propertyNameVisible' => true
				);
				$props[] = array(
					'alert' => false,
					'newline' => true,
					'property' => $this->plugin->txt('request_creation_date'),
					'value' => self::format_date_time($request->getCreateDate()),
					'propertyNameVisible' => true
				);
				break;

			case xoctRequest::STATUS_REFUSED:
				$props[] = array(
					'alert' => true,
					'newline' => true,
					'property' => $lng->txt('status'),
					'value' => $this->plugin->txt('request_status_' . xoctRequest::STATUS_REFUSED),
					'propertyNameVisible' => true
				);
				$props[] = array(
					'alert' => false,
					'newline' => true,
					'property' => $this->plugin->txt('request_creation_date'),
					'value' => self::format_date_time($request->getCreateDate()),
					'propertyNameVisible' => true
				);
				$props[] = array(
					'alert' => false,
					'newline' => true,
					'property' => $this->plugin->txt('request_refusing_date'),
					'value' => self::format_date_time($request->getDateLastStatusChange()),
					'propertyNameVisible' => true
				);
				break;

			case xoctRequest::STATUS_RELEASED:
			case xoctRequest::STATUS_COPY:
				// Display a warning if a file is not a hidden Unix file, and
				// the filename extension is missing
				$file = $request->getAbsoluteFilePath();

				if (!preg_match('/^\\.|\\.[a-zA-Z0-9]+$/', $file)) {
					$props[] = array(
						'alert' => false,
						'property' => $lng->txt('filename_interoperability'),
						'value' => $lng->txt('filename_extension_missing'),
						'propertyNameVisible' => false
					);
				}
				$props[] = array(
					'alert' => false,
					'property' => $lng->txt('size'),
					'value' => ilFormat::formatSize(filesize($file), 'short'),
					'propertyNameVisible' => false,
					'newline' => true,
				);
				$props[] = array(
					'alert' => false,
					'newline' => true,
					'property' => $this->plugin->txt('request_upload_date'),
					'value' => self::format_date_time($request->getDateLastStatusChange()),
					'propertyNameVisible' => true
				);

				if (!ilObjOpenCastAccess::hasAccessToDownload($this->ref_id)) {
					$props[] = array(
						'alert' => true,
						'newline' => true,
						'property' => 'description',
						'value' => $this->plugin->txt('status_no_access_to_download'),
						'propertyNameVisible' => false
					);
				}
				
				break;
		}

		return $props;
	}


	/**
	 * insert item title
	 *
	 * @overwritten
	 */
	public function insertTitle() {
		/**
		 * @var ilCtrl $ilCtrl
		 */
		global $ilCtrl;

		$request = xoctRequest::getInstanceForOpenCastObjectId($this->obj_id);

		switch ($request->getStatus()) {
			case xoctRequest::STATUS_NEW:
			case xoctRequest::STATUS_IN_PROGRRESS:
			case xoctRequest::STATUS_REFUSED:
				$this->default_command = false;
				break;
			case xoctRequest::STATUS_RELEASED:
			case xoctRequest::STATUS_COPY:
				if (ilObjOpenCastAccess::hasAccessToDownload($this->ref_id)) {
					$ilCtrl->setParameterByClass('ilObjOpenCastGUI', xoctRequestGUI::XDGL_ID, xoctRequest::getIdByOpenCastObjectId($this->obj_id));
					$this->default_command = array(
						'link' => $ilCtrl->getLinkTargetByClass('ilObjOpenCastGUI', ilObjOpenCastGUI::CMD_SEND_FILE),
						'frame' => '_top'
					);
				} else {
					$this->default_command = false;
				}

				break;
		}

		parent::insertTitle();
	}


	/**
	 * @param $unix_timestamp
	 *
	 * @return string formatted date
	 */

	public static function format_date_time($unix_timestamp) {
		global $lng;

		$now = time();
		$today = $now - $now % (60 * 60 * 24);
		$yesterday = $today - 60 * 60 * 24;

		if ($unix_timestamp < $yesterday) {
			// given date is older than two days
			$date = date('d. M Y', $unix_timestamp);
		} elseif ($unix_timestamp < $today) {
			// given date yesterday
			$date = $lng->txt('yesterday');
		} else {
			// given date is today
			$date = $lng->txt('today');
		}

		return $date . ', ' . date('H:i', $unix_timestamp);
	}
}

?>
