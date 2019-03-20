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
	 * @param $params xoctWorkflowParameter[]
	 */
	public function createParamsForAllObjects($params) {
		$all_obj_ids = xoctOpenCast::getArray(null, 'obj_id');
		foreach ($all_obj_ids as $obj_id) {
			foreach ($params as $param) {
				$series_param = new xoctSeriesWorkflowParameter();
				$series_param->setObjId($obj_id);
				$series_param->setParamId($param->getId());
				$series_param->setValue($param->getDefaultValue());
				$series_param->create();
			}
		}
	}


	/**
	 * @param $id
	 * @param $value
	 */
	public function updateById($id, $value) {
		$xoctSeriesWorkflowParameter = xoctSeriesWorkflowParameter::find($id);
		$xoctSeriesWorkflowParameter->setValue($value);
		$xoctSeriesWorkflowParameter->update();
	}

}