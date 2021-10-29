<?php

namespace srag\Plugins\Opencast\Model\Metadata\Builder;

use ILIAS\UI\Component\Input\Field\Input;
use ILIAS\UI\Factory;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use xoctException;

class FormItemBuilder
{

    /**
     * @var Factory
     */
    protected $ui_factory;
    /**
     * @var MDCatalogue
     */
    protected $md_catalogue;
    /**
     * @var MDPrefiller
     */
    protected $prefiller;
    /**
     * @var MDFieldConfigRepository
     */
    private $md_repository;

    public function __construct(MDFieldConfigRepository $md_repository,
                                MDCatalogue             $md_catalogue,
                                MDPrefiller             $prefiller,
                                Factory                 $ui_factory)
    {
        $this->ui_factory = $ui_factory;
        $this->md_catalogue = $md_catalogue;
        $this->prefiller = $prefiller;
        $this->md_repository = $md_repository;
    }

    /**
     * @return Input[]
     */
    public function buildFormElements(): array
    {
        return array_map(function (MDFieldConfigAR $fieldConfigAR) {
            return $this->buildFormElementForMDField($fieldConfigAR);
        }, $this->md_repository->getAll());
    }

    /**
     * @throws xoctException
     */
    public function buildFormElementForMDField(MDFieldConfigAR $fieldConfigAR): Input
    {
        $md_definition = $this->md_catalogue->getFieldById($fieldConfigAR->getFieldId());
        switch ($md_definition->getType()->getTitle()) {
            case MDDataType::TYPE_TEXT:
            case MDDataType::TYPE_TEXT_ARRAY:
                $field = $this->ui_factory->input()->field()->text($fieldConfigAR->getTitle());
                break;
            case MDDataType::TYPE_TEXT_LONG:
                $field = $this->ui_factory->input()->field()->textarea($fieldConfigAR->getTitle());
                break;
            case MDDataType::TYPE_TIME:
            case MDDataType::TYPE_DATE:
                $field = $this->ui_factory->input()->field()->dateTime($fieldConfigAR->getTitle());
                break;
            default:
                throw new xoctException(xoctException::INTERNAL_ERROR,
                    'Unknown MDDataType: ' . $md_definition->getType()->getTitle());
        }
        return $field
            ->withRequired($fieldConfigAR->isRequired())
            ->withValue($this->prefiller->getPrefillValue($fieldConfigAR->getPrefill()))
            ->withDisabled($fieldConfigAR->isReadOnly());
    }
}