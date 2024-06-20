<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Config;

use ActiveRecord;
use DOMCdataSection;
use DOMDocument;
use DOMElement;
use ilOpenCastPlugin;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationSubUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageGroup;
use srag\Plugins\Opencast\Model\TermsOfUse\ToUManager;
use srag\Plugins\Opencast\Model\User\xoctUser;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventAR;
use srag\Plugins\Opencast\Model\Metadata\Config\Series\MDFieldConfigSeriesAR;
use srag\Plugins\Opencast\Model\Workflow\WorkflowAR;
use xoctCurl;
use xoctCurlSettings;
use xoctLog;
use xoctRequest;
use xoctRequestSettings;

/**
 * Class xoctConf
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class PluginConfig extends ActiveRecord
{
    public const TABLE_NAME = 'xoct_config';
    public const CONFIG_VERSION = 1;
    public const F_CONFIG_VERSION = 'config_version';
    public const F_USE_MODALS = 'use_modals';
    public const F_CURL_USERNAME = 'curl_username';
    public const F_CURL_PASSWORD = 'curl_password';
    public const F_CURL_MAX_UPLOADSIZE = 'curl_max_upload_size';
    public const F_CURL_CHUNK_SIZE = 'curl_chunk_size';
    public const F_WORKFLOW = 'workflow';
    public const F_WORKFLOW_UNPUBLISH = 'workflow_unpublish';
    public const F_EULA = 'eula';
    public const F_CURL_DEBUG_LEVEL = 'curl_debug_level';
    public const F_API_VERSION = 'api_version';
    public const F_API_BASE = 'api_base';
    public const F_ACTIVATE_CACHE = 'activate_cache';
    public const CACHE_DISABLED = 0;
    public const CACHE_APCU = 1;
    public const CACHE_DATABASE = 2;
    public const F_USER_MAPPING = 'user_mapping';
    public const F_GROUP_PRODUCERS = 'group_producers';
    public const F_STD_ROLES = 'std_roles';
    public const F_ROLE_USER_PREFIX = 'role_user_prefix';
    public const F_ROLE_USER_ACTIONS = 'role_user_actions';
    public const F_ROLE_OWNER_PREFIX = 'role_owner_prefix';
    public const F_IDENTIFIER_TO_UPPERCASE = 'identifier_to_uppercase';
    public const F_SIGN_ANNOTATION_LINKS = 'sign_annotation_links';
    public const F_ANNOTATION_TOKEN_SEC = 'annotation_token_security';
    public const F_SIGN_ANNOTATION_LINKS_TIME = 'sign_annotation_links_time';
    public const F_SIGN_ANNOTATION_LINKS_WITH_IP = 'sign_annotation_links_with_ip';
    public const F_EDITOR_LINK = 'editor_link';
    public const F_INTERNAL_VIDEO_PLAYER = 'internal_player';
    public const F_PRESIGN_LINKS = 'presign_links';
    public const F_SIGN_PLAYER_LINKS = 'sign_player_links';
    public const F_SIGN_PLAYER_LINKS_OVERWRITE_DEFAULT = 'sign_player_links_overwrite_default';
    public const F_SIGN_PLAYER_LINKS_ADDITIONAL_TIME_PERCENT = "sign_player_links_additional_time_percent";
    public const F_SIGN_PLAYER_LINKS_WITH_IP = "sign_player_links_with_ip";
    public const F_SIGN_PLAYER_LINKS_MP4 = "sign_player_links_mp4";
    public const F_SIGN_DOWNLOAD_LINKS = 'sign_download_links';
    public const F_SIGN_DOWNLOAD_LINKS_TIME = 'sign_download_links_time';
    public const F_SIGN_THUMBNAIL_LINKS = 'sign_thumbnail_links';
    public const F_SIGN_THUMBNAIL_LINKS_TIME = 'sign_thumbnail_links_time';
    public const F_SIGN_THUMBNAIL_LINKS_WITH_IP = 'sign_thumbnail_links_with_ip';
    public const F_AUDIO_ALLOWED = 'audio_allowed';
    public const F_SCHEDULE_CHANNEL = 'schedule_channel';
    public const F_CREATE_SCHEDULED_ALLOWED = 'create_scheduled_allowed';
    public const F_STUDIO_ALLOWED = 'oc_studio_allowed';
    public const F_STUDIO_URL = 'oc_studio_url';
    public const F_VIDEO_PORTAL_LINK = 'video_portal_link';
    public const F_VIDEO_PORTAL_TITLE = 'video_portal_title';
    public const F_ENABLE_LIVE_STREAMS = 'enable_live_streams';
    public const F_START_X_MINUTES_BEFORE_LIVE = 'start_x_minutes_before_live';
    public const F_PRESENTATION_NODE = 'presentation_node';
    public const F_LIVESTREAM_TYPE = 'livestream_type';
    public const F_LIVESTREAM_BUFFERED = 'livestream_buffered';
    public const F_ENABLE_CHAT = 'enable_chat';

    public const F_REPORT_QUALITY = 'report_quality';
    public const F_REPORT_QUALITY_EMAIL = 'report_quality_email';
    public const F_REPORT_QUALITY_TEXT = 'report_quality_text';
    public const F_REPORT_QUALITY_ACCESS = 'report_quality_access';
    public const ACCESS_ALL = 1;
    public const ACCESS_OWNER_ADMIN = 2;
    public const F_REPORT_DATE = 'report_date';
    public const F_REPORT_DATE_EMAIL = 'report_date_email';
    public const F_REPORT_DATE_TEXT = 'report_date_text';
    public const F_SCHEDULED_METADATA_EDITABLE = 'scheduled_metadata_editable';
    public const NO_METADATA = 0;
    public const ALL_METADATA = 1;
    public const METADATA_EXCEPT_DATE_PLACE = 2;

    public const F_USE_GENERATED_STREAMING_URLS = 'use_streaming';
    public const F_STREAMING_URL = 'streaming_url';
    public const F_USE_HIGH_LOW_RES_SEGMENT_PREVIEWS = 'use_highlowres_segment_preview';
    public const F_ALLOW_WORKFLOW_PARAMS_IN_SERIES = 'allow_workflow_params_in_series';
    public const F_INGEST_UPLOAD = 'ingest_upload';
    public const F_COMMON_IDP = 'common_idp';
    public const F_LOAD_TABLE_SYNCHRONOUSLY = 'load_table_sync';
    public const F_ACCEPT_TERMS = "accept_terms";
    public const F_RESET = "reset_terms";

    public const F_PAELLA_OPTION = 'paella_config_option';
    public const F_PAELLA_URL = 'paella_conf_url';
    public const F_PAELLA_THEME = 'paella_config_theme';
    public const F_PAELLA_THEME_URL = 'paella_config_theme_url';
    public const F_PAELLA_THEME_LIVE = 'paella_config_theme_l';
    public const F_PAELLA_THEME_URL_LIVE = 'paella_config_theme_url_l';

    public const F_PAELLA_FALLBACK_CAPTIONS = 'paella_conf_fallback_captions';
    public const F_PAELLA_FALLBACK_LANGS = 'paella_conf_fallback_langs';
    public const F_PAELLA_DISPLAY_CAPTION_TEXT_TYPE = 'paella_conf_display_caption_text_type';
    public const F_PAELLA_DISPLAY_CAPTION_TEXT_GENERATOR = 'paella_conf_display_caption_text_generator';
    public const F_PAELLA_DISPLAY_CAPTION_TEXT_GENERATOR_TYPE = 'paella_conf_display_caption_text_generator_type';
    public const F_PAELLA_PREVENT_VIDEO_DOWNLOAD = 'paella_conf_prevent_video_download';

    public const PAELLA_OPTION_DEFAULT = 'default';
    public const PAELLA_OPTION_URL = 'url';
    public const PAELLA_DEFAULT_PATH = 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/js/opencast/src/Paella/config/config.json';
    public const PAELLA_RESOURCES_PATH = 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/js/opencast/src/Paella/resources';
    public const PAELLA_LANG_PATH = 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/js/opencast/src/Paella/lang';
    public const PAELLA_DEFAULT_THEME = 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/js/opencast/src/Paella/default_theme/opencast_theme.json';
    public const PAELLA_DEFAULT_THEME_LIVE = 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/js/opencast/src/Paella/default_theme/opencast_live_theme.json';
    public const PAELLA_DEFAULT_THEME_LIVE_BUFFERED = 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/js/opencast/src/Paella/default_theme/opencast_live_buffered_theme.json';

    public const F_WORKFLOWS_TAGS = 'config_workflows_tags';
    public const F_PAELLA_PREVIEW_FALLBACK = 'paella_config_preview_fallback';
    public const F_PAELLA_PREVIEW_FALLBACK_URL = 'paella_config_preview_fallback_url';
    public const PAELLA_DEFAULT_PREVIEW = 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/images/default_preview.png';

    public const F_THUMBNAIL_UPLOAD_ENABLED = 'thumbnail_config_upload_enabled';
    public const F_THUMBNAIL_UPLOAD_MODE = 'thumbnail_config_upload_mode';
    public const F_THUMBNAIL_ACCEPTED_MIMETYPES = 'thumbnail_config_accepted_mimetypes';

    /**
     * @var array
     */
    public static $roles = [
        self::F_ROLE_USER_PREFIX,
        self::F_ROLE_OWNER_PREFIX
    ];
    /**
     * @var array
     */
    public static $groups = [
        self::F_GROUP_PRODUCERS,
    ];

    /**
     * @deprecated
     */
    public static function returnDbTableName(): string
    {
        return self::TABLE_NAME;
    }

    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }

    public static function setApiSettings(): void
    {
        // CURL
        $xoctCurlSettings = new xoctCurlSettings();
        $xoctCurlSettings->setUsername(self::getConfig(self::F_CURL_USERNAME));
        $xoctCurlSettings->setPassword(self::getConfig(self::F_CURL_PASSWORD));
        xoctCurl::init($xoctCurlSettings);

        //CACHE
        //		xoctCache::setOverrideActive(self::getConfig(self::F_ACTIVATE_CACHE));
        //		xoctCache::setOverrideActive(true);

        // API
        $xoctRequestSettings = new xoctRequestSettings();
        $xoctRequestSettings->setApiBase(self::getConfig(self::F_API_BASE));
        xoctRequest::init($xoctRequestSettings);

        // LOG
        xoctLog::init(self::getConfig(self::F_CURL_DEBUG_LEVEL));

        // USER
        xoctUser::setUserMapping((int) (self::getConfig(self::F_USER_MAPPING) ?: xoctUser::MAP_LOGIN));
    }

    public static function importFromXML(string $xml_file_path): void
    {
        if (!is_readable($xml_file_path)) {
            throw new \InvalidArgumentException("File not readable: " . $xml_file_path);
        }

        $dom_xml = new DOMDocument('1.0', 'UTF-8');
        $dom_xml->loadXML(file_get_contents($xml_file_path));

        /**
         * @var $node DOMElement
         */
        $configuration_nodes = $dom_xml->getElementsByTagName('xoct_conf');
        foreach ($configuration_nodes as $node) {
            $name = $node->getElementsByTagName('name')->item(0)->nodeValue;
            $value = $node->getElementsByTagName('value')->item(0)->nodeValue;
            if ($name) {
                $value = (is_array(json_decode($value)))
                    ? json_decode($value)
                    : $value;
                PluginConfig::set($name, $value);
            }
        }

        /**
         * @var $xoctMDFieldConfigEventAR MDFieldConfigEventAR
         */
        $xoct_md_field_event = $dom_xml->getElementsByTagName('xoct_md_field_event');

        // Clear MDFieldConfigEventAR
        MDFieldConfigEventAR::flushDB();

        foreach ($xoct_md_field_event as $node) {
            $xoctMDFieldConfigEventAR = new MDFieldConfigEventAR();
            $xoctMDFieldConfigEventAR->setSort((int) $node->getElementsByTagName('sort')->item(0)->nodeValue);
            $xoctMDFieldConfigEventAR->setFieldId($node->getElementsByTagName('field_id')->item(0)->nodeValue);
            $xoctMDFieldConfigEventAR->setTitleDe($node->getElementsByTagName('title_de')->item(0)->nodeValue);
            $xoctMDFieldConfigEventAR->setTitleEn($node->getElementsByTagName('title_en')->item(0)->nodeValue);
            $xoctMDFieldConfigEventAR->setVisibleForPermissions(
                $node->getElementsByTagName('visible_for_permissions')->item(0)->nodeValue
            );
            $xoctMDFieldConfigEventAR->setPrefill($node->getElementsByTagName('prefill')->item(0)->nodeValue);
            $xoctMDFieldConfigEventAR->setReadOnly((bool) $node->getElementsByTagName('read_only')->item(0)->nodeValue);
            $xoctMDFieldConfigEventAR->setRequired((bool) $node->getElementsByTagName('required')->item(0)->nodeValue);
            $xoctMDFieldConfigEventAR->setValuesFromEditableString(
                $node->getElementsByTagName('values')->item(0)->nodeValue ?? ''
            );
            $xoctMDFieldConfigEventAR->store();
        }

        /**
         * @var $xoctMDFieldConfigSeriesAR MDFieldConfigSeriesAR
         */
        $xoct_md_field_series = $dom_xml->getElementsByTagName('xoct_md_field_series');

        // Clear MDFieldConfigSeriesAR
        MDFieldConfigSeriesAR::flushDB();

        foreach ($xoct_md_field_series as $node) {
            $xoctMDFieldConfigSeriesAR = new MDFieldConfigSeriesAR();
            $xoctMDFieldConfigSeriesAR->setSort((int) $node->getElementsByTagName('sort')->item(0)->nodeValue);
            $xoctMDFieldConfigSeriesAR->setFieldId($node->getElementsByTagName('field_id')->item(0)->nodeValue);
            $xoctMDFieldConfigSeriesAR->setTitleDe($node->getElementsByTagName('title_de')->item(0)->nodeValue);
            $xoctMDFieldConfigSeriesAR->setTitleEn($node->getElementsByTagName('title_en')->item(0)->nodeValue);
            $xoctMDFieldConfigSeriesAR->setVisibleForPermissions(
                $node->getElementsByTagName('visible_for_permissions')->item(0)->nodeValue
            );
            $xoctMDFieldConfigSeriesAR->setPrefill($node->getElementsByTagName('prefill')->item(0)->nodeValue);
            $xoctMDFieldConfigSeriesAR->setReadOnly(
                (bool) $node->getElementsByTagName('read_only')->item(0)->nodeValue
            );
            $xoctMDFieldConfigSeriesAR->setRequired((bool) $node->getElementsByTagName('required')->item(0)->nodeValue);
            $xoctMDFieldConfigSeriesAR->setValuesFromEditableString(
                $node->getElementsByTagName('values')->item(0)->nodeValue ?? ''
            );
            $xoctMDFieldConfigSeriesAR->store();
        }

        /**
         * @var $xoctWorkflowParameter WorkflowParameter
         */
        $xoct_workflow_parameter = $dom_xml->getElementsByTagName('xoct_workflow_parameter');

        foreach ($xoct_workflow_parameter as $node) {
            $id = $node->getElementsByTagName('id')->item(0)->nodeValue;
            if (!$id) {
                continue;
            }
            $xoctWorkflowParameter = WorkflowParameter::findOrGetInstance($id);
            $xoctWorkflowParameter->setTitle($node->getElementsByTagName('title')->item(0)->nodeValue);
            $xoctWorkflowParameter->setType($node->getElementsByTagName('type')->item(0)->nodeValue);
            $xoctWorkflowParameter->setDefaultValueMember(
                $node->getElementsByTagName('default_value_member')->item(0)->nodeValue
            );
            $xoctWorkflowParameter->setDefaultValueAdmin(
                $node->getElementsByTagName('default_value_admin')->item(0)->nodeValue
            );

            if (!WorkflowParameter::where(['id' => $xoctWorkflowParameter->getId()])->hasSets()) {
                $xoctWorkflowParameter->create();
            } else {
                $xoctWorkflowParameter->update();
            }
        }

        /**
         * @var $xoctWorkflowParameter WorkflowParameter
         */
        $xoct_workflow = $dom_xml->getElementsByTagName('xoct_workflow');

        // We need to reset the workflow table.
        WorkflowAR::flushDB();

        foreach ($xoct_workflow as $node) {
            $xoctWorkflow = new WorkflowAR();
            $xoctWorkflow->setWorkflowId($node->getElementsByTagName('workflow_id')->item(0)->nodeValue);
            $xoctWorkflow->setTitle($node->getElementsByTagName('title')->item(0)->nodeValue ?? '');
            $xoctWorkflow->setDescription($node->getElementsByTagName('description')->item(0)->nodeValue ?? '');
            $xoctWorkflow->setTags($node->getElementsByTagName('tags')->item(0)->nodeValue ?? '');
            $xoctWorkflow->setConfigPanel($node->getElementsByTagName('config_panel')->item(0)->nodeValue ?? '');
            $xoctWorkflow->create();
        }

        /**
         * @var $xoctPublicationUsage PublicationUsage
         */
        $xoct_publication_usage = $dom_xml->getElementsByTagName('xoct_publication_usage');

        // We need to reset the main usages, otherwise we end with an already filled unwanted usages!
        PublicationUsage::flushDB();

        foreach ($xoct_publication_usage as $node) {
            $usage_id = $node->getElementsByTagName('usage_id')->item(0)->nodeValue;
            if (!$usage_id) {
                continue;
            }
            $xoctPublicationUsage = PublicationUsage::findOrGetInstance($usage_id);
            $xoctPublicationUsage->setTitle($node->getElementsByTagName('title')->item(0)->nodeValue);
            $xoctPublicationUsage->setDescription($node->getElementsByTagName('description')->item(0)->nodeValue);
            $xoctPublicationUsage->setChannel($node->getElementsByTagName('channel')->item(0)->nodeValue);
            $xoctPublicationUsage->setFlavor($node->getElementsByTagName('flavor')->item(0)->nodeValue);
            $xoctPublicationUsage->setTag($node->getElementsByTagName('tag')->item(0)->nodeValue ?: '');
            $xoctPublicationUsage->setSearchKey(
                $node->getElementsByTagName('search_key')->item(0)->nodeValue ?: 'flavor'
            );
            $xoctPublicationUsage->setMdType($node->getElementsByTagName('md_type')->item(0)->nodeValue);
            $xoctPublicationUsage->setDisplayName($node->getElementsByTagName('display_name')->item(0)->nodeValue ?? '');
            $xoctPublicationUsage->setGroupId($node->getElementsByTagName('group_id')->item(0)->nodeValue ?? '');
            $mediatype = $node->getElementsByTagName('mediatype')->item(0)->nodeValue ?? '';
            $xoctPublicationUsage->setMediaType($mediatype);
            $ignore_object_setting = $node->getElementsByTagName('ignore_object_setting')->item(0)->nodeValue ?? false;
            $xoctPublicationUsage->setIgnoreObjectSettings((bool) $ignore_object_setting);
            $ext_dl_source = $node->getElementsByTagName('ext_dl_source')->item(0)->nodeValue ?? false;
            $xoctPublicationUsage->setExternalDownloadSource((bool) $ext_dl_source);

            if (!PublicationUsage::where(['usage_id' => $xoctPublicationUsage->getUsageId()])->hasSets()) {
                $xoctPublicationUsage->create();
            } else {
                $xoctPublicationUsage->update();
            }
        }

        /**
         * @var $xoctPublicationSubUsage PublicationSubUsage
         */
        $xoct_publication_sub_usage = $dom_xml->getElementsByTagName('xoct_publication_sub_usage');

        // We need to reset the subs.
        PublicationSubUsage::flushDB();

        foreach ($xoct_publication_sub_usage as $node) {
            $parent_usage_id = $node->getElementsByTagName('parent_usage_id')->item(0)->nodeValue;
            if (!$parent_usage_id) {
                continue;
            }
            $xoctPublicationSubUsage = PublicationSubUsage::findOrGetInstance(0);
            $xoctPublicationSubUsage->setParentUsageId(
                $node->getElementsByTagName('parent_usage_id')->item(0)->nodeValue
            );
            $xoctPublicationSubUsage->setTitle($node->getElementsByTagName('title')->item(0)->nodeValue);
            $xoctPublicationSubUsage->setDescription($node->getElementsByTagName('description')->item(0)->nodeValue);
            $xoctPublicationSubUsage->setChannel($node->getElementsByTagName('channel')->item(0)->nodeValue);
            $xoctPublicationSubUsage->setFlavor($node->getElementsByTagName('flavor')->item(0)->nodeValue);
            $xoctPublicationSubUsage->setTag($node->getElementsByTagName('tag')->item(0)->nodeValue ?: '');
            $xoctPublicationSubUsage->setSearchKey(
                $node->getElementsByTagName('search_key')->item(0)->nodeValue ?: 'flavor'
            );
            $xoctPublicationSubUsage->setMdType($node->getElementsByTagName('md_type')->item(0)->nodeValue);
            $xoctPublicationSubUsage->setDisplayName($node->getElementsByTagName('display_name')->item(0)->nodeValue);
            $xoctPublicationSubUsage->setGroupId($node->getElementsByTagName('group_id')->item(0)->nodeValue);
            $mediatype = $node->getElementsByTagName('mediatype')->item(0)->nodeValue;
            $xoctPublicationSubUsage->setMediaType($mediatype ?? '');
            $ignore_object_setting = (bool) $node->getElementsByTagName('ignore_object_setting')->item(0)->nodeValue;
            $xoctPublicationSubUsage->setIgnoreObjectSettings($ignore_object_setting);
            $ext_dl_source = (bool) $node->getElementsByTagName('ext_dl_source')->item(0)->nodeValue;
            $xoctPublicationSubUsage->setExternalDownloadSource($ext_dl_source);
            $xoctPublicationSubUsage->create();
        }

        /**
         * @var $xoctPublicationUsageGroup PublicationUsageGroup
         */
        $xoct_publication_usage_groups = $dom_xml->getElementsByTagName('xoct_publication_usage_group');

        // We need to remove the publication usage groups no matter what!
        PublicationUsageGroup::flushDB();

        foreach ($xoct_publication_usage_groups as $node) {
            $old_id = $node->getElementsByTagName('id')->item(0)->nodeValue;
            $xoctPublicationUsageGroup = PublicationUsageGroup::findOrGetInstance(0);
            $xoctPublicationUsageGroup->setName($node->getElementsByTagName('name')->item(0)->nodeValue);
            $xoctPublicationUsageGroup->setDisplayName($node->getElementsByTagName('display_name')->item(0)->nodeValue);
            $xoctPublicationUsageGroup->setDescription($node->getElementsByTagName('description')->item(0)->nodeValue);
            $xoctPublicationUsageGroup->create();
            $new_id = $xoctPublicationUsageGroup->getId();

            // Mapping old id with new id in PublicationUsage, because we flushed the table.
            foreach (PublicationUsage::where(['group_id' => intval($old_id)])->get() as $pu) {
                $pu->setGroupId($new_id);
                $pu->update();
            }

            // Mapping old id with new id in PublicationSubUsage, because we flushed the table.
            foreach (PublicationSubUsage::where(['group_id' => intval($old_id)])->get() as $psu) {
                $psu->setGroupId($new_id);
                $psu->update();
            }
        }
    }

    public static function getXMLExport(): string
    {
        global $DIC;

        /** @var \ilComponentRepository $component_repo */
        $component_repo = $DIC['component.repository'];
        $plugin_infos = $component_repo->getPluginById('xoct');

        $opencast_plugin = ilOpenCastPlugin::getInstance();
        $domxml = new DOMDocument('1.0', 'UTF-8');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $config = $domxml->appendChild(new DOMElement('opencast_settings'));

        $xml_info = $config->appendChild(new DOMElement('info'));
        $xml_info->appendChild(new DOMElement('plugin_version', (string) $opencast_plugin->getVersion()));
        $xml_info->appendChild(new DOMElement('plugin_db_version', (string) $plugin_infos->getCurrentDBVersion()));
        $xml_info->appendChild(
            new DOMElement('config_version', (string) PluginConfig::getConfig(PluginConfig::F_CONFIG_VERSION))
        );

        // xoctConf
        $xml_xoctConfs = $config->appendChild(new DOMElement('xoct_confs'));
        /**
         * @var $xoctConf PluginConfig
         */
        foreach (PluginConfig::getCollection()->get() as $xoctConf) {
            $xml_xoctConf = $xml_xoctConfs->appendChild(new DOMElement('xoct_conf'));
            $xml_xoctConf->appendChild(new DOMElement('name', $xoctConf->getName()));
            $value = PluginConfig::getConfig($xoctConf->getName());
            $value = is_array($value) ? json_encode($value) : $value;
            $xml_xoctConf->appendChild(new DOMElement('value'))->appendChild(new DOMCdataSection((string) $value));
        }

        // xoctMDFieldConfigEventARs
        $xml_xoctMDFieldConfigEventARs = $config->appendChild(new DOMElement('xoct_md_field_events'));
        /**
         * @var $xoctMDFieldConfigEventARs MDFieldConfigEventAR
         */
        foreach (MDFieldConfigEventAR::get() as $xoctMDFieldConfigEventAR) {
            $xml_xoctMDE = $xml_xoctMDFieldConfigEventARs->appendChild(new DOMElement('xoct_md_field_event'));
            $xml_xoctMDE->appendChild(new DOMElement('sort'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigEventAR->getSort())
            );
            $xml_xoctMDE->appendChild(new DOMElement('field_id'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigEventAR->getFieldId())
            );
            $xml_xoctMDE->appendChild(new DOMElement('title_de'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigEventAR->getTitle('de'))
            );
            $xml_xoctMDE->appendChild(new DOMElement('title_en'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigEventAR->getTitle('en'))
            );
            $xml_xoctMDE->appendChild(new DOMElement('visible_for_permissions'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigEventAR->getVisibleForPermissions())
            );
            $xml_xoctMDE->appendChild(new DOMElement('prefill'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigEventAR->getPrefill())
            );
            $xml_xoctMDE->appendChild(new DOMElement('read_only'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigEventAR->isReadOnly())
            );
            $xml_xoctMDE->appendChild(new DOMElement('required'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigEventAR->isRequired())
            );
            $xml_xoctMDE->appendChild(new DOMElement('values'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigEventAR->getValuesAsEditableString())
            );
        }

        // xoctMDFieldConfigSeriesARs
        $xml_xoctMDFieldConfigSeriesARs = $config->appendChild(new DOMElement('xoct_md_field_serieses'));
        /**
         * @var $xoctMDFieldConfigSeriesARs MDFieldConfigSeriesAR
         */
        foreach (MDFieldConfigSeriesAR::get() as $xoctMDFieldConfigSeriesAR) {
            $xml_xoctMDS = $xml_xoctMDFieldConfigSeriesARs->appendChild(new DOMElement('xoct_md_field_series'));
            $xml_xoctMDS->appendChild(new DOMElement('sort'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigSeriesAR->getSort())
            );
            $xml_xoctMDS->appendChild(new DOMElement('field_id'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigSeriesAR->getFieldId())
            );
            $xml_xoctMDS->appendChild(new DOMElement('title_de'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigSeriesAR->getTitle('de'))
            );
            $xml_xoctMDS->appendChild(new DOMElement('title_en'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigSeriesAR->getTitle('en'))
            );
            $xml_xoctMDS->appendChild(new DOMElement('visible_for_permissions'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigSeriesAR->getVisibleForPermissions())
            );
            $xml_xoctMDS->appendChild(new DOMElement('prefill'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigSeriesAR->getPrefill())
            );
            $xml_xoctMDS->appendChild(new DOMElement('read_only'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigSeriesAR->isReadOnly())
            );
            $xml_xoctMDS->appendChild(new DOMElement('required'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigSeriesAR->isRequired())
            );
            $xml_xoctMDS->appendChild(new DOMElement('values'))->appendChild(
                new DOMCdataSection((string) $xoctMDFieldConfigSeriesAR->getValuesAsEditableString())
            );
        }

        // xoctWorkflowParameters
        $xml_xoctWorkflowParameters = $config->appendChild(new DOMElement('xoct_workflow_parameters'));
        /**
         * @var $xoctWorkflowParameter WorkflowParameter
         */
        foreach (WorkflowParameter::get() as $xoctWorkflowParameter) {
            $xml_xoctPU = $xml_xoctWorkflowParameters->appendChild(new DOMElement('xoct_workflow_parameter'));
            $xml_xoctPU->appendChild(new DOMElement('id'))->appendChild(
                new DOMCdataSection((string) $xoctWorkflowParameter->getId())
            );
            $xml_xoctPU->appendChild(new DOMElement('title'))->appendChild(
                new DOMCdataSection((string) $xoctWorkflowParameter->getTitle())
            );
            $xml_xoctPU->appendChild(new DOMElement('type'))->appendChild(
                new DOMCdataSection((string) $xoctWorkflowParameter->getType())
            );
            $xml_xoctPU->appendChild(new DOMElement('default_value_member'))->appendChild(
                new DOMCdataSection((string) $xoctWorkflowParameter->getDefaultValueMember())
            );
            $xml_xoctPU->appendChild(new DOMElement('default_value_admin'))->appendChild(
                new DOMCdataSection((string) $xoctWorkflowParameter->getDefaultValueAdmin())
            );
        }

        // xoctWorkflows
        $xml_xoctWorkflows = $config->appendChild(new DOMElement('xoct_workflows'));
        /**
         * @var $xoctWorkflowAR WorkflowAR
         */
        foreach (WorkflowAR::get() as $xoctWorkflows) {
            $xml_xoctWf = $xml_xoctWorkflows->appendChild(new DOMElement('xoct_workflow'));
            $xml_xoctWf->appendChild(new DOMElement('workflow_id'))->appendChild(
                new DOMCdataSection((string) $xoctWorkflows->getWorkflowId())
            );
            $xml_xoctWf->appendChild(new DOMElement('title'))->appendChild(
                new DOMCdataSection((string) $xoctWorkflows->getTitle() ?? '')
            );
            $xml_xoctWf->appendChild(new DOMElement('description'))->appendChild(
                new DOMCdataSection((string) $xoctWorkflows->getDescription() ?? '')
            );
            $xml_xoctWf->appendChild(new DOMElement('tags'))->appendChild(
                new DOMCdataSection((string) $xoctWorkflows->getTags() ?? '')
            );
            $xml_xoctWf->appendChild(new DOMElement('config_panel'))->appendChild(
                new DOMCdataSection((string) $xoctWorkflows->getConfigPanel() ?? '')
            );
        }

        // xoctPublicationUsages
        $xml_xoctPublicationUsages = $config->appendChild(new DOMElement('xoct_publication_usages'));
        /**
         * @var $xoctPublicationUsage PublicationUsage
         */
        foreach (PublicationUsage::get() as $xoctPublicationUsage) {
            $xml_xoctPU = $xml_xoctPublicationUsages->appendChild(
                new DOMElement('xoct_publication_usage')
            );
            $xml_xoctPU->appendChild(new DOMElement('usage_id'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsage->getUsageId())
            );
            $xml_xoctPU->appendChild(new DOMElement('title'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsage->getTitle())
            );
            $xml_xoctPU->appendChild(new DOMElement('description'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsage->getDescription())
            );
            $xml_xoctPU->appendChild(new DOMElement('channel'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsage->getChannel())
            );
            $xml_xoctPU->appendChild(new DOMElement('flavor'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsage->getFlavor())
            );
            $xml_xoctPU->appendChild(new DOMElement('tag'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsage->getTag())
            );
            $xml_xoctPU->appendChild(new DOMElement('search_key'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsage->getSearchKey())
            );
            $xml_xoctPU->appendChild(new DOMElement('md_type'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsage->getMdType())
            );
            $xml_xoctPU->appendChild(new DOMElement('group_id'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsage->getGroupId())
            );
            $xml_xoctPU->appendChild(new DOMElement('display_name'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsage->getDisplayName())
            );
            $xml_xoctPU->appendChild(new DOMElement('mediatype'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsage->getMediaType())
            );
            $xml_xoctPU->appendChild(new DOMElement('ignore_object_setting'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsage->ignoreObjectSettings())
            );
            $xml_xoctPU->appendChild(new DOMElement('ext_dl_source'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsage->isExternalDownloadSource())
            );
        }

        // xoctPublicationSubUsage
        $xml_xoctPublicationSubUsages = $config->appendChild(new DOMElement('xoct_publication_sub_usages'));
        /**
         * @var $xoctPublicationSubUsage PublicationSubUsage
         */
        foreach (PublicationSubUsage::get() as $xoctPublicationSubUsage) {
            $xml_xoctPSU = $xml_xoctPublicationSubUsages->appendChild(new DOMElement('xoct_publication_sub_usage'));
            $xml_xoctPSU->appendChild(new DOMElement('parent_usage_id'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationSubUsage->getParentUsageId())
            );
            $xml_xoctPSU->appendChild(new DOMElement('title'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationSubUsage->getTitle())
            );
            $xml_xoctPSU->appendChild(new DOMElement('description'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationSubUsage->getDescription())
            );
            $xml_xoctPSU->appendChild(new DOMElement('channel'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationSubUsage->getChannel())
            );
            $xml_xoctPSU->appendChild(new DOMElement('flavor'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationSubUsage->getFlavor())
            );
            $xml_xoctPSU->appendChild(new DOMElement('tag'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationSubUsage->getTag())
            );
            $xml_xoctPSU->appendChild(new DOMElement('search_key'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationSubUsage->getSearchKey())
            );
            $xml_xoctPSU->appendChild(new DOMElement('md_type'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationSubUsage->getMdType())
            );
            $xml_xoctPSU->appendChild(new DOMElement('group_id'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationSubUsage->getGroupId())
            );
            $xml_xoctPSU->appendChild(new DOMElement('display_name'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationSubUsage->getDisplayName())
            );
            $xml_xoctPSU->appendChild(new DOMElement('mediatype'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationSubUsage->getMediaType())
            );
            $xml_xoctPSU->appendChild(new DOMElement('ignore_object_setting'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationSubUsage->ignoreObjectSettings())
            );
            $xml_xoctPSU->appendChild(new DOMElement('ext_dl_source'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationSubUsage->isExternalDownloadSource())
            );
        }

        // xoctPublicationUsageGroups
        $xml_xoctPublicationUsageGroups = $config->appendChild(new DOMElement('xoct_publication_usage_groups'));
        /**
         * @var $xoctPublicationUsageGroup PublicationUsageGroup
         */
        foreach (PublicationUsageGroup::get() as $xoctPublicationUsageGroup) {
            $xml_xoctPUG = $xml_xoctPublicationUsageGroups->appendChild(new DOMElement('xoct_publication_usage_group'));
            $xml_xoctPUG->appendChild(new DOMElement('id'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsageGroup->getId())
            );
            $xml_xoctPUG->appendChild(new DOMElement('name'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsageGroup->getName())
            );
            $xml_xoctPUG->appendChild(new DOMElement('display_name'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsageGroup->getDisplayName())
            );
            $xml_xoctPUG->appendChild(new DOMElement('description'))->appendChild(
                new DOMCdataSection((string) $xoctPublicationUsageGroup->getDescription())
            );
        }

        return $domxml->saveXML();
    }

    /**
     * @var array
     */
    protected static $cache = [];
    /**
     * @var array
     */
    protected static $cache_loaded = [];

    public static function isConfigUpToDate(): bool
    {
        return self::getConfig(self::F_CONFIG_VERSION) == self::CONFIG_VERSION;
    }

    public static function load(): void
    {
        parent::get();
    }

    /**
     * @return mixed
     */
    public static function getConfig(string $name)
    {
        if (!(self::$cache_loaded[$name] ?? false)) {
            try {
                $obj = new self($name);
                self::$cache[$name] = json_decode($obj->getValue());
                self::$cache_loaded[$name] = true;
            } catch (\Exception $e) {
                return null;
            }
        }

        return self::$cache[$name];
    }

    /**
     * @param $value
     */
    public static function set(string $name, $value): void
    {
        try {
            $obj = new self($name);
        } catch (\Throwable $t) {
            $obj = new self();
            $obj->setName($name);
        }

        /*
         * If the terms of use have been updated,
         * reset the list of users who have accepted them
         */
        if ($name === self::F_RESET && $value === "1") {
            // ToDo: get instance_id and add as parameter
            ToUManager::resetForInstance();
            $obj->setValue("");
        } else {
            $obj->setValue(json_encode($value));
        }
        if (self::where(['name' => $name])->hasSets()) {
            $obj->update();
        } else {
            $obj->create();
        }
    }

    /**
     * @var string
     * @db_has_field        true
     * @db_is_unique        true
     * @db_is_primary       true
     * @db_is_notnull       true
     * @db_fieldtype        text
     * @db_length           250
     */
    protected $name;

    /**
     * @var string
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           4000
     */
    protected $value;

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
