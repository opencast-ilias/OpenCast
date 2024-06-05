<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI;

use ILIAS\DI\Container;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Implementation\Component\Input\Container\Filter\Standard;
use ILIAS\UI\Implementation\Component\Input\Field\Text;
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
    private UIFactory $ui_factory;
    private \ilUIService $ui_service;
    private MDFieldConfigEventRepository $MDFieldConfigEventRepository;
    private MDCatalogue $MDCatalogue;
    private EventRepository $eventRepository;
    private Container $dic;

    public function __construct(
        MDFieldConfigEventRepository $MDFieldConfigEventRepository,
        MDCatalogueFactory $MDCatalogueFactory,
        EventRepository $eventRepository,
        Container $dic
    ) {
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
                ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_EDIT_VIDEOS)
            ),
            $this->applyFilter(
                $this->eventRepository->getFiltered(['series' => $objectSettings->getSeriesIdentifier()]),
                $objectSettings
            ),
            $this->dic->language()->getLangKey(),
            $this->MDCatalogue
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
        $mdFieldConfigs = $this->MDFieldConfigEventRepository->getAllFilterable(
            ilObjOpenCastAccess::hasPermission(ilObjOpenCastAccess::PERMISSION_EDIT_VIDEOS)
        );
        return $this->ui_service->filter()->standard(
            'xoct_event_table',
            $form_action,
            array_column(
                array_map(function (MDFieldConfigEventAR $mdFieldConfig): array {
                    return [$mdFieldConfig->getFieldId(), $this->mdFieldConfigToFilterItem($mdFieldConfig)];
                }, $mdFieldConfigs),
                1,
                0
            ),
            array_map(function (MDFieldConfigEventAR $mdFieldConfig): bool {
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

    private function mdFieldConfigToFilterItem(MDFieldConfigEventAR $mdFieldConfig): Text
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
            default:
                return $input_f->text($mdFieldConfig->getTitle($this->dic->language()->getLangKey()));
        }
    }

    private function applyFilter(array $events, ObjectSettings $objectSettings): array
    {
        if (!xoct::isIlias7()) { // todo: remove when this is fixed https://mantis.ilias.de/view.php?id=32134
            return $events;
        }
        $filters = $this->filterData();
        return array_filter($events, function (array $event) use ($filters, $objectSettings): bool {
            $event_object = $event['object'];
            foreach ($filters as $key => $value) {
                /** @var Event $event_object */
                $md_value = $event_object->getMetadata()->getField($key)->toString();
                if (stripos($md_value, strtolower($value)) === false) {
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
