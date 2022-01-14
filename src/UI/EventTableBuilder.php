<?php

namespace srag\Plugins\Opencast\UI;

use ILIAS\UI\Component\Input\Container\Filter\Filter;
use ILIAS\UI\Component\Input\Field\Input;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Implementation\Component\Input\Container\Filter\Standard;
use ilUIService;
use srag\Plugins\Opencast\Model\Event\EventRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventAR;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventRepository;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use xoctEventTableGUI;

class EventTableBuilder
{
    /**
     * @var UIFactory
     */
    private $ui_factory;
    /**
     * @var ilUIService
     */
    private $ui_service;
    /**
     * @var MDFieldConfigEventRepository
     */
    private $MDFieldConfigEventRepository;
    /**
     * @var MDCatalogue
     */
    private $MDCatalogue;
    /**
     * @var EventRepository
     */
    private $eventRepository;

    public function __construct(MDFieldConfigEventRepository $MDFieldConfigEventRepository,
                                MDCatalogueFactory           $MDCatalogueFactory,
                                UIFactory                    $ui_factory,
                                ilUIService                  $ui_service,
                                EventRepository              $eventRepository)
    {
        $this->ui_factory = $ui_factory;
        $this->MDFieldConfigEventRepository = $MDFieldConfigEventRepository;
        $this->ui_service = $ui_service;
        $this->MDCatalogue = $MDCatalogueFactory->event();
        $this->eventRepository = $eventRepository;
    }

    public function table($parent_gui, string $parent_cmd, ObjectSettings $objectSettings): xoctEventTableGUI
    {
        return new xoctEventTableGUI(
            $parent_gui,
            $parent_cmd,
            $objectSettings,
            $this->MDFieldConfigEventRepository->getAll(),
            $this->eventRepository->getFiltered($this->filterData() + ['series' => $objectSettings->getSeriesIdentifier()])
        );
    }

    public function filter(string $form_action): Standard
    {
        $mdFieldConfigs = $this->MDFieldConfigEventRepository->getAllFilterable();
        return $this->ui_service->filter()->standard(
            'xoct_event_table',
            $form_action,
            array_column(array_map(function (MDFieldConfigEventAR $mdFieldConfig) {
                return [$mdFieldConfig->getFieldId(), $this->mdFieldConfigToFilterItem($mdFieldConfig)];
            }, $mdFieldConfigs), 1, 0),
            array_map(function (MDFieldConfigEventAR $mdFieldConfig) {
                return true;
            }, $mdFieldConfigs),
            true,
            true
        );
    }

    public function filterData(): array
    {
        return array_filter($this->ui_service->filter()->getData($this->filter('')) ?? []);
    }

    private function mdFieldConfigToFilterItem(MDFieldConfigEventAR $mdFieldConfig): \ILIAS\UI\Implementation\Component\Input\Field\Input
    {
        $input_f = $this->ui_factory->input()->field();
        $fieldDefinition = $this->MDCatalogue->getFieldById($mdFieldConfig->getFieldId());
        switch ($fieldDefinition->getType()->getTitle()) {
            case MDDataType::text()->getTitle():
            case MDDataType::text_array()->getTitle():
            case MDDataType::text_long()->getTitle():
                return $input_f->text($mdFieldConfig->getTitle());
            case MDDataType::date()->getTitle():
            case MDDataType::datetime()->getTitle():
            case MDDataType::time()->getTitle():
                // todo: from-to
                return $input_f->text($mdFieldConfig->getTitle());
        }
    }
}