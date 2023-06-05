<?php

namespace srag\Plugins\Opencast\UI;

use ILIAS\DI\Container;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Implementation\Component\Input\Container\Filter\Standard;
use ILIAS\UI\Implementation\Component\Input\Field\Input;
use ilObjOpenCastAccess;
use ilUIService;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Event\EventRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventAR;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventRepository;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\User\xoctUser;
use xoct;
use xoctEventTableGUI;
use xoctEventTileGUI;

/**
 * Responsible for building the event table, building the table's filter, and initializing the (fitlered) data.
 */
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
    /**
     * @var Container
     */
    private $dic;

    public function __construct(
        MDFieldConfigEventRepository $MDFieldConfigEventRepository,
        MDCatalogueFactory $MDCatalogueFactory,
        EventRepository $eventRepository,
        Container $dic
    )
    {
        $this->ui_factory = $dic->ui()->factory();
        $this->MDFieldConfigEventRepository = $MDFieldConfigEventRepository;
        $this->ui_service = $dic->uiService();
        $this->MDCatalogue = $MDCatalogueFactory->event();
        $this->eventRepository = $eventRepository;
        $this->dic = $dic;
    }

    public function table($parent_gui, string $parent_cmd, ObjectSettings $objectSettings): xoctEventTableGUI
    {
        return new xoctEventTableGUI(
            $parent_gui,
            $parent_cmd,
            $objectSettings,
            $this->MDFieldConfigEventRepository->getAll(
                ilObjOpenCastAccess::hasPermission('edit_videos')
            ),
            $this->applyFilter(
                $this->eventRepository->getFiltered(['series' => $objectSettings->getSeriesIdentifier()]),
                $objectSettings
            ),
            $this->dic->language()->getLangKey()
        );
    }

    public function tiles($parent_gui, ObjectSettings $objectSettings): xoctEventTileGUI
    {
        return new xoctEventTileGUI(
            $parent_gui,
            $objectSettings,
            $this->applyFilter(
                $this->eventRepository->getFiltered(['series' => $objectSettings->getSeriesIdentifier()]),
                $objectSettings
            )
        );
    }

    public function filter(string $form_action): Standard
    {
        $mdFieldConfigs = $this->MDFieldConfigEventRepository->getAllFilterable(ilObjOpenCastAccess::hasPermission('edit_videos'));
        return $this->ui_service->filter()->standard(
            'xoct_event_table',
            $form_action,
            array_column(array_map(function (MDFieldConfigEventAR $mdFieldConfig) {
                return [$mdFieldConfig->getFieldId(), $this->mdFieldConfigToFilterItem($mdFieldConfig)];
            }, $mdFieldConfigs), 1, 0),
            array_map(function (MDFieldConfigEventAR $mdFieldConfig) {
                return true;
            }, $mdFieldConfigs),
            false,
            false
        );
    }

    public function filterData(): array
    {
        return array_filter($this->ui_service->filter()->getData($this->filter('')) ?? []);
    }

    private function mdFieldConfigToFilterItem(MDFieldConfigEventAR $mdFieldConfig): Input
    {
        $input_f = $this->ui_factory->input()->field();
        $fieldDefinition = $this->MDCatalogue->getFieldById($mdFieldConfig->getFieldId());
        switch ($fieldDefinition->getType()->getTitle()) {
            case MDDataType::text()->getTitle():
            case MDDataType::text_array()->getTitle():
            case MDDataType::text_long()->getTitle():
                return $input_f->text($mdFieldConfig->getTitle($this->dic->language()->getLangKey()));
            case MDDataType::date()->getTitle():
            case MDDataType::datetime()->getTitle():
            case MDDataType::time()->getTitle():
                return $input_f->text($mdFieldConfig->getTitle($this->dic->language()->getLangKey()));
        }
    }

    private function applyFilter(array $events, ObjectSettings $objectSettings): array
    {
        if (!xoct::isIlias7()) { // todo: remove when this is fixed https://mantis.ilias.de/view.php?id=32134
            return $events;
        }
        $filters = $this->filterData();
        return array_filter($events, function (array $event) use ($filters, $objectSettings) {
            $event_object = $event['object'];
            foreach ($filters as $key => $value) {
                /** @var Event $event_object */
                $md_value = $event_object->getMetadata()->getField($key)->toString();
                if (strpos(strtolower($md_value), strtolower($value)) === false) {
                    return false;
                }
            }

            return ilObjOpenCastAccess::hasReadAccessOnEvent(
                $event_object,
                xoctUser::getInstance($this->dic->user()),
                $objectSettings
            );
        });
    }
}
