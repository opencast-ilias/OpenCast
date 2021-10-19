<?php

use ILIAS\UI\Factory as UIFactory;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use srag\Plugins\Opencast\UI\Metadata\Config\MDConfigTableBuilder;

class xoctMetadataConfigGUI extends xoctGUI
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
     * @var \ILIAS\UI\Renderer
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
        self::dic()->toolbar()->addComponent(
            self::dic()->ui()->factory()->button()->primary(
                self::plugin()->translate('btn_add_new_metadata_field'),
                self::dic()->ctrl()->getLinkTarget($this, self::CMD_ADD)
            )
        );
        self::output()->output(
            $this->table_builder->render()
        );
    }


    protected function add()
    {
        $form = $this->ui_factory->input()->container()->form()->standard(
            self::dic()->ctrl()->getFormAction($this),
            [
                'field_id' => $this->ui_factory->input()->field()->text(self::plugin()->translate('md_field_id')),
                'title' => $this->ui_factory->input()->field()->text(self::plugin()->translate('md_title')),
                'visible_for_roles' => $this->ui_factory->input()->field()->multiSelect(
                    self::plugin()->translate('md_visible_for_roles'),
                    ['write' => 'Write', 'read' => 'read', 'edit_videos' => 'Edit Videos'] // TODO: roles
                ),
                'required' => $this->ui_factory->input()->field()->checkbox(self::plugin()->translate('md_required')),
                'read_only' => $this->ui_factory->input()->field()->text(self::plugin()->translate('md_read_only')),
                'prefilled' => $this->ui_factory->input()->field()->text(self::plugin()->translate('md_prefilled')),
            ]
        );
        self::output()->output($this->renderer->render($form));
    }

    protected function create()
    {
        // TODO: Implement create() method.
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
}