<?php

use ILIAS\DI\Container;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDPrefiller;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\UI\Metadata\Config\MDConfigTable;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use srag\Plugins\Opencast\LegacyHelpers\OutputTrait;
use srag\Plugins\Opencast\Model\ListProvider\ListProvider;
use srag\Plugins\Opencast\LegacyHelpers\TranslatorTrait;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

abstract class xoctMetadataConfigGUI extends xoctGUI
{
    use OutputTrait;
    use TranslatorTrait;
    use LocaleTrait;

    public const CMD_STORE = 'store';
    public const CMD_REORDER = 'reorder';
    public const CMD_LOAD_LIST = 'loadList';
    public const CMD_CONFIRM_LOAD_LIST = 'confirmLoadList';

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
        self::CMD_REORDER,
        self::CMD_CONFIRM_LOAD_LIST,
        self::CMD_LOAD_LIST
    ];


    protected static $listprovider_sources = [
        'license' => 'LICENSES',
        'language' => 'LANGUAGES'
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
    /**
     * @var \ilToolbarGUI
     */
    private $toolbar;
    /**
     * @var \ILIAS\DI\UIServices
     */
    private $ui;
    /**
     * @var \ILIAS\HTTP\Services
     */
    private $http;
    /**
     * @var \srag\Plugins\Opencast\Model\ListProvider\ListProvider
     */
    private $listprovider;
    /**
     * @var array
     */
    private $post_ids = [];

    public function __construct(
        MDFieldConfigRepository $repository,
        MDCatalogueFactory $md_catalogue_factory,
        Container $dic
    ) {
        parent::__construct();
        $this->dic = $dic;
        $ui = $this->dic->ui();
        $this->toolbar = $this->dic->toolbar();
        $this->ui = $this->dic->ui();
        $this->http = $this->dic->http();
        $this->repository = $repository;
        $this->ui_factory = $ui->factory();
        $this->renderer = $ui->renderer();
        $this->md_catalogue_factory = $md_catalogue_factory;
        $this->listprovider = new ListProvider();
        $this->post_ids = $this->http->request()->getParsedBody()['ids'] ?? [];
    }

    /**
     * @throws xoctException
     */
    public function executeCommand(): void
    {
        $cmd = $this->ctrl->getCmd(self::CMD_STANDARD);
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
            $this->ctrl->setParameter($this, 'field_id', $field_id);
            $items[] = $this->ui_factory->link()->standard(
                $field_id,
                $this->ctrl->getLinkTarget($this, self::CMD_ADD)
            );
        }
        $this->ctrl->clearParameters($this);
        if ($items !== []) {
            $this->toolbar->addComponent(
                $this->ui->factory()->dropdown()->standard(
                    $items
                )->withLabel($this->plugin->txt('btn_add_new_metadata_field'))
            );
        }

        $this->ui->mainTemplate()->setContent($this->buildTable()->getHTML());
    }

    protected function buildTable(): MDConfigTable
    {
        return new MDConfigTable(
            $this,
            $this->getTableTitle(),
            $this->dic,
            $this->plugin,
            $this->repository->getArray()
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
        $this->initLoadValuesToolbar($field_id, self::CMD_ADD);
        $form = $this->buildForm($field_id);
        $this->output($this->renderer->render($form));
    }

    /**
     * @throws xoctException
     * @throws ilTemplateException
     * @throws DICException
     */
    protected function edit()
    {
        $field_id = filter_input(INPUT_GET, 'field_id', FILTER_SANITIZE_STRING);
        $this->initLoadValuesToolbar($field_id, self::CMD_EDIT);
        $form = $this->buildForm($field_id);
        $this->output($this->renderer->render($form));
    }

    /**
     * It renders a button in the toolbar menu offering to load the list in case the field is a TYPE_TEXT_SELECTION
     *
     * @param string $field_id the field id
     * @param string $redirect where to redirect to (either add or edit)
     */
    protected function initLoadValuesToolbar($field_id, $redirect): void
    {
        $md_field_def = $this->getMetadataCatalogue()->getFieldById($field_id);
        $md_field_config = $this->repository->findByFieldId($field_id);
        if ($md_field_def->getType()->getTitle() === MDDataType::TYPE_TEXT_SELECTION) {
            $this->ctrl->setParameter($this, 'field_id', $field_id);
            $this->ctrl->setParameter($this, 'redirect', $redirect);
            $button = ilLinkButton::getInstance();
            $button->setCaption($this->plugin->txt('btn_load_metadata_values_from_listprovider'), false);
            $button->setUrl($this->ctrl->getLinkTarget($this, self::CMD_CONFIRM_LOAD_LIST));
            $button->setPrimary(true);
            $this->toolbar->addButtonInstance($button);
        }
    }

    /**
     * It renders a confirmation form before loading the lists from API
     */
    protected function confirmLoadList(): void
    {
        $field_id = filter_input(INPUT_GET, 'field_id', FILTER_SANITIZE_STRING);
        $this->ctrl->setParameter($this, 'field_id', $field_id);
        $redirect = filter_input(INPUT_GET, 'redirect', FILTER_SANITIZE_STRING);
        $this->ctrl->setParameter($this, 'redirect', $redirect);
        $ilConfirmationGUI = new ilConfirmationGUI();
        $ilConfirmationGUI->setFormAction($this->ctrl->getFormAction($this));
        $ilConfirmationGUI->setHeaderText($this->plugin->txt('msg_md_confirm_load_list'));
        $ilConfirmationGUI->setConfirm($this->plugin->txt('md_accept'), self::CMD_LOAD_LIST);
        $ilConfirmationGUI->setCancel($this->plugin->txt('md_cancel'), $redirect);
        $this->ui->mainTemplate()->setContent($ilConfirmationGUI->getHTML());
    }

    /**
     * Performs the load list from API
     */
    protected function loadList(): void
    {
        $this->ctrl->clearParameters($this);
        $field_id = filter_input(INPUT_GET, 'field_id', FILTER_SANITIZE_STRING);
        $this->ctrl->setParameter($this, 'field_id', $field_id);
        $redirect = filter_input(INPUT_GET, 'redirect', FILTER_SANITIZE_STRING);
        try {
            if ($this->listprovider->hasList($field_id) && key_exists($field_id, self::$listprovider_sources)) {
                $md_field_config = $this->repository->findByFieldId($field_id);
                $source = self::$listprovider_sources[$field_id];
                $digested_list = $this->digestList($this->listprovider->getList($source), $field_id);
                if (!empty($digested_list)) {
                    $separator = MDFieldConfigAR::VALUE_SEPERATOR;
                    $converted_list = array_map(function ($key, $value) use ($separator) {
                        return "{$key}{$separator}{$value}";
                    }, array_keys($digested_list), array_values($digested_list));
                    if (!empty($converted_list)) {
                        $encoded_list = base64_encode(json_encode($converted_list));
                        $this->ctrl->setParameter($this, 'possible_values_list', $encoded_list);
                        ilUtil::sendSuccess($this->plugin->txt('msg_md_listproviders_load_success'), true);
                    } else {
                        ilUtil::sendFailure($this->plugin->txt('msg_md_listproviders_load_invalid'), true);
                    }
                }
            } else {
                ilUtil::sendFailure($this->plugin->txt('msg_md_listproviders_empty'), true);
            }
        } catch (xoctException $ex) {
            $error_message = $ex->getMessage();
            if ($ex->getCode() == 403) {
                $error_message = $this->plugin->txt('msg_md_listproviders_no_access');
            }
            ilUtil::sendFailure($error_message, true);
        }

        $this->ctrl->redirect($this, $redirect);
    }

    /**
     * Converts or better say digest the loaded list and replaces the translation to be processed by the plugin.
     *
     * @param array $raw_list the raw list that comes from the listprovider endpoints
     * @param string $field_id the field id to differentiate between the list type
     *
     * @return array of digested list.
     */
    protected function digestList(array $raw_list, string $field_id): array
    {
        $digested = [];
        foreach ($raw_list as $key => $value) {
            if ($field_id == 'language') {
                $split = explode('.', $value);
                $default_text = ucfirst(strtolower($split[count($split) - 1]));
                $translated = $this->getLocaleString(
                    'md_lang_list_' . $key,
                    '',
                    $default_text
                );
                $digested[$key] = $translated;
                continue;
            }
            if ($field_id == 'license') {
                $value = json_decode($value);
                if (!$value) {
                    continue;
                }
                if ($value->selectable) {
                    $split = [$key];
                    if (isset($value->label)) {
                        $split = explode('.', $value->label);
                    }
                    $default_text = $split[count($split) - 1];
                    $translated = $this->getLocaleString(
                        'md_license_list_' . str_replace(['-', ' '], ['', ''], $key),
                        '',
                        $default_text
                    );
                    $digested[$key] = $translated;
                }
                continue;
            }
        }
        return $digested;
    }

    /**
     * @throws xoctException
     * @throws ilTemplateException
     * @throws DICException
     */
    protected function store()
    {
        $field_id = filter_input(INPUT_GET, 'field_id', FILTER_SANITIZE_STRING);
        $form = $this->buildForm($field_id)->withRequest($this->http->request());
        $data = $form->getData();
        if (is_null($data)) {
            $this->dic->ui()->mainTemplate()->setContent($this->renderer->render($form));
            return;
        }
        $this->repository->storeFromArray($data['fields']);
        ilUtil::sendSuccess($this->plugin->txt('msg_success'), true);
        $this->ctrl->redirect($this, self::CMD_STANDARD);
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
        $already_configured = array_map(function (MDFieldConfigAR $md_config): string {
            return $md_config->getFieldId();
        }, $this->repository->getAll(false));
        $available_total = array_map(function (MDFieldDefinition $md_field_def): string {
            return $md_field_def->getId();
        }, $this->getMetadataCatalogue()->getFieldDefinitions());
        return array_diff($available_total, $already_configured);
    }

    abstract protected function getMetadataCatalogue(): MDCatalogue;

    /**
     * @param string $cmd
     * @throws DICException
     * @throws xoctException
     */
    protected function buildForm(string $field_id): Standard
    {
        $this->ctrl->setParameter($this, 'field_id', $field_id);
        $md_field_def = $this->getMetadataCatalogue()->getFieldById($field_id);
        $md_field_config = $this->repository->findByFieldId($field_id);
        $fields = [
            'field_id' => $this->ui_factory->input()->field()->text(
                $this->plugin->txt('md_field_id')
            )
                ->withDisabled(true)
                ->withValue($field_id)
                ->withRequired(true),
            'title_de' => $this->ui_factory->input()->field()->text(
                $this->plugin->txt('md_title_de')
            )
                ->withRequired(true)
                ->withValue(
                    $md_field_config instanceof \srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR ? $md_field_config->getTitle(
                        'de'
                    ) : ''
                ),
            'title_en' => $this->ui_factory->input()->field()->text(
                $this->plugin->txt('md_title_en')
            )
                ->withRequired(true)
                ->withValue(
                    $md_field_config instanceof \srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR ? $md_field_config->getTitle(
                        'en'
                    ) : ''
                ),
            'visible_for_permissions' => $this->ui_factory->input()->field()->select(
                $this->plugin->txt('md_visible_for_permissions'),
                [
                    MDFieldConfigAR::VISIBLE_ALL => $this->plugin->txt('md_visible_all'),
                    MDFieldConfigAR::VISIBLE_ADMIN => $this->plugin->txt('md_visible_admin')
                ]
            )->withRequired(true)
                ->withValue(
                    $md_field_config instanceof \srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR ? $md_field_config->getVisibleForPermissions(
                    ) : null
                ),
            'required' => $this->ui_factory->input()->field()->checkbox(
                $this->plugin->txt('md_required')
            )
                ->withDisabled(
                    $md_field_def->isRequired() || $md_field_def->isReadOnly()
                )
                ->withValue(
                    $md_field_def->isRequired(
                    ) || ($md_field_config && $md_field_config->isRequired())
                ),
                'read_only' => $this->ui_factory->input()->field()->checkbox(
                $this->plugin->txt('md_read_only')
            )
                ->withDisabled($md_field_def->isReadOnly())
                ->withValue(
                    $md_field_def->isReadOnly(
                    ) || ($md_field_config && $md_field_config->isReadOnly())
                )
        ];

        // When the data type is selection, we only provide possible values.
        if ($md_field_def->getType()->getTitle() === MDDataType::TYPE_TEXT_SELECTION) {
            $info = vsprintf($this->plugin->txt('md_values_info'),
                array_fill(0, 3, MDFieldConfigAR::VALUE_SEPERATOR)
            );
            $values = $md_field_config instanceof MDFieldConfigAR ? $md_field_config->getValuesAsEditableString() : '';
            if ($encoded_possible_values_list = filter_input(INPUT_GET, 'possible_values_list', FILTER_SANITIZE_STRING)) {
                $decoded_list = base64_decode($encoded_possible_values_list);
                $possible_values_list = implode("\n", json_decode($decoded_list, true));
                $values = $possible_values_list;
            }
            $fields['values'] = $this->ui_factory->input()->field()->textarea(
                $this->plugin->txt('md_values'),
                $info
            )
                ->withValue($values)
                ->withRequired(true)
                ->withDisabled($md_field_def->isReadOnly());
        } else {
            // Other mds with different data types other than selection, we provide prefill.
            $fields['prefill'] = $this->ui_factory->input()->field()->text(
                        $this->plugin->txt('md_prefill')
                    )
                ->withValue($md_field_config instanceof MDFieldConfigAR ? $md_field_config->getPrefill() : '')
                ->withByline(
                    $this->translate('prefill_info', 'md', [
                        '['. MDPrefiller::USER_PLACEHOLDER_FLAG  . '.' .
                            strtoupper(array_keys(MDPrefiller::$user_properties)[0]) . '], [' .
                            MDPrefiller::USER_PLACEHOLDER_FLAG  . '.' .
                            strtoupper(array_keys(MDPrefiller::$user_properties)[1]) . ']',
                            MDPrefiller::USER_PLACEHOLDER_FLAG,
                            MDPrefiller::COURSE_PLACEHOLDER_FLAG,
                            MDPrefiller::MD_PLACEHOLDER_FLAG,
                            MDPrefiller::COURSE_PLACEHOLDER_FLAG,
                            implode(', ', array_map('strtoupper', array_keys(MDPrefiller::$course_properties))),
                            MDPrefiller::USER_PLACEHOLDER_FLAG,
                            implode(', ', array_map('strtoupper', array_keys(MDPrefiller::$user_properties))),
                            MDPrefiller::MD_PLACEHOLDER_FLAG,
                            implode(', ', array_map('strtoupper', array_keys(MDPrefiller::$md_properties))),
                            MDPrefiller::MD_PLACEHOLDER_FLAG,
                            '['. MDPrefiller::MD_PLACEHOLDER_FLAG  . '.' .
                            strtoupper(array_keys(MDPrefiller::$md_properties)[0]) . '.1]',
                    ])
            );
        }

        return $this->ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, self::CMD_STORE),
            [
                'fields' => $this->ui_factory->input()->field()->section(
                    $fields,
                    $this->plugin->txt(
                        'md_conf_form_' . ($md_field_config instanceof \srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR ? 'edit' : 'create')
                    )
                )
            ]
        );
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
        if (!empty($this->post_ids)) {
            $sort = 1;
            foreach ($this->post_ids as $id) {
                $configAR = $this->repository->findByFieldId($id);
                $configAR->setSort($sort);
                $configAR->update();
                $sort++;
            }
        }
        exit;
    }
}
