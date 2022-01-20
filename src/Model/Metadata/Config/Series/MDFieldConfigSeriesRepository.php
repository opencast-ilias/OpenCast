<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config\Series;

use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\MDPrefillOption;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use xoctException;

class MDFieldConfigSeriesRepository implements MDFieldConfigRepository
{
    /** @var MDCatalogueFactory */
    private $MDCatalogueFactory;

    /**
     * @param MDCatalogueFactory $MDCatalogueFactory
     */
    public function __construct(MDCatalogueFactory $MDCatalogueFactory)
    {
        $this->MDCatalogueFactory = $MDCatalogueFactory;
    }

    /**
     * @return MDFieldConfigSeriesAR[]
     */
    public function getAll(): array
    {
        return MDFieldConfigSeriesAR::orderBy('sort')->get();
    }

    /**
     * Important: this returns all fields that are defined as read_only by the Opencast Metadata Catalogue - NOT ONLY by the
     * metadata field configuration in the plugin. This is an important distinction, since fields that are read_only in
     * the plugin but NOT read_only in Opencast might still be prefilled, e.g. with the course title or current username.
     *
     * @return array|MDFieldConfigAR[]
     * @throws xoctException
     */
    public function getAllForForm(): array
    {
        $MDCatalogue = $this->MDCatalogueFactory->event();
        return array_filter(MDFieldConfigSeriesAR::orderBy('sort')->get(),
            function (MDFieldConfigSeriesAR $ar) use ($MDCatalogue) {
                return !$MDCatalogue->getFieldById($ar->getFieldId())->isReadOnly();
            });
    }

    public function getArray(): array
    {
        return MDFieldConfigSeriesAR::orderBy('sort')->getArray();
    }

    public function findByFieldId(string $field_id): ?MDFieldConfigAR
    {
        $ar = MDFieldConfigSeriesAR::where(['field_id' => $field_id])->first();
        return $ar;
    }

    public function storeFromArray(array $data): MDFieldConfigAR
    {
        $ar = MDFieldConfigSeriesAR::where(['field_id' => $data['field_id']])->first();
        if (is_null($ar)) {
            $ar = new MDFieldConfigSeriesAR();
        }
        $ar->setFieldId($data['field_id']);
        $ar->setTitle($data['title']);
        $ar->setVisibleForPermissions($data['visible_for_permissions']);
        $ar->setPrefill(new MDPrefillOption($data['prefill']));
        $ar->setReadOnly($data['read_only']);
        $ar->setRequired($data['required']);
        $ar->setSort($this->getNextSort());
        $ar->create();
        return $ar;
    }

    private function getNextSort(): int
    {
        /** @var MDFieldConfigSeriesAR $highest */
        $highest = MDFieldConfigSeriesAR::orderBy('sort', 'desc')->first();
        return $highest ? ($highest->getSort() + 1) : 1;
    }
}