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
		/** @var xoctWorkflowParameter $series_parameter */
		foreach (xoctWorkflowParameter::where([ 'param_id' => $param_ids ], [ 'param_id' => 'IN' ])->get() as $series_parameter) {
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
				$series_param = new xoctWorkflowParameter();
				$series_param->setObjId($obj_id);
				$series_param->setParamId($param->getId());
				$series_param->setValue($param->getDefaultValue());
				$series_param->create();
			}
		}
	}
}