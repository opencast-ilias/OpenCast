<?php

use ILIAS\UI\Component\Component;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGrant;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageGroup;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageGroupRepository;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationSubUsageRepository;
use srag\Plugins\Opencast\Model\User\xoctUser;
use srag\Plugins\Opencast\UI\Modal\EventModals;
use srag\Plugins\Opencast\Model\DTO\DownloadDto;
use srag\Plugins\Opencast\LegacyHelpers\TranslatorTrait;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

/**
 * Class xoctEventRenderer
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctEventRenderer
{
    use TranslatorTrait;
    use LocaleTrait;

    public const LANG_MODULE = 'event';
    /**
     * @var ilOpenCastPlugin
     */
    protected $plugin;
    /**
     * @var OpencastDIC
     */
    protected $container;

    /**
     * @var Event
     */
    protected $event;
    /**
     * @var null | ObjectSettings
     */
    protected $objectSettings;
    /**
     * @var Factory
     */
    protected $factory;
    /**
     * @var Renderer
     */
    protected $renderer;
    /**
     * @var EventModals
     */
    protected static $modals;
    /**
     * @var array
     */
    private $dropdowns;
    /**
     * @var \ilCtrlInterface
     */
    private $ctrl;
    /**
     * @var \ILIAS\DI\UIServices
     */
    private $ui;
    /**
     * @var \ilObjUser
     */
    private $user;

    public function __construct(Event $event, ?ObjectSettings $objectSettings = null)
    {
        global $DIC;
        $ui = $DIC->ui();
        $this->ctrl = $DIC->ctrl();
        $this->ui = $DIC->ui();
        $this->user = $DIC->user();
        $this->container = OpencastDIC::getInstance();
        $this->plugin = $this->container->plugin();
        $this->event = $event;
        $this->objectSettings = $objectSettings;
        $this->factory = $ui->factory();
        $this->renderer = $ui->renderer();
        $this->dropdowns = [];
    }

    public static function initModals(EventModals $modals): void
    {
        self::$modals = $modals;
    }

    /**
     * @param        $tpl         ilTemplate
     * @param        $variable    string
     * @param        $value       string
     * @param string $block_title string
     */
    public function insert(&$tpl, $variable, $value, $block_title = ''): void
    {
        if ($block_title) {
            $tpl->setCurrentBlock($block_title);
        }

        $tpl->setVariable($variable, $value);

        if ($block_title) {
            $tpl->parseCurrentBlock();
        }
    }

    /**
     * Renders the dropdowns, in case the items to display is in a publication usage group.
     * If a group has only one item, it renders it as normal that is why tpl is passed as reference.
     * @param $tpl ilTemplate
     */
    public function renderDropdowns(&$tpl): void
    {
        $value = '';
        $sorted_list = [];
        if (!empty($this->dropdowns)) {
            $sorted_list = PublicationUsageGroupRepository::getSortedArrayList(array_keys($this->dropdowns));
        }
        foreach ($sorted_list as $group_id => $group_data) {
            $dropdown_contents = $this->dropdowns[$group_id];
            if (count($dropdown_contents) > 1) {
                $items = [];
                foreach ($dropdown_contents as $content) {
                    $items[] = $this->factory->link()->standard(
                        $content['display_name'],
                        $content['link']
                    );
                }
                $display_name = $this->getLocaleString(
                    strtolower($group_data['display_name']),
                    PublicationUsageGroup::DISPLAY_NAME_LANG_MODULE,
                    $group_data['display_name']
                );
                if (empty($display_name)) {
                    $display_name = $this->getLocaleString('default', PublicationUsageGroup::DISPLAY_NAME_LANG_MODULE);
                }
                $dropdown = $this->factory->dropdown()->standard(
                    $items
                )->withLabel($display_name);
                $value .= $this->renderer->renderAsync($dropdown);
            } else {
                $content = reset($dropdown_contents);
                $this->insert($tpl, $content['variable'], $content['html'], $content['block_title']);
                continue;
            }
        }

        if (!empty($value)) {
            $block_title_dpdn = 'dropdown';
            $variable_dpdb = 'DROPDOWN';
            $tpl->setCurrentBlock($block_title_dpdn);

            $tpl->setVariable($variable_dpdb, $value);

            $tpl->parseCurrentBlock();
        }
    }


    /**
     * @param        $tpl ilTemplate
     * @param string $block_title
     * @param string $variable
     *
     * @throws xoctException
     */
    public function insertPreviewImage(&$tpl, $block_title = 'preview_image', $variable = 'PREVIEW_IMAGE'): void
    {
        $this->insert($tpl, $variable, $this->getPreviewImageHTML(), $block_title);
    }

    /**
     * @return string
     * @throws xoctException
     */
    public function getPreviewImageHTML()
    {
        $preview_image_tpl = $this->plugin->getTemplate('default/tpl.event_preview_image.html');
        $preview_image_tpl->setVariable('ID', $this->event->getIdentifier());
        $preview_image_tpl->setVariable('THUMBNAIL', $this->getThumbnailHTML());
        return $preview_image_tpl->get();
    }

    public function getPreviewLink(): string
    {
        return 'data-preview_link="' . $this->event->getIdentifier() . '"';
    }

    /**
     * @return string
     * @throws xoctException
     */
    public function getThumbnailHTML()
    {
        return $this->renderer->render(
            $this->factory->image()->responsive($this->event->publications()->getThumbnailUrl(), 'Thumbnail')
        );
    }

    /**
     * @param        $tpl ilTemplate
     * @param string $block_title
     * @param string $variable
     * @param string $button_type
     *
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    public function insertPlayerLink(&$tpl, $block_title = 'link', $variable = 'LINK', $button_type = 'btn-info'): void
    {
        if ($player_link_html = $this->getPlayerLinkHTML($button_type)) {
            $this->insert($tpl, $variable, $player_link_html, $block_title);
        }
    }

    /**
     * @param string $button_type
     *
     * @return string
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    public function getPlayerLinkHTML($button_type = 'btn-info')
    {
        if ($this->isEventAccessible() && (!is_null($this->event->publications()->getPlayerPublication()) || !is_null(
            $this->event->publications()->getLivePublication()
        ))) {
            $link_tpl = $this->plugin->getTemplate('default/tpl.player_link.html');
            $link_tpl->setVariable(
                'LINK_TEXT',
                $this->plugin->txt(
                    $this->event->isLiveEvent() ? self::LANG_MODULE . '_player_live' : self::LANG_MODULE . '_player'
                )
            );
            $link_tpl->setVariable('BUTTON_TYPE', $button_type);
            $link_tpl->setVariable('PREVIEW_LINK', $this->getPreviewLink());
            $link_tpl->setVariable('TARGET', '_blank');
            if (PluginConfig::getConfig(PluginConfig::F_USE_MODALS)) {
                $modal = $this->getPlayerModal();
                $link_tpl->setVariable('LINK_URL', '#');
                $link_tpl->setVariable('MODAL', $modal->getHTML());
                $link_tpl->setVariable('MODAL_LINK', $this->getModalLink());
            } else {
                $link_tpl->setVariable('LINK_URL', $this->getInternalPlayerLink());
            }

            return $link_tpl->get();
        } else {
            return '';
        }
    }

    public function getInternalPlayerLink(): string
    {
        $this->ctrl->clearParametersByClass(xoctEventGUI::class);
        $this->ctrl->setParameterByClass(xoctEventGUI::class, xoctEventGUI::IDENTIFIER, $this->event->getIdentifier());
        return $this->ctrl->getLinkTargetByClass(
            [
                ilRepositoryGUI::class,
                ilObjOpenCastGUI::class,
                xoctEventGUI::class,
                xoctPlayerGUI::class
            ],
            xoctPlayerGUI::CMD_STREAM_VIDEO
        );
    }

    /**
     * @return ilModalGUI
     * @throws xoctException
     */
    public function getPlayerModal()
    {
        $modal = ilModalGUI::getInstance();
        $modal->setId('modal_' . $this->event->getIdentifier());
        $modal->setHeading($this->event->getTitle());
        $modal->setBody(
            '<iframe class="xoct_iframe" allowfullscreen="true" src="' . $this->getInternalPlayerLink(
            ) . '" style="border:none;"></iframe><br>'
        );
        return $modal;
    }

    public function getModalLink(): string
    {
        return 'data-toggle="modal" data-target="#modal_' . $this->event->getIdentifier() . '"';
    }

    /**
     * @param        $tpl ilTemplate
     * @param string $block_title
     * @param string $variable
     * @param string $button_type
     *
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    public function insertDownloadLink(&$tpl, $block_title = 'link', $variable = 'LINK', $button_type = 'btn-info'): void
    {
        $publication_repository = new PublicationUsageRepository();
        $publication_sub_repository = new PublicationSubUsageRepository();
        $categorized_download_dtos = $this->event->publications()->getDownloadDtos(false);
        foreach ($categorized_download_dtos as $usage_type => $content) {
            foreach ($content as $usage_id => $download_dtos) {
                $download_pub_usage = null;
                $display_name = '';
                if ($usage_type == PublicationUsage::USAGE_TYPE_ORG) {
                    $download_pub_usage = $publication_repository->getUsage($usage_id);
                    $display_name = $publication_repository->getDisplayName($usage_id);
                } else {
                    $download_pub_usage = $publication_sub_repository->convertSingleSubToUsage($usage_id);
                    $display_name = $publication_sub_repository->getDisplayName($usage_id);
                }

                if (is_null($download_pub_usage)) {
                    continue;
                }

                if (empty($display_name)) {
                    $display_name = $this->translate('download', self::LANG_MODULE);
                }

                $download_html = $this->getDownloadLinkHTML($download_pub_usage, $download_dtos, $display_name, $button_type);
                if (!empty($download_html)) {
                    $group_id = $download_pub_usage->getGroupId();
                    if (!is_null($group_id) && !$download_pub_usage->isAllowMultiple()) {
                        $this->dropdowns[$group_id][] = [
                            'variable' => $variable,
                            'display_name' => $display_name,
                            'link' => $this->ctrl->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_DOWNLOAD),
                            'html' => $download_html,
                            'block_title' => $block_title,
                        ];
                    } else {
                        $this->insert($tpl, $variable, $download_html, $block_title);
                    }
                }
            }
        }
    }

    /**
     * @param PublicationUsage $download_publication_usage
     * @param DownloadDto[] $download_dtos
     * @param string $display_name
     * @param string $button_type
     *
     * @return string
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    public function getDownloadLinkHTML(
        $download_publication_usage,
        $download_dtos,
        $display_name,
        $button_type = 'btn_info'
    ): string {
        $html = '';
        $ignore_object_settings = $download_publication_usage->ignoreObjectSettings();
        $has_streaming_only = $this->objectSettings instanceof ObjectSettings && $this->objectSettings->getStreamingOnly();
        $show_download = true;
        if ($has_streaming_only && $ignore_object_settings == false) {
            $show_download = false;
        }
        if (($this->event->getProcessingState() == Event::STATE_SUCCEEDED) && (count($download_dtos) > 0)) {
            if (!$show_download) {
                return '';
            }

            // Setting event_id is necessary, because we use it for both multi approach with pub_id or subusage approach with usage_type and usage_id.
            $this->ctrl->setParameterByClass(xoctEventGUI::class, 'event_id', $this->event->getIdentifier());

            // Setting the floowing parameters to null first, so that we get accurate parameters later on in download action.
            $this->ctrl->setParameterByClass(xoctEventGUI::class, 'pub_id', null);
            $this->ctrl->setParameterByClass(xoctEventGUI::class, 'usage_type', null);
            $this->ctrl->setParameterByClass(xoctEventGUI::class, 'usage_id', null);

            $multi = $download_publication_usage->isAllowMultiple();
            if ($multi) {
                $items = array_map(function ($dto): \ILIAS\UI\Component\Link\Standard {
                    $this->ctrl->setParameterByClass(xoctEventGUI::class, 'pub_id', $dto->getPublicationId());
                    return $this->factory->link()->standard(
                        $dto->getResolution(),
                        $this->ctrl->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_DOWNLOAD)
                    );
                }, $download_dtos);
                $dropdown = $this->factory->dropdown()->standard(
                    $items
                )->withLabel($display_name);
                $html = $this->renderer->renderAsync($dropdown);
            } else {
                $usage_type = $download_publication_usage->isSub() ? 'sub' : 'org';
                $this->ctrl->setParameterByClass(xoctEventGUI::class, 'usage_type', $usage_type);
                $usage_id = $usage_type === 'sub' ? $download_publication_usage->getSubId() :
                    $download_publication_usage->getUsageId();
                $this->ctrl->setParameterByClass(xoctEventGUI::class, 'usage_id', $usage_id);
                $link = $this->ctrl->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_DOWNLOAD);
                $link_tpl = $this->plugin->getTemplate('default/tpl.player_link.html');
                $link_tpl->setVariable('TARGET', '_self');
                $link_tpl->setVariable('BUTTON_TYPE', $button_type);
                $link_tpl->setVariable('LINK_TEXT', $display_name);
                $link_tpl->setVariable('LINK_URL', $link);

                $html = $link_tpl->get();
            }
        }
        return $html;
    }

    /**
     * @param        $tpl ilTemplate
     * @param string $block_title
     * @param string $variable
     * @param string $button_type
     *
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    public function insertAnnotationLink(&$tpl, $block_title = 'link', $variable = 'LINK', $button_type = 'btn-info'): void
    {
        list($display_name, $annotation_link_html) = $this->getAnnotationLinkHTML($button_type);
        if (!empty($annotation_link_html)) {
            $annotatePublicationUsage = (new PublicationUsageRepository())->getUsage(PublicationUsage::USAGE_ANNOTATE);
            $group_id = $annotatePublicationUsage->getGroupId();
            if (!is_null($group_id)) {
                $this->dropdowns[$group_id][] = [
                    'variable' => $variable,
                    'display_name' => $display_name,
                    'link' => $this->ctrl->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_ANNOTATE),
                    'block_title' => $block_title,
                ];
                return;
            }
            $this->insert($tpl, $variable, $annotation_link_html, $block_title);
        }
    }

    /**
     * @param string $button_type
     *
     * @return array
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    public function getAnnotationLinkHTML($button_type = 'btn_info'): array
    {
        $display_name = (new PublicationUsageRepository())->getDisplayName(PublicationUsage::USAGE_ANNOTATE);
        if (empty($display_name)) {
            $display_name = $this->translate('annotate', self::LANG_MODULE);
        }
        $html = '';
        if (($this->event->getProcessingState() == Event::STATE_SUCCEEDED)
            && ($this->event->publications()->getAnnotationPublication())) {
            $this->ctrl->setParameterByClass(
                xoctEventGUI::class,
                xoctEventGUI::IDENTIFIER,
                $this->event->getIdentifier()
            );
            $annotations_link = $this->ctrl->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_ANNOTATE);
            $link_tpl = $this->plugin->getTemplate('default/tpl.player_link.html');
            $link_tpl->setVariable('TARGET', '_blank');
            $link_tpl->setVariable('BUTTON_TYPE', $button_type);
            $link_tpl->setVariable('LINK_TEXT', $display_name);
            $link_tpl->setVariable('LINK_URL', $annotations_link);

            $html = $link_tpl->get();
        }
        return [$display_name, $html];
    }

    /**
     * @param        $tpl ilTemplate
     * @param string $block_title
     * @param string $variable
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    public function insertTitle(&$tpl, $block_title = 'title', $variable = 'TITLE'): void
    {
        $this->insert($tpl, $variable, $this->getTitleHTML(), $block_title);
    }

    /**
     * @param        $tpl ilTemplate
     * @param string $block_title
     * @param string $variable
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    public function insertDescription(&$tpl, $block_title = 'description', $variable = 'DESCRIPTION'): void
    {
        $this->insert($tpl, $variable, $this->getDescriptionHTML(), $block_title);
    }

    public function getTitleHTML(): string
    {
        return $this->event->getTitle();
    }

    public function getDescriptionHTML(): string
    {
        return $this->event->getDescription();
    }

    /**
     * @param        $tpl
     * @param string $block_title
     * @param string $variable
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    public function insertState(&$tpl, $block_title = 'state', $variable = 'STATE'): void
    {
        if ($state_html = $this->getStateHTML()) {
            $this->insert($tpl, $variable, $state_html, $block_title);
        }
    }

    /**
     * @return string
     * @throws DICException
     * @throws ilTemplateException
     * @throws xoctException
     */
    public function getStateHTML()
    {
        if (!$this->isEventAccessible()) {
            $processing_state = $this->event->getProcessingState();
            $state_tpl = $this->plugin->getTemplate('default/tpl.event_state.html');
            $state_tpl->setVariable('STATE_CSS', Event::$state_mapping[$processing_state]);

            $suffix = '';
            if ($this->container->acl_utils()->isUserOwnerOfEvent(xoctUser::getInstance($this->user), $this->event)
                && in_array($processing_state, [
                    Event::STATE_FAILED,
                    Event::STATE_ENCODING
                ])) {
                $suffix = '_owner';
            }

            $placeholders = [];
            if ($processing_state == Event::STATE_LIVE_SCHEDULED) {
                $placeholders[] = date(
                    'd.m.Y, H:i',
                    $this->event->getScheduling()->getStart()->getTimestamp() - (((int) PluginConfig::getConfig(
                        PluginConfig::F_START_X_MINUTES_BEFORE_LIVE
                    )) * 60)
                );
            }

            $state_tpl->setVariable(
                'STATE',
                $this->translate(
                    'state_' . strtolower($processing_state) . $suffix,
                    self::LANG_MODULE,
                    $placeholders
                )
            );

            return $state_tpl->get();
        } else {
            return '';
        }
    }

    /**
     * @param        $tpl ilTemplate
     * @param string $block_title
     * @param string $variable
     */
    public function insertPresenter(&$tpl, $block_title = 'presenter', $variable = 'PRESENTER'): void
    {
        $this->insert($tpl, $variable, $this->getPresenterHTML(), $block_title);
    }

    /**
     * @return String
     */
    public function getPresenterHTML()
    {
        return $this->event->getPresenter() ?: '&nbsp';
    }

    /**
     * @param        $tpl ilTemplate
     * @param string $block_title
     * @param string $variable
     */
    public function insertLocation(&$tpl, $block_title = 'location', $variable = 'LOCATION'): void
    {
        $this->insert($tpl, $variable, $this->getLocationHTML(), $block_title);
    }

    public function getLocationHTML(): string
    {
        return $this->event->getLocation();
    }

    /**
     * @param        $tpl ilTemplate
     * @param string $block_title
     * @param string $variable
     * @param string $format
     */
    public function insertStart(&$tpl, $block_title = 'start', $variable = 'START', $format = 'd.m.Y - H:i'): void
    {
        $this->insert($tpl, $variable, $this->getStartHTML($format), $block_title);
    }

    /**
     * @param string $format
     */
    public function getStartHTML($format = 'd.m.Y - H:i'): string
    {
        return $this->event->getStart()->setTimezone(new DateTimeZone(ilTimeZone::_getDefaultTimeZone()))->format(
            $format
        );
    }

    public function insertUnprotectedLink(
        ilTemplate &$tpl,
        string $block_title = 'unprotected_link',
        string $variable = 'UNPROTECTED_LINK'
    ): void {
        $link_tpl = $this->plugin->getTemplate('default/tpl.event_link.html');
        $link = $this->event->publications()->getUnprotectedLink() ?: '';
        $link_tpl->setVariable('URL', $link);
        $link_tpl->setVariable('TOOLTIP_TEXT', $this->plugin->txt('tooltip_copy_link'));
        $this->insert($tpl, $variable, $link ? $link_tpl->get() : '', $block_title);
    }

    /**
     * @param        $tpl ilTemplate
     * @param string $block_title
     * @param string $variable
     * @throws DICException
     * @throws ilTemplateException
     */
    public function insertOwner(&$tpl, $block_title = 'owner', $variable = 'OWNER', string $username = null): void
    {
        $this->insert($tpl, $variable, $this->getOwnerHTML($username), $block_title);
    }

    /**
     * @throws DICException
     * @throws ilTemplateException
     */
    public function getOwnerHTML(string $owner_username = null): string
    {
        $owner_tpl = $this->plugin->getTemplate('default/tpl.event_owner.html');
        if ($owner_username === null) {
            $owner_username = $this->container->acl_utils()->getOwnerUsernameOfEvent($this->event);
        }
        $owner_tpl->setVariable('OWNER', $owner_username);

        if ($this->objectSettings instanceof ObjectSettings && $this->objectSettings->getPermissionPerClip()) {
            $owner_tpl->setCurrentBlock('invitations');
            $in = PermissionGrant::getActiveInvitationsForEvent($this->event, $this->objectSettings, true);
            if ($in > 0) {
                $owner_tpl->setVariable('INVITATIONS', $in);
            }
            $owner_tpl->parseCurrentBlock();
        }

        return $owner_tpl->get();
    }

    protected function isEventAccessible(): bool
    {
        $processing_state = $this->event->getProcessingState();

        if ($processing_state == Event::STATE_SUCCEEDED) {
            return true;
        }

        if ($this->event->isLiveEvent()) {
            if ($processing_state == Event::STATE_LIVE_RUNNING) {
                return true;
            }
            if ($processing_state == Event::STATE_LIVE_SCHEDULED) {
                $start = $this->event->getScheduling()->getStart()->getTimestamp();
                $accessible_before_start = ((int) PluginConfig::getConfig(
                    PluginConfig::F_START_X_MINUTES_BEFORE_LIVE
                )) * 60;
                $accessible_from = $start - $accessible_before_start;
                $accessible_to = $this->event->getScheduling()->getEnd()->getTimestamp();
                return ($accessible_from < time()) && ($accessible_to > time());
            }
        }

        return false;
    }

    /**
     * @return Component[]
     * @throws DICException
     */
    public function getActions(): array
    {
        if (!in_array($this->event->getProcessingState(), [
            Event::STATE_SUCCEEDED,
            Event::STATE_NOT_PUBLISHED,
            Event::STATE_READY_FOR_CUTTING,
            Event::STATE_OFFLINE,
            Event::STATE_FAILED,
            Event::STATE_SCHEDULED,
            Event::STATE_SCHEDULED_OFFLINE,
            Event::STATE_LIVE_RUNNING,
            Event::STATE_LIVE_SCHEDULED,
            Event::STATE_LIVE_OFFLINE,
        ])) {
            return [];
        }
        /**
         * @var $xoctUser xoctUser
         */
        $xoctUser = xoctUser::getInstance($this->user);

        $this->ctrl->setParameterByClass(
            xoctEventGUI::class,
            xoctEventGUI::IDENTIFIER,
            $this->event->getIdentifier()
        );
        $this->ctrl->setParameterByClass(
            xoctGrantPermissionGUI::class,
            xoctEventGUI::IDENTIFIER,
            $this->event->getIdentifier()
        );
        $this->ctrl->setParameterByClass(
            xoctChangeOwnerGUI::class,
            xoctEventGUI::IDENTIFIER,
            $this->event->getIdentifier()
        );

        $actions = [];

        if (ilObjOpenCast::DEV) {
            $actions[] = $this->factory->link()->standard(
                $this->plugin->txt('event_view'),
                $this->ctrl->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_VIEW)
            );
        }

        // Edit Owner
        if (ilObjOpenCastAccess::checkAction(
            ilObjOpenCastAccess::ACTION_EDIT_OWNER,
            $this->event,
            $xoctUser,
            $this->objectSettings
        )) {
            $actions[] = $this->factory->link()->standard(
                $this->plugin->txt('event_edit_owner'),
                $this->ctrl->getLinkTargetByClass(xoctChangeOwnerGUI::class, xoctChangeOwnerGUI::CMD_STANDARD)
            );
        }

        // Share event
        if (ilObjOpenCastAccess::checkAction(
            ilObjOpenCastAccess::ACTION_SHARE_EVENT,
            $this->event,
            $xoctUser,
            $this->objectSettings
        )) {
            $actions[] = $this->factory->link()->standard(
                $this->plugin->txt('event_invite_others'),
                $this->ctrl->getLinkTargetByClass(xoctGrantPermissionGUI::class, xoctGrantPermissionGUI::CMD_STANDARD)
            );
        }

        // Cut Event
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_CUT, $this->event, $xoctUser)) {
            $actions[] = $this->factory->link()->standard(
                $this->plugin->txt('event_cut'),
                $this->ctrl->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_CUT)
            )->withOpenInNewViewport(true);
        }

        // Republish
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $this->event, $xoctUser)
            && !$this->event->isScheduled() && !is_null(self::$modals) && !is_null(self::$modals->getRepublishModal())
        ) {
            $actions[] = $this->factory->button()->shy(
                $this->plugin->txt('event_republish'),
                self::$modals->getRepublishModal()->getShowSignal()
            )->withOnLoadCode(function ($id) {
                return "$({$id}).on('click'," .
                        "function(event){ $('input#republish_event_id').val('{$this->event->getIdentifier()}'); });";
            });
        }

        // Online/offline
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_SET_ONLINE_OFFLINE, $this->event, $xoctUser)) {
            if ($this->event->getXoctEventAdditions()->getIsOnline()) {
                $actions[] = $this->factory->link()->standard(
                    $this->plugin->txt('event_set_offline'),
                    $this->ctrl->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_SET_OFFLINE)
                );
            } else {
                $actions[] = $this->factory->link()->standard(
                    $this->plugin->txt('event_set_online'),
                    $this->ctrl->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_SET_ONLINE)
                );
            }
        }

        // Delete Event
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_DELETE_EVENT, $this->event, $xoctUser)) {
            $actions[] = $this->factory->link()->standard(
                $this->plugin->txt('event_delete'),
                $this->ctrl->getLinkTargetByClass(xoctEventGUI::class, xoctEventGUI::CMD_CONFIRM)
            );
        }

        // Edit Event
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_EVENT, $this->event, $xoctUser)) {
            // show different langvar when date is editable
            $lang_var = ($this->event->isScheduled()
                && (PluginConfig::getConfig(
                    PluginConfig::F_SCHEDULED_METADATA_EDITABLE
                ) == PluginConfig::ALL_METADATA)) ?
                'event_edit_date' : 'event_edit';
            $actions[] = $this->factory->link()->standard(
                $this->plugin->txt($lang_var),
                $this->ctrl->getLinkTargetByClass(
                    xoctEventGUI::class,
                    $this->event->isScheduled() ? xoctEventGUI::CMD_EDIT_SCHEDULED : xoctEventGUI::CMD_EDIT
                )
            );
        }

        // Report Quality
        if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_REPORT_QUALITY_PROBLEM, $this->event)
            && !is_null(self::$modals) && !is_null(self::$modals->getReportQualityModal())
        ) {
            $actions[] = $this->factory->button()->shy(
                $this->plugin->txt('event_report_quality_problem'),
                self::$modals->getReportQualityModal()->getShowSignal()
            )->withOnLoadCode(function ($id) {
                return "$({$id}).on('click', function(event){ " .
                    "$('input#xoct_report_quality_event_id').val('{$this->event->getIdentifier()}');" .
                    "$('#xoct_report_quality_modal textarea#message').focus(); });";
            });
        }

        return $actions;
    }
}
