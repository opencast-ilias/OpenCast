<?php

/**
 * Class xoctSeriesWorkflowParameterRepository
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctSeriesWorkflowParameterRepository {

	/**
	 * @var self
	 */
	protected static $instance;
	/**
	 * @var array
	 */
	protected $parameters;

	/**
	 * @return self
	 */
	public static function getInstance() {
		if (self::$instance == NULL) {
			$self = new self();
			self::$instance = $self;
		}
		return self::$instance;
	}


	/**
	 * @param $param_ids
	 */
	public function deleteParamsForAllObjectsById($param_ids) {
		if (!is_array($param_ids)) {
			$param_ids = [$param_ids];
		}
		/** @var xoctSeriesWorkflowParameter $series_parameter */
		foreach (xoctSeriesWorkflowParameter::where([ 'param_id' => $param_ids ], [ 'param_id' => 'IN' ])->get() as $series_parameter) {
			$series_parameter->delete();
		}
	}


	/**
	 * @param $params xoctWorkflowParameter[]|xoctWorkflowParameter
	 */
	public function createParamsForAllObjects($params) {
		if (!is_array($params)) {
			$params = [$params];
		}
		$all_obj_ids = xoctOpenCast::getArray(null, 'obj_id');
		foreach ($all_obj_ids as $obj_id) {
			foreach ($params as $param) {
				$series_param = new xoctSeriesWorkflowParameter();
				$series_param->setObjId($obj_id);
				$series_param->setParamId($param->getId());
				$series_param->setValueMember($param->getDefaultValueMember());
				$series_param->setValueAdmin($param->getDefaultValueAdmin());
				$series_param->create();
			}
		}
	}


	/**
	 * @param $id
	 * @param $value_member
	 * @param $value_admin
	 */
	public function updateById($id, $value_member, $value_admin) {
		$xoctSeriesWorkflowParameter = xoctSeriesWorkflowParameter::find($id);
		$xoctSeriesWorkflowParameter->setValueMember($value_member);
		$xoctSeriesWorkflowParameter->setValueAdmin($value_admin);
		$xoctSeriesWorkflowParameter->update();
	}


	/**
	 * @param $obj_id
	 *
	 * @return ilFormPropertyGUI[]
	 */
	public function getFormItemsForObjId($obj_id) {
		$items = [];
		$is_admin = ilObjOpenCastAccess::hasPermission('edit_videos');
		if (xoctConf::getConfig(xoctConf::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES)) {
			/** @var xoctSeriesWorkflowParameter $input */
			foreach (xoctSeriesWorkflowParameter::innerjoin(xoctWorkflowParameter::TABLE_NAME, 'param_id', 'id', [ 'title' ])->where([
				'obj_id' => $obj_id,
				($is_admin ? 'value_admin' : 'value_member') => xoctSeriesWorkflowParameter::VALUE_SHOW_IN_FORM
			])->get() as $input) {
				$cb = new ilCheckboxInputGUI($input->xoct_workflow_param_title ?: $input->getParamId(), xoctEventFormGUI::F_WORKFLOW_PARAMETER . '['
					. $input->getParamId() . ']');
				$items[] = $cb;
			}
		} else {
			/** @var xoctWorkflowParameter $input */
			foreach (xoctWorkflowParameter::where([
				($is_admin ? 'default_value_admin' : 'default_value_member') => xoctWorkflowParameter::VALUE_SHOW_IN_FORM
			])->get() as $input) {
				$cb = new ilCheckboxInputGUI($input->getTitle() ?: $input->getId(), xoctEventFormGUI::F_WORKFLOW_PARAMETER . '['
					. $input->getId() . ']');
				$items[] = $cb;
			}
		}
		return $items;
	}


	/**
	 * @param $obj_id
	 *
	 * @return array
	 */
	public function getAutomaticallySetParametersForObjId($obj_id) {
		$parameters = [];
		if (xoctConf::getConfig(xoctConf::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES)) {
			/** @var xoctSeriesWorkflowParameter $xoctSeriesWorkflowParameter */
			foreach (xoctSeriesWorkflowParameter::where(['obj_id' => $obj_id, 'value' => xoctSeriesWorkflowParameter::VALUE_SET_AUTOMATICALLY])->get() as $xoctSeriesWorkflowParameter) {
				$parameters[$xoctSeriesWorkflowParameter->getParamId()] = 1;
			}
		} else {
			/** @var xoctWorkflowParameter $xoctSeriesWorkflowParameter */
			foreach (xoctWorkflowParameter::where(['value' => xoctWorkflowParameter::VALUE_SET_AUTOMATICALLY])->get() as $xoctSeriesWorkflowParameter) {
				$parameters[$xoctSeriesWorkflowParameter->getId()] = 1;
			}
		}
		return $parameters;
	}

}