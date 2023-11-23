<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config\Series;

use Exception;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use xoctException;

class MDFieldConfigSeriesRepository implements MDFieldConfigRepository
{
    /** @var MDCatalogueFactory */
    private $MDCatalogueFactory;

    public function __construct(MDCatalogueFactory $MDCatalogueFactory)
    {
        $this->MDCatalogueFactory = $MDCatalogueFactory;
    }

    /**
     * @return MDFieldConfigSeriesAR[]
     * @throws Exception
     */
    public function getAll(bool $is_admin): array
    {
        $AR = MDFieldConfigSeriesAR::orderBy('sort');
        if (!$is_admin) {
            $AR = $AR->where(['visible_for_permissions' => 'all']);
        }
        return $AR->get();
    }

    /**
     * Important: this returns all fields that are defined as read_only by the Opencast Metadata Catalogue - NOT ONLY by the
     * metadata field configuration in the plugin. This is an important distinction, since fields that are read_only in
     * the plugin but NOT read_only in Opencast might still be prefilled, e.g. with the course title or current username.
     *
     * @return array|MDFieldConfigAR[]
     * @throws xoctException
     */
    public function getAllEditable(bool $is_admin): array
    {
        $MDCatalogue = $this->MDCatalogueFactory->series();
        $AR = MDFieldConfigSeriesAR::orderBy('sort');
        if (!$is_admin) {
            $AR = $AR->where(['visible_for_permissions' => 'all']);
        }
        return array_filter(
            $AR->get(),
            function (MDFieldConfigSeriesAR $ar) use ($MDCatalogue): bool {
                return !$MDCatalogue->getFieldById($ar->getFieldId())->isReadOnly();
            }
        );
    }

    public function getArray(): array
    {
        return MDFieldConfigSeriesAR::orderBy('sort')->getArray();
    }

    public function findByFieldId(string $field_id): ?MDFieldConfigAR
    {
        return MDFieldConfigSeriesAR::where(['field_id' => $field_id])->first();
    }

    public function storeFromArray(array $data): MDFieldConfigAR
    {
        $ar = MDFieldConfigSeriesAR::where(['field_id' => $data['field_id']])->first();
        $is_new = $ar === null;
        if ($is_new) {
            $ar = new MDFieldConfigSeriesAR();
            $ar->setSort($this->getNextSort());
        }
        $ar->setFieldId($data['field_id']);
        $ar->setTitleDe($data['title_de']);
        $ar->setTitleEn($data['title_en']);
        $ar->setVisibleForPermissions($data['visible_for_permissions']);
        $ar->setPrefill($data['prefill']);
        $ar->setReadOnly($data['read_only']);
        $ar->setRequired($data['required']);
        $ar->setValuesFromEditableString($data['values'] ?? '');
        if ($is_new) {
            $ar->create();
        } else {
            $ar->update();
        }
        return $ar;
    }

    private function getNextSort(): int
    {
        /** @var MDFieldConfigSeriesAR $highest */
        $highest = MDFieldConfigSeriesAR::orderBy('sort', 'desc')->first();
        return $highest ? ($highest->getSort() + 1) : 1;
    }

    public function delete($field_id): void
    {
        $activeRecord = MDFieldConfigSeriesAR::where(['field_id' => $field_id])->first();
        if ($activeRecord) {
            $activeRecord->delete();
        }
    }
}
