<?php

use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\MDPrefillOption;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\UI\Metadata\Config\MDConfigTableBuilder;

abstract class xoctMetadataConfigGUI extends xoctGUI
{
    const CMD_STORE = 'store';

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
        self::CMD_EDIT,
        self::CMD_ADD,
        self::CMD_STORE,
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
     * @var MDCatalogueFactory
     */
    protected $md_catalogue_factory;

    /**
     * @param MDFieldConfigRepository $repository
     * @param MDConfigTableBuilder $table_builder
     */
    public function __construct(MDFieldConfigRepository $repository,
                                MDConfigTableBuilder $table_builder,
                                MDCatalogueFactory $md_catalogue_factory)
    {
        $this->repository = $repository;
        $this->table_builder = $table_builder;
        $this->ui_factory = self::dic()->ui()->factory();
        $this->renderer = self::dic()->ui()->renderer();
        $this->md_catalogue_factory = $md_catalogue_factory;
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

    /**
     * @throws DICException
     * @throws ilTemplateException
     */
    protected function index()
    {
        $items = [];
        foreach ($this->getAvailableMetadataFields() as $field_id) {
            self::dic()->ctrl()->setParameter($this, 'field_id', $field_id);
            $items[] = $this->ui_factory->link()->standard($field_id,
                self::dic()->ctrl()->getLinkTarget($this, self::CMD_ADD));
        }
        self::dic()->ctrl()->clearParameters($this);
        if (count($items)) {
            self::dic()->toolbar()->addComponent(
                self::dic()->ui()->factory()->dropdown()->standard(
                    $items
                )->withLabel(self::plugin()->translate('btn_add_new_metadata_field'))
            );
        }
        self::output()->output(
            $this->table_builder->withTitle($this->getTableTitle())->render()
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
        $form = $this->buildForm($field_id);
        self::output()->output($this->renderer->render($form));
    }

    /**
     * @throws xoctException
     * @throws ilTemplateException
     * @throws DICException
     */
    protected function edit()
    {
        $field_id = filter_input(INPUT_GET, 'row_id_md_config_table', FILTER_SANITIZE_STRING);
        $form = $this->buildForm($field_id);
        self::output()->output($this->renderer->render($form));
    }

    /**
     * @throws xoctException
     * @throws ilTemplateException
     * @throws DICException
     */
    protected function store()
    {
        $field_id = filter_input(INPUT_GET, 'field_id', FILTER_SANITIZE_STRING);
        $form = $this->buildForm($field_id);
        $request = self::dic()->http()->request();
        $data = $form->withRequest($request)->getData();
        if (is_null($data)) {
            self::output()->output($this->renderer->render($form));
            return;
        }
        $this->repository->storeFromArray($data);
        ilUtil::sendSuccess(self::plugin()->translate('msg_success'), true);
        self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
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
        $already_configured = array_map(function (MDFieldConfigAR $md_config) {
            return $md_config->getFieldId();
        }, $this->repository->getAll());
        $available_total = array_map(function (MDFieldDefinition $md_field_def) {
            return $md_field_def->getId();
        }, $this->getMetadataCatalogue()->getFieldDefinitions());
        return array_diff($available_total, $already_configured);
    }

    abstract protected function getMetadataCatalogue(): MDCatalogue;

    /**
     * @param string $field_id
     * @param string $cmd
     * @return Standard
     * @throws DICException
     * @throws xoctException
     */
    protected function buildForm(string $field_id): Standard
    {
        self::dic()->ctrl()->setParameter($this, 'field_id', $field_id);
        $md_field_def = $this->getMetadataCatalogue()->getFieldById($field_id);
        $md_field_config = $this->repository->findByFieldId($field_id);
        return $this->ui_factory->input()->container()->form()->standard(
            self::dic()->ctrl()->getFormAction($this, self::CMD_STORE),
            [
                'field_id' => $this->ui_factory->input()->field()->text(self::plugin()->translate('md_field_id'))
                    ->withDisabled(true)
                    ->withValue($field_id)
                    ->withRequired(true),
                'title' => $this->ui_factory->input()->field()->text(self::plugin()->translate('md_title'))
                    ->withRequired(true)
                    ->withValue($md_field_config ? $md_field_config->getTitle() : ''),
                'visible_for_permissions' => $this->ui_factory->input()->field()->multiSelect(
                    self::plugin()->translate('md_visible_for_permissions'),
                    ['write' => 'Write', 'read' => 'read', 'edit_videos' => 'Edit Videos'] // TODO: roles
                )->withRequired(true)
                    ->withValue($md_field_config ? $md_field_config->getVisibleForPermissions() : []),
                'required' => $this->ui_factory->input()->field()->checkbox(self::plugin()->translate('md_required'))
                    ->withDisabled($md_field_def->isRequired() || $md_field_def->isReadOnly())
                    ->withValue($md_field_def->isRequired() || ($md_field_config && $md_field_config->isRequired())),
                'read_only' => $this->ui_factory->input()->field()->checkbox(self::plugin()->translate('md_read_only'))
                    ->withDisabled($md_field_def->isReadOnly())
                    ->withValue($md_field_def->isReadOnly() || ($md_field_config && $md_field_config->isReadOnly())),
                'prefill' => $this->ui_factory->input()->field()->select(self::plugin()->translate('md_prefill'),
                    $this->getPrefillOptions())
                    ->withDisabled($md_field_def->isReadOnly())
                    ->withValue($md_field_config ? $md_field_config->getPrefill()->getValue() : null),
            ]
        );
    }

    protected function getPrefillOptions(): array
    {
        $options = [];
        foreach (MDPrefillOption::$allowed_values as $allowed_value) {
            $options[$allowed_value] = self::plugin()->translate($allowed_value);
        }
        return $options;
    }

    protected function create()
    {
    }

    protected function update()
    {
    }

    abstract protected function getTableTitle() : string;
}