<?php

namespace srag\Plugins\Opencast\UI\Modal;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Modal\Modal;

/**
 * Class EventModals
 *
 * @package srag\Plugins\Opencast\UI\Modal
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class EventModals
{

    /**
     * @var Modal
     */
    protected $report_quality_modal;
    /**
     * @var Modal
     */
    protected $report_date_modal;
    /**
     * @var Modal
     */
    protected $republish_modal;


    /**
     * @return Component[]
     */
    public function getAllComponents() : array
    {
        $return = [];
        if (!is_null($this->report_date_modal)) {
            $return[] = $this->report_date_modal;
        }
        if (!is_null($this->report_quality_modal)) {
            $return[] = $this->report_quality_modal;
        }
        if (!is_null($this->republish_modal)) {
            $return[] = $this->republish_modal;
        }
        return $return;
    }


    /**
     * @return Modal|null
     */
    public function getReportQualityModal()
    {
        return $this->report_quality_modal;
    }


    /**
     * @param Modal $report_quality_modal
     */
    public function setReportQualityModal(Modal $report_quality_modal)
    {
        $this->report_quality_modal = $report_quality_modal;
    }


    /**
     * @return Modal|null
     */
    public function getReportDateModal()
    {
        return $this->report_date_modal;
    }


    /**
     * @param Modal $report_date_modal
     */
    public function setReportDateModal(Modal $report_date_modal)
    {
        $this->report_date_modal = $report_date_modal;
    }


    /**
     * @return Modal|null
     */
    public function getRepublishModal()
    {
        return $this->republish_modal;
    }


    /**
     * @param Modal $republish_modal
     */
    public function setRepublishModal(Modal $republish_modal)
    {
        $this->republish_modal = $republish_modal;
    }
}