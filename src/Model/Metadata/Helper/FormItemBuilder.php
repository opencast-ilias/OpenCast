<?php

namespace srag\Plugins\Opencast\Model\Metadata\Helper;

use DateTime;
use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\UI\Component\Input\Field\Input;
use ILIAS\UI\Factory as UIFactory;
use srag\Plugins\Opencast\Model\API\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use xoctException;

class FormItemBuilder
{

    /**
     * @var UIFactory
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
    private $md_conf_repository;
    /**
     * @var RefineryFactory
     */
    private $refinery_factory;

    public function __construct(MDCatalogue             $md_catalogue,
                                MDFieldConfigRepository $repository,
                                MDPrefiller             $prefiller,
                                UIFactory               $ui_factory,
                                RefineryFactory         $refinery_factory)
    {
        $this->ui_factory = $ui_factory;
        $this->md_catalogue = $md_catalogue;
        $this->prefiller = $prefiller;
        $this->md_conf_repository = $repository;
        $this->refinery_factory = $refinery_factory;
    }

    /**
     * @return Input[]
     * @throws xoctException
     */
    public function buildFormElements(bool $prefill, ?Metadata $existingMetadata = null): array
    {
        $form_elements = [];
        $md_field_configs = $this->md_conf_repository->getAll();
        array_walk($md_field_configs, function(MDFieldConfigAR $fieldConfigAR) use ($existingMetadata, &$form_elements, $prefill) {
            $key = $fieldConfigAR->getFieldId();
            $md_definition = $this->md_catalogue->getFieldById($fieldConfigAR->getFieldId());
            if (is_null($existingMetadata) && $md_definition->isReadOnly()) {
                // read only fields in creation forms don't make sense
                return;
            }
            $input = $this->buildFormElementForMDField($fieldConfigAR, $md_definition, $prefill && is_null($existingMetadata));
            if (is_null($existingMetadata)) {
                $form_elements[$key] = $input;
                return;
            }
            $value = $existingMetadata->getField($fieldConfigAR->getFieldId())->getValue();
            $input = $value ? $input->withValue($this->formatValue($value, $md_definition)) : $input;
            $form_elements[$key] = $input;
        });
        return $form_elements;
    }

    /**
     * @throws xoctException
     */
    public function buildFormElementForMDField(MDFieldConfigAR $fieldConfigAR, MDFieldDefinition $md_definition, bool $prefill): Input
    {
        switch ($md_definition->getType()->getTitle()) {
            case MDDataType::TYPE_TEXT:
                $field = $this->ui_factory->input()->field()->text($fieldConfigAR->getTitle());
                break;
            case MDDataType::TYPE_TEXT_ARRAY:
                $field = $this->ui_factory->input()->field()->text($fieldConfigAR->getTitle())
                    ->withAdditionalTransformation($this->refinery_factory->custom()->transformation(function(string $value) {
                        return explode(',', $value);
                    }));
                break;
            case MDDataType::TYPE_TEXT_LONG:
                $field = $this->ui_factory->input()->field()->textarea($fieldConfigAR->getTitle());
                break;
            case MDDataType::TYPE_TIME:
            case MDDataType::TYPE_DATETIME:
                $field = $this->ui_factory->input()->field()->dateTime($fieldConfigAR->getTitle())->withUseTime(true);
                break;
            default:
                throw new xoctException(xoctException::INTERNAL_ERROR,
                    'Unknown MDDataType: ' . $md_definition->getType()->getTitle());
        }
        $value = $prefill ? $this->prefiller->getPrefillValue($fieldConfigAR->getPrefill()) : null;
        $field = $field
            ->withRequired($fieldConfigAR->isRequired())
            ->withDisabled($fieldConfigAR->isReadOnly());
        return $value ? $field->withValue($value) : $field;
    }

    private function formatValue($value, MDFieldDefinition $md_definition)
    {
        switch ($md_definition->getType()->getTitle()) {
            case MDDataType::TYPE_DATETIME:
                /** @var $value DateTime */
                return $value->format('Y-m-d H:i:s');
            case MDDataType::TYPE_TEXT_ARRAY:
                return implode(',', $value);
            default:
                return $value;
        }
    }
}