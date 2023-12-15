<?php

declare(strict_types=1);

use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\UserSettings\UserSettingsRepository;
use srag\Plugins\Opencast\DI\OpencastDIC;

/**
 * Class xoctEventTileGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctEventTileGUI
{
    public const GET_PAGE = 'page';
    /**
     * @var ilOpenCastPlugin
     */
    protected $plugin;
    /**
     * @var OpencastDIC
     */
    protected $container;

    /**
     * @var xoctEventGUI
     */
    protected $parent_gui;
    /**
     * @var ObjectSettings
     */
    protected $objectSettings;
    /**
     * @var bool
     */
    protected $has_scheduled_events = false;
    /**
     * @var Event[]
     */
    protected $events;
    /**
     * @var Factory
     */
    protected $factory;
    /**
     * @var Renderer
     */
    protected $renderer;
    /**
     * @var int
     */
    protected $page = 0;
    /**
     * @var int
     */
    protected $limit;
    /**
     * @var \ilLanguage
     */
    private $language;
    /**
     * @var \ilObjUser
     */
    private $user;
    /**
     * @var \ilCtrl
     */
    private $ctrl;

    public function __construct(xoctEventGUI $parent_gui, ObjectSettings $objectSettings, array $data)
    {
        global $DIC;
        $ui = $DIC->ui();
        $user = $DIC->user();
        $this->language = $DIC->language();
        $this->container = OpencastDIC::getInstance();
        $this->plugin = $this->container->plugin();
        $this->user = $DIC->user();
        $this->ctrl = $DIC->ctrl();
        $this->parent_gui = $parent_gui;
        $this->objectSettings = $objectSettings;
        $this->factory = $ui->factory();
        $this->renderer = $ui->renderer();
        $this->page = (int) filter_input(INPUT_GET, self::GET_PAGE) ?: $this->page;
        $ref_id = (int) ($DIC->http()->request()->getQueryParams()['ref_id'] ?? 0);
        $this->limit = UserSettingsRepository::getTileLimitForUser($user->getId(), $ref_id);
        $this->events = array_values(
            array_map(static function ($item) {
                return $item['object'];
            }, $this->sortData($data))
        );
    }

    /**
     * @return string
     * @throws ilTemplateException
     * @throws xoctException
     */
    public function getHTML()
    {
        $container_tpl = $this->plugin->getTemplate('default/tpl.tile_container.html');
        $container_tpl->setVariable('LIMIT_SELECTOR', $this->getLimitSelectorHTML());
        $container_tpl->setVariable('PAGINATION_TOP', $this->getPaginationHTML());
        $container_tpl->setVariable('PAGINATION_BOTTOM', $this->getPaginationHTML());

        $from = $this->page * $this->limit;
        $to = ($this->page + 1) * $this->limit;
        for ($i = $from; $i < $to && isset($this->events[$i]); $i++) {
            $event = $this->events[$i];
            $event_renderer = new xoctEventRenderer($event, $this->objectSettings);

            $dropdown = $this->factory->dropdown()->standard($event_renderer->getActions());

            $image = $this->factory->image()->standard(
                $event->publications()->getThumbnailUrl(),
                "Thumbnail"
            );

            $tile_tpl = $this->plugin->getTemplate('default/tpl.event_tile.html');
            $event_renderer->insertTitle($tile_tpl);
            $event_renderer->insertState($tile_tpl);

            $buttons_tpl = $this->plugin->getTemplate('default/tpl.event_buttons.html');
            $event_renderer->insertPlayerLink($buttons_tpl, 'link', 'LINK', 'btn-default');

            // The object settings will be checked based from within the insertDownloadLink method!
            $event_renderer->insertDownloadLink($buttons_tpl, 'link', 'LINK', 'btn-default');

            if ($this->objectSettings->getUseAnnotations()) {
                $event_renderer->insertAnnotationLink($buttons_tpl, 'link', 'LINK', 'btn-default');
            }

            // In order to render dropdowns, we have to call its method here (at the end),
            // because the dropdown list gets its value during the call of download and annotate insertion.
            $event_renderer->renderDropdowns($buttons_tpl);

            $tile_tpl->setVariable('EVENT_BUTTONS', $buttons_tpl->get());

            $card = $this->factory->card()->repositoryObject(
                $tile_tpl->get(),
                $image
            )->withActions(
                $dropdown
            );

            $sections = [];

            $property_list = [];
            $property_list[$this->language->txt('date')] = $event_renderer->getStartHTML();
            if ($this->objectSettings->getPermissionPerClip()) {
                $property_list[$this->language->txt('owner')] = $event_renderer->getOwnerHTML();
            }
            $sections[] = $this->factory->listing()->descriptive($property_list);

            $card = $card->withSections($sections);

            $container_tpl->setCurrentBlock('tile');
            $container_tpl->setVariable('TILE', $this->renderer->renderAsync($card));
            $container_tpl->parseCurrentBlock();
        }
        return $container_tpl->get();
    }

    /**
     * @param Event[] $events
     *
     * @return mixed
     */
    protected function sortData(array $events)
    {
        $tab_prop = null;
        if (class_exists('ilTablePropertiesStorageGUI')) {
            $tab_prop = new ilTablePropertiesStorageGUI();
        } elseif (class_exists('ilTablePropertiesStorage')) {
            $tab_prop = new ilTablePropertiesStorage();
        }
        if ($tab_prop !== null) {
            $direction = $tab_prop->getProperty(
                xoctEventTableGUI::getGeneratedPrefix($this->parent_gui->getObjId()),
                $this->user->getId(),
                'direction'
            ) ?? 'asc';
            $order = $tab_prop->getProperty(
                xoctEventTableGUI::getGeneratedPrefix($this->parent_gui->getObjId()),
                $this->user->getId(),
                'order'
            ) ?? 'start';
        }

        switch ($order) {
            case 'start_unix':
                $order = 'start';
                break;
            case 'created_unix':
                $order = 'created';
                break;
        }

        if (class_exists('ilUtil') && method_exists('ilUtil', 'sortArray')) {
            return ilUtil::sortArray(
                $events,
                $order,
                $direction
            );
        } elseif (class_exists('ilArrayUtil') && method_exists('ilArrayUtil', 'sortArray')) {
            return ilArrayUtil::sortArray(
                $events,
                $order,
                $direction
            );
        }

        return $events;
    }

    /**
     * @return bool
     */
    public function hasScheduledEvents()
    {
        return $this->has_scheduled_events;
    }

    /**
     * @return string
     */
    protected function getPaginationHTML()
    {
        $max_count = count($this->events);
        $pages = ($max_count == 0) ? 1 : (int) ceil($max_count / $this->limit);
        $pagination = $this->factory->viewControl()->pagination()
                                    ->withMaxPaginationButtons($pages)
                                    ->withTotalEntries($max_count)
                                    ->withCurrentPage($this->page)
                                    ->withTargetURL(
                                        $this->ctrl->getLinkTarget(
                                            $this->parent_gui,
                                            xoctEventGUI::CMD_STANDARD,
                                            '',
                                            true
                                        ),
                                        self::GET_PAGE
                                    )
                                    ->withPageSize($this->limit);

        return $this->renderer->renderAsync($pagination);
    }

    /**
     * @return string
     * @throws ilTemplateException
     */
    protected function getLimitSelectorHTML(): string
    {
        $tpl = $this->plugin->getTemplate('default/tpl.tile_limit_selector.html');
        $tpl->setVariable(
            'LIMIT_SELECTOR_FORM_ACTION',
            $this->ctrl->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_CHANGE_TILE_LIMIT)
        );
        $select_input = new ilSelectInputGUI($this->plugin->txt('tiles_per_page'), 'tiles_per_page');
        $select_input->setOptions([4 => 4, 8 => 8, 12 => 12, 16 => 16]);
        $select_input->setValue($this->limit);
        $tpl->setVariable('LABEL_LIMIT_SELECTOR', $this->plugin->txt('tiles_per_page'));
        $tpl->setVariable('LIMIT_SELECTOR', $select_input->getToolbarHTML());

        return $tpl->get();
    }
}
