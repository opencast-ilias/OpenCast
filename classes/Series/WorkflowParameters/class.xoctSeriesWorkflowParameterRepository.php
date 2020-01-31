<?php

use srag\Plugins\Opencast\UI\Input\EventFormGUI;

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
	 * @param $obj_id
	 * @param $param_id
	 *
	 * @return xoctSeriesWorkflowParameter
	 */
	public static function getByObjAndParamId($obj_id, $param_id) {
		return xoctSeriesWorkflowParameter::where(['obj_id' => $obj_id, 'param_id' => $param_id])->first();
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
				(new xoctSeriesWorkflowParameter())
					->setObjId($obj_id)
					->setParamId($param->getId())
					->setValueMember($param->getDefaultValueMember())
					->setValueAdmin($param->getDefaultValueAdmin())
					->create();
			}
		}
	}


	/**
	 * @param $id
	 * @param $value_member
	 * @param $value_admin
	 */
	public function updateById($id, $value_member, $value_admin) {
		xoctSeriesWorkflowParameter::find($id)
			->setValueMember($value_member)
			->setValueAdmin($value_admin)
			->update();
	}


	/**
	 * @param $obj_id
	 * @param $as_admin
	 *
	 * @return array Format $id => $title
	 */
	public function getParametersInFormForObjId($obj_id, $as_admin) {
		$parameter = [];
		if (xoctConf::getConfig(xoctConf::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES)) {
			/** @var xoctSeriesWorkflowParameter $input */
			foreach (xoctSeriesWorkflowParameter::innerjoin(xoctWorkflowParameter::TABLE_NAME, 'param_id', 'id', [ 'title' ])->where([
				'obj_id' => $obj_id,
				($as_admin ? 'value_admin' : 'value_member') => xoctSeriesWorkflowParameter::VALUE_SHOW_IN_FORM
			])->get() as $input) {
				$parameter[$input->getParamId()] = $input->xoct_workflow_param_title ?: $input->getParamId();
			}
		} else {
			/** @var xoctWorkflowParameter $input */
			foreach (xoctWorkflowParameter::where([
				($as_admin ? 'default_value_admin' : 'default_value_member') => xoctWorkflowParameter::VALUE_SHOW_IN_FORM
			])->get() as $input) {
				$parameter[$input->getId()] = $input->getTitle() ?: $input->getId();
			}
		}
		return $parameter;
	}

	/**
	 * @return array Format $id => $title
	 */
	public function getGeneralParametersInForm() : array
    {
		$parameter = [];
        /** @var xoctWorkflowParameter $input */
        foreach (xoctWorkflowParameter::where([
            'default_value_admin' => xoctWorkflowParameter::VALUE_SHOW_IN_FORM
        ])->get() as $input) {
            $parameter[$input->getId()] = $input->getTitle() ?: $input->getId();
        }
		return $parameter;
	}

	/**
	 * @param      $obj_id
	 *
	 * @param bool $as_admin
	 *
	 * @return ilFormPropertyGUI[]
	 */
	public function getFormItemsForObjId($obj_id, $as_admin) : array {
		$items = [];
		foreach ($this->getParametersInFormForObjId($obj_id, $as_admin) as $id => $title) {
			$cb = new ilCheckboxInputGUI($title, EventFormGUI::F_WORKFLOW_PARAMETER . '['
				. $id . ']');
			$items[] = $cb;
		}
		return $items;
	}


	/**
	 * @param      $obj_id
	 *
	 * @param bool $as_admin
	 *
	 * @return array
	 */
	public function getAutomaticallySetParametersForObjId($obj_id, $as_admin = true) {
		$parameters = [];
		if (xoctConf::getConfig(xoctConf::F_ALLOW_WORKFLOW_PARAMS_IN_SERIES)) {
			/** @var xoctSeriesWorkflowParameter $xoctSeriesWorkflowParameter */
			foreach (xoctSeriesWorkflowParameter::where([
				'obj_id' => $obj_id,
				($as_admin ? 'value_admin' : 'value_member') => xoctSeriesWorkflowParameter::VALUE_ALWAYS_ACTIVE
			])->get() as $xoctSeriesWorkflowParameter) {
				$parameters[$xoctSeriesWorkflowParameter->getParamId()] = 1;
			}
			/** @var xoctSeriesWorkflowParameter $xoctSeriesWorkflowParameter */
			foreach (xoctSeriesWorkflowParameter::where([
				'obj_id' => $obj_id,
				($as_admin ? 'value_admin' : 'value_member') => xoctSeriesWorkflowParameter::VALUE_ALWAYS_INACTIVE
			])->get() as $xoctSeriesWorkflowParameter) {
				$parameters[$xoctSeriesWorkflowParameter->getParamId()] = 0;
			}
		} else {
			/** @var xoctWorkflowParameter $xoctSeriesWorkflowParameter */
			foreach (xoctWorkflowParameter::where([ ($as_admin ? 'default_value_admin' : 'default_value_member') => xoctWorkflowParameter::VALUE_ALWAYS_ACTIVE ])
				         ->get() as $xoctSeriesWorkflowParameter) {
				$parameters[$xoctSeriesWorkflowParameter->getId()] = 1;
			}
			/** @var xoctWorkflowParameter $xoctSeriesWorkflowParameter */
			foreach (xoctWorkflowParameter::where([ ($as_admin ? 'default_value_admin' : 'default_value_member') => xoctWorkflowParameter::VALUE_ALWAYS_INACTIVE ])
				         ->get() as $xoctSeriesWorkflowParameter) {
				$parameters[$xoctSeriesWorkflowParameter->getId()] = 0;
			}
		}
		return $parameters;
	}


    /**
     * @return array
     */
    public function getGeneralAutomaticallySetParameters()
    {
        $parameters = [];
        /** @var xoctWorkflowParameter $xoctSeriesWorkflowParameter */
        foreach (xoctWorkflowParameter::where(['default_value_admin' => xoctWorkflowParameter::VALUE_ALWAYS_ACTIVE ])
            ->get() as $xoctSeriesWorkflowParameter) {
            $parameters[$xoctSeriesWorkflowParameter->getId()] = 1;
        }
        /** @var xoctWorkflowParameter $xoctSeriesWorkflowParameter */
        foreach (xoctWorkflowParameter::where(['default_value_admin' => xoctWorkflowParameter::VALUE_ALWAYS_INACTIVE ])
            ->get() as $xoctSeriesWorkflowParameter) {
            $parameters[$xoctSeriesWorkflowParameter->getId()] = 0;
        }
        return $parameters;
    }


	/**
	 * @param $obj_id
	 */
	public function syncAvailableParameters($obj_id) {
		/** @var xoctWorkflowParameter[] $workflow_parameters */
		$workflow_parameters = xoctWorkflowParameter::get();
		$series_parameters = xoctSeriesWorkflowParameter::where(['obj_id' => $obj_id])->getArray('param_id');

		// create missing
		foreach ($workflow_parameters as $workflow_parameter) {
			if (!isset($series_parameters[$workflow_parameter->getId()])) {
				(new xoctSeriesWorkflowParameter())
					->setObjId($obj_id)
					->setParamId($workflow_parameter->getId())
					->setValueAdmin($workflow_parameter->getDefaultValueAdmin())
					->setValueMember($workflow_parameter->getDefaultValueMember())
					->create();
			} else {
				unset($series_parameters[$workflow_parameter->getId()]);
			}
		}

		// delete not existing
		foreach ($series_parameters as $id => $series_parameter) {
			xoctSeriesWorkflowParameter::find($id)->delete();
		}
	}


    /**
     * @return array
     */
    public function getGeneralFormItems() : array
    {
        $items = [];
        foreach ($this->getGeneralParametersInForm() as $id => $title) {
            $cb = new ilCheckboxInputGUI($title, EventFormGUI::F_WORKFLOW_PARAMETER . '['
                . $id . ']');
            $items[] = $cb;
        }
        return $items;
    }

}