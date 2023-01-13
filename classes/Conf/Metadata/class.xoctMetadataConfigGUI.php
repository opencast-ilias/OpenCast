<?php

use ILIAS\DI\Container;
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
use srag\Plugins\Opencast\UI\Metadata\Config\MDConfigTable;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;

abstract class xoctMetadataConfigGUI extends xoctGUI
{
    public const CMD_STORE = 'store';
    public const CMD_REORDER = 'reorder';

    /**
     * @var MDFieldConfigRepository
     */
    protected $repository;

    protected static $available_commands = [
        self::CMD_STANDARD,
        self::CMD_EDIT,
        self::CMD_ADD,
        self::CMD_STORE,
        self::CMD_DELETE,
        self::CMD_CONFIRM,
        self::CMD_REORDER
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
     * @var Container
     */
    protected $dic;
    /**
     * @var ilPlugin
     */
    protected $plugin;

    public function __construct(
        MDFieldConfigRepository $repository,
        MDCatalogueFactory $md_catalogue_factory,
        Container $dic,
        ilPlugin $plugin
    ) {
        $this->repository = $repository;
        $this->ui_factory = self::dic()->ui()->factory();
        $this->renderer = self::dic()->ui()->renderer();
        $this->md_catalogue_factory = $md_catalogue_factory;
        $this->dic = $dic;
        $this->plugin = $plugin;
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
            $items[] = $this->ui_factory->link()->standard(
                $field_id,
                self::dic()->ctrl()->getLinkTarget($this, self::CMD_ADD)
            );
        }
        self::dic()->ctrl()->clearParameters($this);
        if (count($items)) {
            self::dic()->toolbar()->addComponent(
                self::dic()->ui()->factory()->dropdown()->standard(
                    $items
                )->withLabel(self::plugin()->translate('btn_add_new_metadata_field'))
            );
        }

        self::output()->output($this->buildTable());
    }

    protected function buildTable(): MDConfigTable
    {
        return new MDConfigTable($this, $this->getTableTitle(), $this->dic, $this->plugin, $this->repository->getArray());
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
        $field_id = filter_input(INPUT_GET, 'field_id', FILTER_SANITIZE_STRING);
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
        $form = $this->buildForm($field_id)->withRequest(self::dic()->http()->request());
        $data = $form->getData();
        if (is_null($data)) {
            $this->dic->ui()->mainTemplate()->setContent($this->renderer->render($form));
            return;
        }
        $this->repository->storeFromArray($data['fields']);
        ilUtil::sendSuccess(self::plugin()->translate('msg_success'), true);
        self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
    }

    protected function confirmDelete()
    {
        // TODO: Implement confirmDelete() method.
    }

    protected function delete()
    {
        $field_id = $this->dic->http()->request()->getQueryParams()['field_id'];
        $this->repository->delete($field_id);
        $this->dic->ctrl()->redirect($this, self::CMD_STANDARD);
    }

    protected function getAvailableMetadataFields(): array
    {
        $already_configured = array_map(function (MDFieldConfigAR $md_config) {
            return $md_config->getFieldId();
        }, $this->repository->getAll(false));
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
        $fields = [
            'field_id' => $this->ui_factory->input()->field()->text(
                self::plugin()->translate('md_field_id')
            )
                                           ->withDisabled(true)
                                           ->withValue($field_id)
                                           ->withRequired(true),
            'title_de' => $this->ui_factory->input()->field()->text(
                self::plugin()->translate('md_title_de')
            )
                                           ->withRequired(true)
                                           ->withValue(
                                               $md_field_config ? $md_field_config->getTitle('de') : ''
                                           ),
            'title_en' => $this->ui_factory->input()->field()->text(
                self::plugin()->translate('md_title_en')
            )
                                           ->withRequired(true)
                                           ->withValue(
                                               $md_field_config ? $md_field_config->getTitle('en') : ''
                                           ),
            'visible_for_permissions' => $this->ui_factory->input()->field()->select(
                self::plugin()->translate('md_visible_for_permissions'),
                [
                    MDFieldConfigAR::VISIBLE_ALL => $this->plugin->txt('md_visible_all'),
                    MDFieldConfigAR::VISIBLE_ADMIN => $this->plugin->txt('md_visible_admin')
                ]
            )->withRequired(true)
                                                          ->withValue(
                                                              $md_field_config ? $md_field_config->getVisibleForPermissions(
                                                              ) : null
                                                          ),
            'required' => $this->ui_factory->input()->field()->checkbox(
                self::plugin()->translate('md_required')
            )
                                           ->withDisabled(
                                               $md_field_def->isRequired() || $md_field_def->isReadOnly()
                                           )
                                           ->withValue(
                                               $md_field_def->isRequired(
                                               ) || ($md_field_config && $md_field_config->isRequired())
                                           ),
            'read_only' => $this->ui_factory->input()->field()->checkbox(
                self::plugin()->translate('md_read_only')
            )
                                            ->withDisabled($md_field_def->isReadOnly())
                                            ->withValue(
                                                $md_field_def->isReadOnly(
                                                ) || ($md_field_config && $md_field_config->isReadOnly())
                                            ),
            'prefill' => $this->ui_factory->input()->field()->select(
                self::plugin()->translate('md_prefill'),
                $this->getPrefillOptions()
            )
                                          ->withRequired(true)
                                          ->withDisabled($md_field_def->isReadOnly())
                                          ->withValue(
                                              $md_field_config ? $md_field_config->getPrefill()->getValue(
                                              ) : MDPrefillOption::T_NONE
                                          )
        ];

        if ($md_field_def->getType()->getTitle() === MDDataType::TYPE_TEXT_SELECTION) {
            $fields['values'] = $this->ui_factory->input()->field()->textarea(
                self::plugin()->translate('md_values'),
                self::plugin()->translate('md_values_info', '', [MDFieldConfigAR::VALUE_SEPERATOR])
            )->withValue(
                $md_field_config ? $md_field_config->getValuesAsEditableString() : ''
            )->withRequired(true)
             ->withDisabled($md_field_def->isReadOnly());
        }

        return $this->ui_factory->input()->container()->form()->standard(
            self::dic()->ctrl()->getFormAction($this, self::CMD_STORE),
            [
                'fields' => $this->ui_factory->input()->field()->section(
                    $fields,
                    $this->plugin->txt('md_conf_form_' . ($md_field_config ? 'edit' : 'create'))
                )
            ]
        );
    }

    protected function getPrefillOptions(): array
    {
        $options = [];
        foreach (MDPrefillOption::$allowed_values as $allowed_value) {
            $options[$allowed_value] = self::plugin()->translate('md_prefill_' . $allowed_value);
        }
        return $options;
    }

    protected function create()
    {
    }

    protected function update()
    {
    }

    abstract protected function getTableTitle(): string;

    protected function reorder(): void
    {
        $ids = $_POST['ids'];
        $sort = 1;
        foreach ($ids as $id) {
            $configAR = $this->repository->findByFieldId($id);
            $configAR->setSort($sort);
            $configAR->update();
            $sort++;
        }
        exit;
    }
}
