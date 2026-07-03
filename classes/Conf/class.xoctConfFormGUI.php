<?php

declare(strict_types=1);

use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\User\xoctUser;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;
use srag\Plugins\Opencast\Container\Init;

/**
 * Class xoctConfFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctConfFormGUI extends ilPropertyFormGUI
{
    use LocaleTrait {
        LocaleTrait::getLocaleString as _getLocaleString;
    }

    public function getLocaleString(string $string, ?string $module = '', ?string $fallback = null): string
    {
        return $this->_getLocaleString($string, empty($module) ? 'config' : $module, $fallback);
    }

    protected PluginConfig $object;
    protected ilOpenCastPlugin $plugin;
    protected \ilGlobalTemplateInterface $main_tpl;

    public function __construct(
        protected xoctConfGUI $parent_gui,
        protected string $subtab_active
    ) {
        global $DIC;
        $container = Init::init($DIC);
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->plugin = $container->plugin();
        $this->main_tpl->addJavaScript($this->plugin->getRelativeDirectory() . '/js/opencast/dist/index.js');
        $this->main_tpl->addCss($this->plugin->getStyleSheetLocation('default/password_toggle.css'));
        parent::__construct();
        $this->initForm();
    }

    protected function initForm(): void
    {
        $this->setTarget('_top');
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
        $this->initButtons();

        switch ($this->subtab_active) {
            case xoctMainGUI::SUBTAB_API:
                $this->initAPISection();
                break;
            case xoctMainGUI::SUBTAB_EVENTS:
                $this->initEventsSection();
                break;
            case xoctMainGUI::SUBTAB_TOU:
                $this->initToUSection();
                break;
            case xoctMainGUI::SUBTAB_GROUPS_ROLES:
                $this->initGroupsRolesSection();
                break;
            case xoctMainGUI::SUBTAB_SECURITY:
                $this->initSecuritySection();
                break;
            case xoctMainGUI::SUBTAB_ADVANCED:
                $this->initAdvancedSection();
                break;
        }
    }

    protected function initButtons(): void
    {
        $this->addCommandButton(xoctGUI::CMD_UPDATE, $this->getLocaleString(xoctGUI::CMD_UPDATE));
    }

    public function fillForm(): void
    {
        $array = [];
        foreach ($this->getItems() as $item) {
            $this->getValuesForItem($item, $array);
        }
        $this->setValuesByArray($array);
    }

    /**
     * @param $item
     * @param $array
     *
     * @internal param $key
     */
    private function getValuesForItem($item, array &$array): void
    {
        if (self::checkItem($item)) {
            $key = $item->getPostVar();
            $array[$key] = PluginConfig::getConfig($key);
            if (self::checkForSubItem($item)) {
                foreach ($item->getSubItems() as $subitem) {
                    $this->getValuesForItem($subitem, $array);
                }
            }
        }
    }

    public function saveObject(): bool
    {
        if (!$this->checkInput()) {
            return false;
        }
        foreach ($this->getItems() as $item) {
            $this->saveValueForItem($item);
        }
        PluginConfig::set(PluginConfig::F_CONFIG_VERSION, PluginConfig::CONFIG_VERSION);

        return true;
    }

    /**
     * @param $item
     */
    private function saveValueForItem($item): void
    {
        if (self::checkItem($item)) {
            $key = $item->getPostVar();
            PluginConfig::set($key, $this->getInput($key));
            if (self::checkForSubItem($item)) {
                foreach ($item->getSubItems() as $subitem) {
                    $this->saveValueForItem($subitem);
                }
            }
        }
    }

    public static function checkForSubItem($item): bool
    {
        return !$item instanceof ilFormSectionHeaderGUI && !$item instanceof ilMultiSelectInputGUI;
    }

    /**
     * @param $item
     */
    public static function checkItem($item): bool
    {
        return !$item instanceof ilFormSectionHeaderGUI;
    }

    protected function initAPISection(): void
    {
        $code = "il.Opencast.Form.passwordToggle.init('" . PluginConfig::F_CURL_PASSWORD . "');";
        $this->main_tpl->addOnLoadCode($code);

        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->getLocaleString('curl'));
        $this->addItem($h);

        $te = new ilTextInputGUI($this->getLocaleString(PluginConfig::F_API_VERSION), PluginConfig::F_API_VERSION);
        $te->setInfo($this->getLocaleString(PluginConfig::F_API_VERSION . '_info'));
        $te->setRequired(true);
        $this->addItem($te);

        $te = new ilTextInputGUI($this->getLocaleString(PluginConfig::F_API_BASE), PluginConfig::F_API_BASE);
        $te->setInfo($this->getLocaleString(PluginConfig::F_API_BASE . '_info'));
        $te->setRequired(true);
        $this->addItem($te);

        $te = new ilTextInputGUI($this->getLocaleString(PluginConfig::F_CURL_USERNAME), PluginConfig::F_CURL_USERNAME);
        $te->setInfo($this->getLocaleString(PluginConfig::F_CURL_USERNAME . '_info'));
        $te->setRequired(true);
        $this->addItem($te);

        $te = new ilTextInputGUI($this->getLocaleString(PluginConfig::F_CURL_PASSWORD), PluginConfig::F_CURL_PASSWORD);
        $te->setInfo($this->getLocaleString(PluginConfig::F_CURL_PASSWORD . '_info'));
        $te->setRequired(true);
        $this->addItem($te);
    }

    protected function initEventsSection(): void
    {
        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->getLocaleString('events'));
        $this->addItem($h);

        $te = new ilTextInputGUI($this->getLocaleString(PluginConfig::F_WORKFLOW), PluginConfig::F_WORKFLOW);
        $te->setInfo($this->getLocaleString(PluginConfig::F_WORKFLOW . '_info'));
        $te->setRequired(true);
        $this->addItem($te);

        $te = new ilNumberInputGUI(
            $this->getLocaleString(PluginConfig::F_CURL_MAX_UPLOADSIZE),
            PluginConfig::F_CURL_MAX_UPLOADSIZE
        );
        $te->setInfo($this->getLocaleString(PluginConfig::F_CURL_MAX_UPLOADSIZE . '_info'));
        $te->setRequired(true);
        $this->addItem($te);

        $te = new ilTextInputGUI(
            $this->getLocaleString(PluginConfig::F_WORKFLOW_UNPUBLISH),
            PluginConfig::F_WORKFLOW_UNPUBLISH
        );
        $te->setInfo($this->getLocaleString(PluginConfig::F_WORKFLOW_UNPUBLISH . '_info'));
        $this->addItem($te);

        $te = new ilTextInputGUI($this->getLocaleString(PluginConfig::F_EDITOR_LINK), PluginConfig::F_EDITOR_LINK);
        $te->setInfo($this->getLocaleString(PluginConfig::F_EDITOR_LINK . '_info'));
        $this->addItem($te);

        $te = new ilTextInputGUI(
            $this->getLocaleString(PluginConfig::F_SCHEDULE_CHANNEL),
            PluginConfig::F_SCHEDULE_CHANNEL
        );
        $te->setInfo($this->getLocaleString(PluginConfig::F_SCHEDULE_CHANNEL . '_info'));
        $te->setMulti(true);
        $this->addItem($te);

        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_CREATE_SCHEDULED_ALLOWED),
            PluginConfig::F_CREATE_SCHEDULED_ALLOWED
        );
        $cb->setInfo($this->getLocaleString(PluginConfig::F_CREATE_SCHEDULED_ALLOWED . '_info'));
        $this->addItem($cb);

        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_STUDIO_ALLOWED),
            PluginConfig::F_STUDIO_ALLOWED
        );
        $cb->setInfo($this->getLocaleString(PluginConfig::F_STUDIO_ALLOWED . '_info'));
        $this->addItem($cb);

        // Studio Link.
        $te = new ilTextInputGUI($this->getLocaleString(PluginConfig::F_STUDIO_URL), PluginConfig::F_STUDIO_URL);
        $te->setInfo($this->getLocaleString(PluginConfig::F_STUDIO_URL . '_info'));
        $cb->addSubItem($te);

        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_AUDIO_ALLOWED),
            PluginConfig::F_AUDIO_ALLOWED
        );
        $cb->setInfo($this->getLocaleString(PluginConfig::F_AUDIO_ALLOWED . '_info'));
        $this->addItem($cb);

        // INTERNAL VIDEO PLAYER
        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_INTERNAL_VIDEO_PLAYER),
            PluginConfig::F_INTERNAL_VIDEO_PLAYER
        );
        $cb->setInfo($this->getLocaleString(PluginConfig::F_INTERNAL_VIDEO_PLAYER . '_info'));
        $this->addItem($cb);

        $cbs = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_USE_GENERATED_STREAMING_URLS),
            PluginConfig::F_USE_GENERATED_STREAMING_URLS
        );
        $cbs->setInfo($this->getLocaleString(PluginConfig::F_USE_GENERATED_STREAMING_URLS . '_info'));
        $cbs->setRequired(false);
        $cb->addSubItem($cbs);

        $te = new ilTextInputGUI($this->getLocaleString(PluginConfig::F_STREAMING_URL), PluginConfig::F_STREAMING_URL);
        $te->setInfo($this->getLocaleString(PluginConfig::F_STREAMING_URL . '_info'));
        $te->setRequired(true);
        $cbs->addSubItem($te);

        $cbs = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_USE_HIGH_LOW_RES_SEGMENT_PREVIEWS),
            PluginConfig::F_USE_HIGH_LOW_RES_SEGMENT_PREVIEWS
        );
        $cbs->setInfo($this->getLocaleString(PluginConfig::F_USE_HIGH_LOW_RES_SEGMENT_PREVIEWS . '_info'));
        $cbs->setRequired(false);
        $cb->addSubItem($cbs);

        // LIVE STREAMS
        $cbs = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_ENABLE_LIVE_STREAMS),
            PluginConfig::F_ENABLE_LIVE_STREAMS
        );
        $cbs->setInfo($this->getLocaleString(PluginConfig::F_ENABLE_LIVE_STREAMS . '_info'));
        $cbs->setRequired(false);
        $this->addItem($cbs);

        $te = new ilTextInputGUI(
            $this->getLocaleString(PluginConfig::F_PRESENTATION_NODE),
            PluginConfig::F_PRESENTATION_NODE
        );
        $te->setInfo($this->getLocaleString(PluginConfig::F_PRESENTATION_NODE . '_info'));
        $te->setRequired(true);
        $cbs->addSubItem($te);

        $te = new ilSelectInputGUI(
            $this->getLocaleString(PluginConfig::F_LIVESTREAM_TYPE),
            PluginConfig::F_LIVESTREAM_TYPE
        );
        $te->setInfo($this->getLocaleString(PluginConfig::F_LIVESTREAM_TYPE . '_info'));
        $te->setOptions([
            'hls' => $this->getLocaleString(PluginConfig::F_LIVESTREAM_TYPE . '_hls'),
            'mpegts' => $this->getLocaleString(PluginConfig::F_LIVESTREAM_TYPE . '_mpegts'),
        ]);
        $te->setRequired(true);
        $cbs->addSubItem($te);

        $cbs2 = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_LIVESTREAM_BUFFERED),
            PluginConfig::F_LIVESTREAM_BUFFERED
        );
        $cbs2->setInfo($this->getLocaleString(PluginConfig::F_LIVESTREAM_BUFFERED . '_info'));
        $cbs->addSubItem($cbs2);

        $ni = new ilNumberInputGUI(
            $this->getLocaleString(PluginConfig::F_START_X_MINUTES_BEFORE_LIVE),
            PluginConfig::F_START_X_MINUTES_BEFORE_LIVE
        );
        $ni->setInfo($this->getLocaleString(PluginConfig::F_START_X_MINUTES_BEFORE_LIVE . '_info'));
        $cbs->addSubItem($ni);

        $cbs2 = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_ENABLE_CHAT),
            PluginConfig::F_ENABLE_CHAT
        );
        $cbs2->setInfo($this->getLocaleString(PluginConfig::F_ENABLE_CHAT . '_info'));
        $cbs2->setRequired(false);
        $cbs->addSubItem($cbs2);

        // ENABLE CUTTING
        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_ENABLE_CUTTING),
            PluginConfig::F_ENABLE_CUTTING
        );
        $cb->setInfo($this->getLocaleString(PluginConfig::F_ENABLE_CUTTING . '_info'));
        $this->addItem($cb);

        // MODALS
        $cb = new ilCheckboxInputGUI($this->getLocaleString(PluginConfig::F_USE_MODALS), PluginConfig::F_USE_MODALS);
        $cb->setInfo($this->getLocaleString(PluginConfig::F_USE_MODALS . '_info'));
        $this->addItem($cb);

        // QUALITY REPORT
        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_REPORT_QUALITY),
            PluginConfig::F_REPORT_QUALITY
        );
        $cb->setInfo($this->getLocaleString(PluginConfig::F_REPORT_QUALITY . '_info'));
        $this->addItem($cb);

        $te = new ilTextInputGUI(
            $this->getLocaleString(PluginConfig::F_REPORT_QUALITY_EMAIL),
            PluginConfig::F_REPORT_QUALITY_EMAIL
        );
        $te->setInfo($this->getLocaleString(PluginConfig::F_REPORT_QUALITY_EMAIL . '_info'));
        $te->setRequired(true);
        $cb->addSubItem($te);

        $te = new ilTextAreaInputGUI(
            $this->getLocaleString(PluginConfig::F_REPORT_QUALITY_TEXT),
            PluginConfig::F_REPORT_QUALITY_TEXT
        );
        $te->setInfo($this->getLocaleString(PluginConfig::F_REPORT_QUALITY_TEXT . '_info'));
        $te->setRequired(true);
        $te->setRows(8);
        $te->setUseRte(true);
        $te->setRteTagSet("extended");
        $te->disableButtons([
            'charmap',
            'undo',
            'redo',
            'justifyleft',
            'justifycenter',
            'justifyright',
            'justifyfull',
            'anchor',
            'fullscreen',
            'cut',
            'copy',
            'paste',
            'pastetext',
            'formatselect',
        ]);
        $cb->addSubItem($te);

        $ri = new ilRadioGroupInputGUI(
            $this->getLocaleString(PluginConfig::F_REPORT_QUALITY_ACCESS),
            PluginConfig::F_REPORT_QUALITY_ACCESS
        );
        $ro = new ilRadioOption(
            $this->getLocaleString(PluginConfig::F_REPORT_QUALITY_ACCESS . '_' . PluginConfig::ACCESS_ALL),
            (string) PluginConfig::ACCESS_ALL
        );
        $ri->addOption($ro);
        $ro = new ilRadioOption(
            $this->getLocaleString(PluginConfig::F_REPORT_QUALITY_ACCESS . '_' . PluginConfig::ACCESS_OWNER_ADMIN),
            (string) PluginConfig::ACCESS_OWNER_ADMIN
        );
        $ri->addOption($ro);
        $ri->setRequired(true);
        $cb->addSubItem($ri);

        // DATE REPORT
        $cb = new ilCheckboxInputGUI($this->getLocaleString(PluginConfig::F_REPORT_DATE), PluginConfig::F_REPORT_DATE);
        $cb->setInfo($this->getLocaleString(PluginConfig::F_REPORT_DATE . '_info'));
        $this->addItem($cb);

        $te = new ilTextInputGUI(
            $this->getLocaleString(PluginConfig::F_REPORT_DATE_EMAIL),
            PluginConfig::F_REPORT_DATE_EMAIL
        );
        $te->setInfo($this->getLocaleString(PluginConfig::F_REPORT_DATE_EMAIL . '_info'));
        $te->setRequired(true);
        $cb->addSubItem($te);

        $te = new ilTextAreaInputGUI(
            $this->getLocaleString(PluginConfig::F_REPORT_DATE_TEXT),
            PluginConfig::F_REPORT_DATE_TEXT
        );
        $te->setInfo($this->getLocaleString(PluginConfig::F_REPORT_DATE_TEXT . '_info'));
        $te->setRequired(true);
        $te->setRows(8);
        $te->setUseRte(true);
        $te->setRteTagSet("extended");
        $te->disableButtons([
            'charmap',
            'undo',
            'redo',
            'justifyleft',
            'justifycenter',
            'justifyright',
            'justifyfull',
            'anchor',
            'fullscreen',
            'cut',
            'copy',
            'paste',
            'pastetext',
            'formatselect',
        ]);
        $cb->addSubItem($te);

        // SCHEDULED METADATA EDITABLE
        $ri = new ilRadioGroupInputGUI(
            $this->getLocaleString(PluginConfig::F_SCHEDULED_METADATA_EDITABLE),
            PluginConfig::F_SCHEDULED_METADATA_EDITABLE
        );
        $ro = new ilRadioOption(
            $this->getLocaleString(PluginConfig::F_SCHEDULED_METADATA_EDITABLE . '_' . PluginConfig::NO_METADATA),
            (string) PluginConfig::NO_METADATA
        );
        $ri->addOption($ro);
        $ro = new ilRadioOption(
            $this->getLocaleString(PluginConfig::F_SCHEDULED_METADATA_EDITABLE . '_' . PluginConfig::ALL_METADATA),
            (string) PluginConfig::ALL_METADATA
        );
        $ro->setInfo(
            $this->getLocaleString(
                PluginConfig::F_SCHEDULED_METADATA_EDITABLE . '_' . PluginConfig::ALL_METADATA . '_info'
            )
        );
        $ri->addOption($ro);
        $ro = new ilRadioOption(
            $this->getLocaleString(
                PluginConfig::F_SCHEDULED_METADATA_EDITABLE . '_' . PluginConfig::METADATA_EXCEPT_DATE_PLACE
            ),
            (string) PluginConfig::METADATA_EXCEPT_DATE_PLACE
        );
        $ri->addOption($ro);
        $this->addItem($ri);
    }

    private function initToUSection(): void
    {
        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->getLocaleString('eula'));
        $this->addItem($h);

        $te = new ilTextAreaInputGUI($this->getLocaleString(PluginConfig::F_EULA), PluginConfig::F_EULA);
        $te->setRequired(true);
        $te->setUseRte(true);
        $te->setRteTagSet("extended");
        $te->disableButtons([
            'charmap',
            'undo',
            'redo',
            'justifyleft',
            'justifycenter',
            'justifyright',
            'justifyfull',
            'anchor',
            'fullscreen',
            'cut',
            'copy',
            'paste',
            'pastetext',
            'formatselect',
        ]);
        $te->setRows(5);
        $this->addItem($te);

        // Terms of Use
        $terms = new ilCheckboxInputGUI($this->getLocaleString("accept_terms"), PluginConfig::F_ACCEPT_TERMS);
        $terms->setInfo($this->getLocaleString("accept_terms_info"));
        $this->addItem($terms);

        // Reset?
        $reset = new ilCheckboxInputGUI($this->getLocaleString(PluginConfig::F_RESET), PluginConfig::F_RESET);
        $reset->setInfo($this->getLocaleString(PluginConfig::F_RESET . "_info"));
        $this->addItem($reset);
    }

    protected function initGroupsRolesSection(): void
    {
        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->getLocaleString('groups'));
        $this->addItem($h);

        // groups
        foreach (PluginConfig::$groups as $group) {
            $te = new ilTextInputGUI($this->getLocaleString($group), $group);
            $te->setInfo($this->getLocaleString($group . '_info'));
            $this->addItem($te);
        }

        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->getLocaleString('roles'));
        $this->addItem($h);

        // standard roles
        $te = new ilTextInputGUI($this->getLocaleString('std_roles'), PluginConfig::F_STD_ROLES);
        $te->setInfo($this->getLocaleString('std_roles_info'));
        $te->setMulti(true);
        $te->setInlineStyle('min-width:250px');
        $this->addItem($te);

        $te = new ilTextInputGUI(
            $this->getLocaleString(PluginConfig::F_ROLE_USER_ACTIONS),
            PluginConfig::F_ROLE_USER_ACTIONS
        );
        $te->setInfo($this->getLocaleString(PluginConfig::F_ROLE_USER_ACTIONS . "_info"));
        $te->setMulti(true);
        $this->addItem($te);

        // other roles
        foreach (PluginConfig::$roles as $role) {
            $te = new ilTextInputGUI($this->getLocaleString($role), $role);
            $te->setInfo($this->getLocaleString($role . '_info'));
            $te->setRequired(true);
            $this->addItem($te);
        }

        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_IDENTIFIER_TO_UPPERCASE),
            PluginConfig::F_IDENTIFIER_TO_UPPERCASE
        );
        $this->addItem($cb);
    }

    protected function initSecuritySection(): void
    {
        $strings = (object) [
            'show' => $this->getLocaleString(PluginConfig::F_JWT_SECURITY_PK . '_show_icon'),
            'hide' => $this->getLocaleString(PluginConfig::F_JWT_SECURITY_PK . '_hide_icon'),
            'hidden_element_title' => $this->getLocaleString(PluginConfig::F_JWT_SECURITY_PK . '_hidden_element_title')
        ];
        $strings = json_encode($strings);
        $code = "il.Opencast.Form.passwordToggle.initTextarea('" . PluginConfig::F_JWT_SECURITY_PK . "', '" . $strings . "');";
        $this->main_tpl->addOnLoadCode($code);

        $this->main_tpl->setOnScreenMessage('info', $this->getLocaleString('security_info'), true);
        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->getLocaleString('security'));
        $this->addItem($h);

        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_SIGN_PLAYER_LINKS),
            PluginConfig::F_SIGN_PLAYER_LINKS
        );
        $this->addItem($cb);

        $cb_sub = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_SIGN_PLAYER_LINKS_OVERWRITE_DEFAULT),
            PluginConfig::F_SIGN_PLAYER_LINKS_OVERWRITE_DEFAULT
        );
        $cb->addSubItem($cb_sub);

        $cb_sub_2 = new ilNumberInputGUI(
            $this->getLocaleString(PluginConfig::F_SIGN_PLAYER_LINKS_ADDITIONAL_TIME_PERCENT),
            PluginConfig::F_SIGN_PLAYER_LINKS_ADDITIONAL_TIME_PERCENT
        );
        $cb_sub_2->setInfo($this->getLocaleString(PluginConfig::F_SIGN_PLAYER_LINKS_ADDITIONAL_TIME_PERCENT . '_info'));
        $cb_sub->addSubItem($cb_sub_2);

        $cb_sub = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_SIGN_PLAYER_LINKS_WITH_IP),
            PluginConfig::F_SIGN_PLAYER_LINKS_WITH_IP
        );
        $cb->addSubItem($cb_sub);

        $cb_sub = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_SIGN_PLAYER_LINKS_MP4),
            PluginConfig::F_SIGN_PLAYER_LINKS_MP4
        );
        $cb->addSubItem($cb_sub);

        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_SIGN_DOWNLOAD_LINKS),
            PluginConfig::F_SIGN_DOWNLOAD_LINKS
        );
        $cb->setInfo($this->getLocaleString(PluginConfig::F_SIGN_DOWNLOAD_LINKS . '_info'));
        $this->addItem($cb);

        $cb_sub = new ilNumberInputGUI(
            $this->getLocaleString(PluginConfig::F_SIGN_DOWNLOAD_LINKS_TIME),
            PluginConfig::F_SIGN_DOWNLOAD_LINKS_TIME
        );
        $cb_sub->setInfo($this->getLocaleString(PluginConfig::F_SIGN_DOWNLOAD_LINKS_TIME . '_info'));
        $cb->addSubItem($cb_sub);

        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_SIGN_THUMBNAIL_LINKS),
            PluginConfig::F_SIGN_THUMBNAIL_LINKS
        );
        $this->addItem($cb);

        $cb_sub = new ilNumberInputGUI(
            $this->getLocaleString(PluginConfig::F_SIGN_THUMBNAIL_LINKS_TIME),
            PluginConfig::F_SIGN_THUMBNAIL_LINKS_TIME
        );
        $cb_sub->setInfo($this->getLocaleString(PluginConfig::F_SIGN_THUMBNAIL_LINKS_TIME . '_info'));
        $cb->addSubItem($cb_sub);

        $cb_sub = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_SIGN_THUMBNAIL_LINKS_WITH_IP),
            PluginConfig::F_SIGN_THUMBNAIL_LINKS_WITH_IP
        );
        $cb->addSubItem($cb_sub);

        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_SIGN_ANNOTATION_LINKS),
            PluginConfig::F_SIGN_ANNOTATION_LINKS
        );
        $this->addItem($cb);

        $cb_sub = new ilNumberInputGUI(
            $this->getLocaleString(PluginConfig::F_SIGN_ANNOTATION_LINKS_TIME),
            PluginConfig::F_SIGN_ANNOTATION_LINKS_TIME
        );
        $cb_sub->setInfo($this->getLocaleString(PluginConfig::F_SIGN_ANNOTATION_LINKS_TIME . '_info'));
        $cb->addSubItem($cb_sub);

        $cb_sub = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_SIGN_ANNOTATION_LINKS_WITH_IP),
            PluginConfig::F_SIGN_ANNOTATION_LINKS_WITH_IP
        );
        $cb->addSubItem($cb_sub);

        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_ANNOTATION_TOKEN_SEC),
            PluginConfig::F_ANNOTATION_TOKEN_SEC
        );
        $cb->setInfo($this->getLocaleString(PluginConfig::F_ANNOTATION_TOKEN_SEC . '_info'));
        $this->addItem($cb);

        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_PRESIGN_LINKS),
            PluginConfig::F_PRESIGN_LINKS
        );
        $cb->setInfo($this->getLocaleString(PluginConfig::F_PRESIGN_LINKS . '_info'));
        $this->addItem($cb);

        // JWT enabled.
        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_JWT_SECURITY_ENABLED),
            PluginConfig::F_JWT_SECURITY_ENABLED
        );
        $cb->setInfo($this->getLocaleString(PluginConfig::F_JWT_SECURITY_ENABLED . '_info'));
        $this->addItem($cb);

        // JWT Private Key.
        $te_cb_sub = new ilTextAreaInputGUI($this->getLocaleString(PluginConfig::F_JWT_SECURITY_PK), PluginConfig::F_JWT_SECURITY_PK);
        $te_cb_sub->setInfo($this->getLocaleString(PluginConfig::F_JWT_SECURITY_PK . '_info'));
        $te_cb_sub->setRequired(true);
        $cb->addSubItem($te_cb_sub);

        //JWT Expiration.
        $nu_cb_sub = new ilNumberInputGUI($this->getLocaleString(PluginConfig::F_JWT_SECURITY_EXP), PluginConfig::F_JWT_SECURITY_EXP);
        $nu_cb_sub->setMinValue(1, true);
        $nu_cb_sub->setValue((string) PluginConfig::getConfig(PluginConfig::F_JWT_SECURITY_EXP) ?? "15");
        $nu_cb_sub->setInfo($this->getLocaleString(PluginConfig::F_JWT_SECURITY_EXP . '_info'));
        $cb->addSubItem($nu_cb_sub);

        // JWT Algorithm.
        $se_cb_sub = new ilSelectInputGUI($this->getLocaleString(PluginConfig::F_JWT_SECURITY_ALG), PluginConfig::F_JWT_SECURITY_ALG);
        $se_cb_sub->setInfo($this->getLocaleString(PluginConfig::F_JWT_SECURITY_ALG . '_info'));
        $algorithms = [];
        foreach (array_keys(\OpencastApi\Auth\JWT\OcJwtHandler::SUPPORTED_ALGORITHMS) as $alg) {
            $algorithms[$alg] = $alg;
        }
        $default = \OpencastApi\Auth\JWT\OcJwtHandler::DEFAULT_ALGORITHM;
        $se_cb_sub->setOptions($algorithms);
        $se_cb_sub->setValue(PluginConfig::getConfig(PluginConfig::F_JWT_SECURITY_ALG) ?? $default);
        $cb->addSubItem($se_cb_sub);

        // JWT Iframe Player Path
        $te_iframe_player_path_cb_sub = new ilTextInputGUI($this->getLocaleString('jwt_security_iframe_player_path'), PluginConfig::F_JWT_SECURITY_IFRAME_PLAYER_PATH);
        $te_iframe_player_path_cb_sub->setInfo($this->getLocaleString('jwt_security_iframe_player_path_info'));
        $cb->addSubItem($te_iframe_player_path_cb_sub);

        // JWT Basic Roles
        $te_basic_roles_cb_sub = new ilTextInputGUI($this->getLocaleString('jwt_security_basic_roles'), PluginConfig::F_JWT_SECURITY_BASIC_ROLES);
        $te_basic_roles_cb_sub->setInfo($this->getLocaleString('jwt_security_basic_roles_info'));
        $te_basic_roles_cb_sub->setMulti(true);
        $te_basic_roles_cb_sub->setInlineStyle('min-width:250px');
        $cb->addSubItem($te_basic_roles_cb_sub);

        // JWT Studio Roles.
        $te_studio_cb_sub = new ilTextInputGUI($this->getLocaleString('jwt_security_studio_roles'), PluginConfig::F_JWT_SECURITY_STUDIO_ROLES);
        $te_studio_cb_sub->setInfo($this->getLocaleString('jwt_security_studio_roles_info'));
        $te_studio_cb_sub->setMulti(true);
        $te_studio_cb_sub->setInlineStyle('min-width:250px');
        $cb->addSubItem($te_studio_cb_sub);

        // JWT Editor Roles.
        $te_editor_cb_sub = new ilTextInputGUI($this->getLocaleString('jwt_security_editor_roles'), PluginConfig::F_JWT_SECURITY_EDITOR_ROLES);
        $te_editor_cb_sub->setInfo($this->getLocaleString('jwt_security_editor_roles_info'));
        $te_editor_cb_sub->setMulti(true);
        $te_editor_cb_sub->setInlineStyle('min-width:250px');
        $cb->addSubItem($te_editor_cb_sub);

        // JWT Annotation-tool Roles.
        $te_annotation_tool_cb_sub = new ilTextInputGUI($this->getLocaleString('jwt_security_annotation_tool_roles'), PluginConfig::F_JWT_SECURITY_ANNOTATION_TOOL_ROLES);
        $te_annotation_tool_cb_sub->setInfo($this->getLocaleString('jwt_security_annotation_tool_roles_info'));
        $te_annotation_tool_cb_sub->setMulti(true);
        $te_annotation_tool_cb_sub->setInlineStyle('min-width:250px');
        $cb->addSubItem($te_annotation_tool_cb_sub);
    }

    protected function initAdvancedSection(): void
    {
        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->getLocaleString('advanced'));
        $this->addItem($h);

        $cb = new ilCheckboxInputGUI($this->getLocaleString(PluginConfig::F_COMMON_IDP), PluginConfig::F_COMMON_IDP);
        $cb->setInfo($this->getLocaleString(PluginConfig::F_COMMON_IDP . '_info'));
        $this->addItem($cb);

        $te = new ilSelectInputGUI($this->getLocaleString(PluginConfig::F_USER_MAPPING), PluginConfig::F_USER_MAPPING);
        $te->setInfo($this->getLocaleString(PluginConfig::F_USER_MAPPING . '_info'));
        $te->setOptions([
            xoctUser::MAP_EXT_ID => 'External-ID',
            xoctUser::MAP_LOGIN => 'Login',
            xoctUser::MAP_EMAIL => 'E-Mail',
        ]);
        $this->addItem($te);

        $cb = new ilRadioGroupInputGUI(
            $this->getLocaleString(PluginConfig::F_ACTIVATE_CACHE),
            PluginConfig::F_ACTIVATE_CACHE
        );
        $cb->setInfo($this->getLocaleString(PluginConfig::F_ACTIVATE_CACHE . '_info'));
        $opt = new ilRadioOption(
            $this->getLocaleString(PluginConfig::F_ACTIVATE_CACHE . '_' . PluginConfig::CACHE_DISABLED),
            (string) PluginConfig::CACHE_DISABLED
        );
        $cb->addOption($opt);
        $opt = new ilRadioOption(
            $this->getLocaleString(PluginConfig::F_ACTIVATE_CACHE . '_' . PluginConfig::CACHE_APCU),
            (string) PluginConfig::CACHE_APCU
        );
        $opt->setInfo(
            $this->getLocaleString(PluginConfig::F_ACTIVATE_CACHE . '_' . PluginConfig::CACHE_APCU . '_info')
        );
        $apc_available = !function_exists('apcu_fetch');
        $opt->setDisabled($apc_available);

        $cb->addOption($opt);
        $opt = new ilRadioOption(
            $this->getLocaleString(PluginConfig::F_ACTIVATE_CACHE . '_' . PluginConfig::CACHE_DATABASE),
            (string) PluginConfig::CACHE_DATABASE
        );
        $opt->setInfo(
            $this->getLocaleString(PluginConfig::F_ACTIVATE_CACHE . '_' . PluginConfig::CACHE_DATABASE . '_info')
        );
        $cb->addOption($opt);
        $this->addItem($cb);

        $te = new ilSelectInputGUI(
            $this->getLocaleString(PluginConfig::F_CURL_DEBUG_LEVEL),
            PluginConfig::F_CURL_DEBUG_LEVEL
        );
        $te->setInfo($this->getLocaleString(PluginConfig::F_CURL_DEBUG_LEVEL . '_info'));
        $te->setOptions([
            xoctLog::DEBUG_DEACTIVATED => $this->getLocaleString('log_level_' . xoctLog::DEBUG_DEACTIVATED),
            xoctLog::DEBUG_LEVEL_1 => $this->getLocaleString('log_level_' . xoctLog::DEBUG_LEVEL_1),
            xoctLog::DEBUG_LEVEL_2 => $this->getLocaleString('log_level_' . xoctLog::DEBUG_LEVEL_2),
            xoctLog::DEBUG_LEVEL_3 => $this->getLocaleString('log_level_' . xoctLog::DEBUG_LEVEL_3),
            xoctLog::DEBUG_LEVEL_4 => $this->getLocaleString('log_level_' . xoctLog::DEBUG_LEVEL_4),
        ]);
        $this->addItem($te);

        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_INGEST_UPLOAD),
            PluginConfig::F_INGEST_UPLOAD
        );
        $cb->setInfo($this->getLocaleString(PluginConfig::F_INGEST_UPLOAD . '_info'));
        $this->addItem($cb);

        $cb = new ilCheckboxInputGUI(
            $this->getLocaleString(PluginConfig::F_LOAD_TABLE_SYNCHRONOUSLY),
            PluginConfig::F_LOAD_TABLE_SYNCHRONOUSLY
        );
        $cb->setInfo($this->getLocaleString(PluginConfig::F_LOAD_TABLE_SYNCHRONOUSLY . '_info'));
        $this->addItem($cb);
    }
}
