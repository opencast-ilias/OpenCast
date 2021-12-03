<?php

namespace srag\Plugins\Opencast\Model\Metadata\Helper;

use DateTime;
use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\UI\Component\Input\Field\Input;
use ILIAS\UI\Factory as UIFactory;
use srag\Plugins\Opencast\Model\API\Agent\Agent;
use srag\Plugins\Opencast\Model\API\Agent\AgentApiRepository;
use srag\Plugins\Opencast\Model\API\Metadata\Metadata;
use srag\Plugins\Opencast\Model\API\Scheduling\Scheduling;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventAR;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use xoctException;

class MDFormItemBuilder
{

    const LABEL_PREFIX = 'md_';

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
    /**
     * @var AgentApiRepository
     */
    private $agent_repository;

    public function __construct(MDCatalogue             $md_catalogue,
                                MDFieldConfigRepository $repository,
                                MDPrefiller             $prefiller,
                                UIFactory               $ui_factory,
                                RefineryFactory         $refinery_factory,
                                AgentApiRepository      $agent_repository)
    {
        $this->ui_factory = $ui_factory;
        $this->md_catalogue = $md_catalogue;
        $this->prefiller = $prefiller;
        $this->md_conf_repository = $repository;
        $this->refinery_factory = $refinery_factory;
        $this->agent_repository = $agent_repository;
    }

    public function upload(): array
    {
        $form_elements = [];
        $MDFieldConfigARS = $this->md_conf_repository->getAllEditable();
        array_walk($MDFieldConfigARS, function (MDFieldConfigEventAR $md_field_config) use (&$form_elements) {
            $key = $this->prefixPostVar($md_field_config->getFieldId());
            $form_elements[$key] = $this->buildFormElementForMDField($md_field_config,
                $this->prefiller->getPrefillValue($md_field_config->getPrefill()));
        });
        return $form_elements;
    }

    public function edit(Metadata $existing_metadata): array
    {
        $form_elements = [];
        $MDFieldConfigARS = $this->md_conf_repository->getAll();
        array_walk($MDFieldConfigARS, function (MDFieldConfigEventAR $md_field_config) use (&$form_elements, $existing_metadata) {
            $key = $this->prefixPostVar($md_field_config->getFieldId());
            $form_elements[$key] = $this->buildFormElementForMDField($md_field_config,
                $existing_metadata->getField($md_field_config->getFieldId())->getValue());
        });
        return $form_elements;
    }

    public function schedule(): array
    {
        $form_elements = [];
        $MDFieldConfigARS = array_filter($this->md_conf_repository->getAllEditable(), function (MDFieldConfigEventAR $fieldConfigAR) {
            // start date is part of scheduling and location has a special input field
            return !in_array($fieldConfigAR->getFieldId(),
                [MDFieldDefinition::F_START_DATE, MDFieldDefinition::F_LOCATION]);
        });
        array_walk($MDFieldConfigARS, function (MDFieldConfigEventAR $md_field_config) use (&$form_elements) {
            $key = $this->prefixPostVar($md_field_config->getFieldId());
            $form_elements[$key] = $this->buildFormElementForMDField($md_field_config,
                $this->prefiller->getPrefillValue($md_field_config->getPrefill()));
        });
        $form_elements[$this->prefixPostVar(MDFieldDefinition::F_LOCATION)] = $this->buildSchedulingLocationInput();
        return $form_elements;
    }

    public function editScheduled(Metadata $existing_metadata, Scheduling $existing_scheduling): array
    {

    }

    /**
     * @throws xoctException
     */
    public function buildFormElementForMDField(MDFieldConfigAR $fieldConfigAR, $value): Input
    {
        $md_definition = $this->md_catalogue->getFieldById($fieldConfigAR->getFieldId());
        switch ($md_definition->getType()->getTitle()) {
            case MDDataType::TYPE_TEXT:
                $field = $this->ui_factory->input()->field()->text($fieldConfigAR->getTitle());
                break;
            case MDDataType::TYPE_TEXT_ARRAY:
                $field = $this->ui_factory->input()->field()->text($fieldConfigAR->getTitle())
                    ->withAdditionalTransformation($this->refinery_factory->custom()->transformation(function (string $value) {
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
        $field = $field
            ->withRequired($fieldConfigAR->isRequired())
            ->withDisabled($fieldConfigAR->isReadOnly());
        return $value ? $field->withValue($value) : $field;
    }

    private function buildSchedulingLocationInput(): Input
    {
        $options = [];
        $agents = $this->agent_repository->findAll();
        array_walk($agents, function(Agent $agent) use (&$options) {
            $options[$agent->getAgentId()] = $agent->getAgentId();
        });
        // todo: label
        return $this->ui_factory->input()->field()->select('Location', $options)->withRequired(true);
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

    public function prefixPostVar(string $label): string
    {
        return self::LABEL_PREFIX . $label;
    }
}