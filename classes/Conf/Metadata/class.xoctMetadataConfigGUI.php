<?php

use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventAR;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\MDPrefillOption;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\UI\Metadata\Config\MDConfigTableBuilder;

abstract class xoctMetadataConfigGUI extends xoctGUI
{

    /**
     * @var MDFieldConfigRepository
     */
    protected $repository;
    /**
     * @var MDConfigTableBuilder
     */
    protected $table_builder;

    protected static $available_commands = [
        self::CMD_STANDARD,
        self::CMD_UPDATE,
        self::CMD_ADD,
        self::CMD_CREATE,
        self::CMD_DELETE,
        self::CMD_CONFIRM
    ];
    /**
     * @var UIFactory
     */
    protected $ui_factory;
    /**
     * @var Renderer
     */
    protected $renderer;

    /**
     * @param MDFieldConfigRepository $repository
     * @param MDConfigTableBuilder $table_builder
     */
    public function __construct(MDFieldConfigRepository $repository, MDConfigTableBuilder $table_builder)
    {
        $this->repository = $repository;
        $this->table_builder = $table_builder;
        $this->ui_factory = self::dic()->ui()->factory();
        $this->renderer = self::dic()->ui()->renderer();
    }


    /**
     * @throws xoctException
     */
    public function executeCommand()
    {
        $cmd = self::dic()->ctrl()->getCmd(self::CMD_STANDARD);
        if (!in_array($cmd, self::$available_commands)) {
            throw new xoctException(xoctException::INTERNAL_ERROR, "invalid command: $cmd");
        }
        $this->$cmd();
    }

    protected function index()
    {
        $items = [];
        foreach ($this->getAvailableMetadataFields() as $field_id) {
            self::dic()->ctrl()->setParameter($this, 'field_id', $field_id);
            $items[] = $this->ui_factory->link()->standard($field_id,
                self::dic()->ctrl()->getLinkTarget($this, self::CMD_ADD));
        }
        if (count($items)) {
            self::dic()->toolbar()->addComponent(
                self::dic()->ui()->factory()->dropdown()->standard(
                    $items
                )->withLabel(self::plugin()->translate('btn_add_new_metadata_field'))
            );
        }
        self::output()->output(
            $this->table_builder->render()
        );
    }


    /**
     * @throws ilTemplateException
     * @throws xoctException
     * @throws DICException
     */
    protected function add()
    {
        $field_id = filter_input(INPUT_GET, 'field_id', FILTER_SANITIZE_STRING);
        $form = $this->buildForm($field_id, self::CMD_CREATE);
        self::output()->output($this->renderer->render($form));
    }

    protected function create()
    {
        $field_id = filter_input(INPUT_GET, 'field_id', FILTER_SANITIZE_STRING);
        $form = $this->buildForm($field_id);
        $request = self::dic()->http()->request();
        $data = $form->withRequest($request)->getData();
        if (is_null($data)) {
            self::output()->output($this->renderer->render($form));
            return;
        }
        $this->repository->createFromArray($data);
        ilUtil::sendSuccess(self::plugin()->translate('msg_success'), true);
        self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
    }

    protected function edit()
    {
        // TODO: Implement edit() method.
    }

    protected function update()
    {
        // TODO: Implement update() method.
    }

    protected function confirmDelete()
    {
        // TODO: Implement confirmDelete() method.
    }

    protected function delete()
    {
        // TODO: Implement delete() method.
    }

    protected function getAvailableMetadataFields(): array
    {
        $already_configured = array_map(function (MDFieldConfigEventAR $md_config) {
            return $md_config->getFieldId();
        }, $this->repository->getAll());
        $available_total = array_map(function(MDFieldDefinition $md_field_def) {
            return $md_field_def->getId();
        }, $this->getMetadataCatalogue()->getFieldDefinitions());
        return array_diff($available_total, $already_configured);
    }

    abstract protected function getMetadataCatalogue() : MDCatalogue;

    /**
     * @param string $field_id
     * @param string $cmd
     * @return Standard
     * @throws DICException
     * @throws xoctException
     */
    protected function buildForm(string $field_id, string $cmd = ''): Standard
    {
        self::dic()->ctrl()->saveParameter($this, 'field_id');
        $md_field = $this->getMetadataCatalogue()->getFieldById($field_id);
        $form = $this->ui_factory->input()->container()->form()->standard(
            self::dic()->ctrl()->getFormAction($this, $cmd),
            [
                'field_id' => $this->ui_factory->input()->field()->text(self::plugin()->translate('md_field_id'))
                    ->withDisabled(true)
                    ->withValue($field_id)
                    ->withRequired(true),
                'title' => $this->ui_factory->input()->field()->text(self::plugin()->translate('md_title'))
                    ->withRequired(true),
                'visible_for_roles' => $this->ui_factory->input()->field()->multiSelect(
                    self::plugin()->translate('md_visible_for_roles'),
                    ['write' => 'Write', 'read' => 'read', 'edit_videos' => 'Edit Videos'] // TODO: roles
                )->withRequired(true),
                'required' => $this->ui_factory->input()->field()->checkbox(self::plugin()->translate('md_required'))
                    ->withDisabled($md_field->isRequired() || $md_field->isReadOnly())
                    ->withValue($md_field->isRequired()),
                'read_only' => $this->ui_factory->input()->field()->checkbox(self::plugin()->translate('md_read_only'))
                    ->withDisabled($md_field->isReadOnly())
                    ->withValue($md_field->isReadOnly()),
                'prefill' => $this->ui_factory->input()->field()->select(self::plugin()->translate('md_prefill'),
                    $this->getPrefillOptions())
                    ->withDisabled($md_field->isReadOnly()),
            ]
        );
        return $form;
    }

    protected function getPrefillOptions() : array
    {
        $options = [];
        foreach (MDPrefillOption::$allowed_values as $allowed_value) {
            $options[$allowed_value] = self::plugin()->translate($allowed_value);
        }
        return $options;
    }
}