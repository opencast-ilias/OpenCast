<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config\Event;

use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\MDPrefillOption;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use xoctException;

class MDFieldConfigEventRepository implements MDFieldConfigRepository
{
    /**
     * @var MDCatalogueFactory
     */
    private $MDCatalogueFactory;

    /**
     * @param MDCatalogueFactory $MDCatalogueFactory
     */
    public function __construct(MDCatalogueFactory $MDCatalogueFactory)
    {
        $this->MDCatalogueFactory = $MDCatalogueFactory;
    }

    /**
     * @return MDFieldConfigEventAR[]
     */
    public function getAll() : array
    {
        return MDFieldConfigEventAR::get();
    }

    public function getAllEditable(): array
    {
        return MDFieldConfigEventAR::where(['read_only' => false])->get();
    }

    public function getArray() : array
    {
        return MDFieldConfigEventAR::getArray();
    }

    public function findByFieldId(string $field_id): ?MDFieldConfigAR
    {
        $ar = MDFieldConfigEventAR::where(['field_id' => $field_id])->first();
        return $ar;
    }

    public function storeFromArray(array $data) : MDFieldConfigAR
    {
        $ar = MDFieldConfigEventAR::where(['field_id' => $data['field_id']])->first();
        if (is_null($ar)) {
            $ar = new MDFieldConfigEventAR();
        }
        $ar->setFieldId($data['field_id']);
        $ar->setTitle($data['title']);
        $ar->setVisibleForPermissions($data['visible_for_permissions']);
        $ar->setPrefill(new MDPrefillOption($data['prefill']));
        $ar->setReadOnly($data['read_only']);
        $ar->setRequired($data['required']);
        $ar->store();
        return $ar;
    }

    /**
     * @return MDFieldConfigEventAR[]
     * @throws xoctException
     */
    function getAllFilterable() : array
    {
        $catalogue = $this->MDCatalogueFactory->event();
        return array_filter($this->getAll(), function (MDFieldConfigEventAR $fieldConfig) use ($catalogue) {
            return $catalogue->getFieldById($fieldConfig->getFieldId())
                ->getType()->isFilterable();
        });
    }
}