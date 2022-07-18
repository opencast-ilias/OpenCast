<?php

namespace srag\Plugins\Opencast\Model\Config;

use ActiveRecord;
use DOMCdataSection;
use DOMDocument;
use DOMElement;
use ilOpenCastPlugin;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\TermsOfUse\ToUManager;
use srag\Plugins\Opencast\Model\User\xoctUser;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
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

    const TABLE_NAME = 'xoct_config';
    const CONFIG_VERSION = 1;
    const F_CONFIG_VERSION = 'config_version';
    const F_USE_MODALS = 'use_modals';
    const F_CURL_USERNAME = 'curl_username';
    const F_CURL_PASSWORD = 'curl_password';
    const F_WORKFLOW = 'workflow';
    const F_WORKFLOW_UNPUBLISH = 'workflow_unpublish';
    const F_EULA = 'eula';
    const F_CURL_DEBUG_LEVEL = 'curl_debug_level';
    const F_API_VERSION = 'api_version';
    const F_API_BASE = 'api_base';
    const F_ACTIVATE_CACHE = 'activate_cache';
    const CACHE_DISABLED = 0;
    const CACHE_STANDARD = 1;
    const CACHE_DATABASE = 2;
    const F_USER_MAPPING = 'user_mapping';
    const F_GROUP_PRODUCERS = 'group_producers';
    const F_STD_ROLES = 'std_roles';
    const F_ROLE_USER_PREFIX = 'role_user_prefix';
    const F_ROLE_OWNER_PREFIX = 'role_owner_prefix';
    const F_IDENTIFIER_TO_UPPERCASE = 'identifier_to_uppercase';
    const F_SIGN_ANNOTATION_LINKS = 'sign_annotation_links';
    const F_ANNOTATION_TOKEN_SEC = 'annotation_token_security';
    const F_SIGN_ANNOTATION_LINKS_TIME = 'sign_annotation_links_time';
    const F_SIGN_ANNOTATION_LINKS_WITH_IP = 'sign_annotation_links_with_ip';
    const F_EDITOR_LINK = 'editor_link';
    const F_INTERNAL_VIDEO_PLAYER = 'internal_player';
    const F_PRESIGN_LINKS = 'presign_links';
    const F_SIGN_PLAYER_LINKS = 'sign_player_links';
    const F_SIGN_PLAYER_LINKS_OVERWRITE_DEFAULT = 'sign_player_links_overwrite_default';
    const F_SIGN_PLAYER_LINKS_ADDITIONAL_TIME_PERCENT = "sign_player_links_additional_time_percent";
    const F_SIGN_PLAYER_LINKS_WITH_IP = "sign_player_links_with_ip";
    const F_SIGN_DOWNLOAD_LINKS = 'sign_download_links';
    const F_SIGN_DOWNLOAD_LINKS_TIME = 'sign_download_links_time';
    const F_SIGN_THUMBNAIL_LINKS = 'sign_thumbnail_links';
    const F_SIGN_THUMBNAIL_LINKS_TIME = 'sign_thumbnail_links_time';
    const F_SIGN_THUMBNAIL_LINKS_WITH_IP = 'sign_thumbnail_links_with_ip';
    const F_AUDIO_ALLOWED = 'audio_allowed';
    const F_SCHEDULE_CHANNEL = 'schedule_channel';
    const F_CREATE_SCHEDULED_ALLOWED = 'create_scheduled_allowed';
    const F_STUDIO_ALLOWED = 'oc_studio_allowed';
    const F_STUDIO_URL = 'oc_studio_url';
    const F_EXT_DL_SOURCE = 'external_download_source';
    const F_VIDEO_PORTAL_LINK = 'video_portal_link';
    const F_VIDEO_PORTAL_TITLE = 'video_portal_title';
    const F_ENABLE_LIVE_STREAMS = 'enable_live_streams';
    const F_START_X_MINUTES_BEFORE_LIVE = 'start_x_minutes_before_live';
    const F_PRESENTATION_NODE = 'presentation_node';
    const F_ENABLE_CHAT = 'enable_chat';

    const F_REPORT_QUALITY = 'report_quality';
    const F_REPORT_QUALITY_EMAIL = 'report_quality_email';
    const F_REPORT_QUALITY_TEXT = 'report_quality_text';
    const F_REPORT_QUALITY_ACCESS = 'report_quality_access';
    const ACCESS_ALL = 1;
    const ACCESS_OWNER_ADMIN = 2;
    const F_REPORT_DATE = 'report_date';
    const F_REPORT_DATE_EMAIL = 'report_date_email';
    const F_REPORT_DATE_TEXT = 'report_date_text';
    const F_SCHEDULED_METADATA_EDITABLE = 'scheduled_metadata_editable';
    const NO_METADATA = 0;
    const ALL_METADATA = 1;
    const METADATA_EXCEPT_DATE_PLACE = 2;

    const F_USE_GENERATED_STREAMING_URLS = 'use_streaming';
    const F_STREAMING_URL = 'streaming_url';
    const F_USE_HIGH_LOW_RES_SEGMENT_PREVIEWS = 'use_highlowres_segment_preview';
    const F_ALLOW_WORKFLOW_PARAMS_IN_SERIES = 'allow_workflow_params_in_series';
    const F_INGEST_UPLOAD = 'ingest_upload';
    const F_COMMON_IDP = 'common_idp';
    const F_LOAD_TABLE_SYNCHRONOUSLY = 'load_table_sync';
    const F_ACCEPT_TERMS = "accept_terms";
    const F_RESET = "reset_terms";

    const F_PAELLA_OPTION = 'paella_config_option';
    const F_PAELLA_OPTION_LIVE = 'paella_config_option_l';
    const F_PAELLA_URL = 'paella_conf_url';
    const F_PAELLA_URL_LIVE = 'paella_conf_url_l';

    const PAELLA_OPTION_DEFAULT = 'default';
    const PAELLA_OPTION_URL = 'url';
    const PAELLA_DEFAULT_PATH = 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/js/paella_player/config.json';
    const PAELLA_DEFAULT_PATH_LIVE = 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/js/paella_player/config_live.json';

    /**
     * @var array
     */
    public static $roles = array(
        self::F_ROLE_USER_PREFIX,
        self::F_ROLE_OWNER_PREFIX
    );
    /**
     * @var array
     */
    public static $groups = array(
        self::F_GROUP_PRODUCERS,
    );


    /**
     * @return string
     * @deprecated
     */
    static function returnDbTableName()
    {
        return self::TABLE_NAME;
    }


    /**
     * @return string
     */
    public function getConnectorContainerName()
    {
        return self::TABLE_NAME;
    }


    public static function setApiSettings()
    {
        // CURL
        $xoctCurlSettings = new xoctCurlSettings();
        $xoctCurlSettings->setUsername(self::getConfig(self::F_CURL_USERNAME));
        $xoctCurlSettings->setPassword(self::getConfig(self::F_CURL_PASSWORD));
        $xoctCurlSettings->setVerifyPeer(true);
        $xoctCurlSettings->setVerifyHost(true);
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
        xoctUser::setUserMapping(self::getConfig(self::F_USER_MAPPING) ? self::getConfig(self::F_USER_MAPPING) : xoctUser::MAP_LOGIN);
    }

    /**
     * @param string $xml_file_path
     */
    public static function importFromXML(string $xml_file_path)
    {
        $domxml = new DOMDocument('1.0', 'UTF-8');
        $domxml->loadXML(file_get_contents($xml_file_path));

        /**
         * @var $node DOMElement
         */
        $xoct_confs = $domxml->getElementsByTagName('xoct_conf');
        foreach ($xoct_confs as $node) {
            $name = $node->getElementsByTagName('name')->item(0)->nodeValue;
            $value = $node->getElementsByTagName('value')->item(0)->nodeValue;
            if ($name) {
                $value = (is_array(json_decode($value))) ? json_decode($value) : $value;
                PluginConfig::set($name, $value);
            }
        }

        /**
         * @var $xoctWorkflowParameter WorkflowParameter
         */
        $xoct_workflow_parameter = $domxml->getElementsByTagName('xoct_workflow_parameter');

        foreach ($xoct_workflow_parameter as $node) {
            $id = $node->getElementsByTagName('id')->item(0)->nodeValue;
            if (!$id) {
                continue;
            }
            $xoctWorkflowParameter = WorkflowParameter::findOrGetInstance($id);
            $xoctWorkflowParameter->setTitle($node->getElementsByTagName('title')->item(0)->nodeValue);
            $xoctWorkflowParameter->setType($node->getElementsByTagName('type')->item(0)->nodeValue);
            $xoctWorkflowParameter->setDefaultValueMember($node->getElementsByTagName('default_value_member')->item(0)->nodeValue);
            $xoctWorkflowParameter->setDefaultValueAdmin($node->getElementsByTagName('default_value_admin')->item(0)->nodeValue);

            if (!WorkflowParameter::where(array('id' => $xoctWorkflowParameter->getId()))->hasSets()) {
                $xoctWorkflowParameter->create();
            } else {
                $xoctWorkflowParameter->update();
            }
        }

        /**
         * @var $xoctPublicationUsage PublicationUsage
         */
        $xoct_publication_usage = $domxml->getElementsByTagName('xoct_publication_usage');

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
            $xoctPublicationUsage->setSearchKey($node->getElementsByTagName('search_key')->item(0)->nodeValue ?: 'flavor');
            $xoctPublicationUsage->setMdType($node->getElementsByTagName('md_type')->item(0)->nodeValue);

            if (!PublicationUsage::where(array('usage_id' => $xoctPublicationUsage->getUsageId()))->hasSets()) {
                $xoctPublicationUsage->create();
            } else {
                $xoctPublicationUsage->update();
            }
        }
    }

    /**
     * @return string
     */
    public static function getXMLExport(): string
    {
        $opencast_plugin = ilOpenCastPlugin::getInstance();
        $domxml = new DOMDocument('1.0', 'UTF-8');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $config = $domxml->appendChild(new DOMElement('opencast_settings'));

        $xml_info = $config->appendChild(new DOMElement('info'));
        $xml_info->appendChild(new DOMElement('plugin_version', $opencast_plugin->getVersion()));
        $xml_info->appendChild(new DOMElement('plugin_db_version', $opencast_plugin->getDBVersion()));
        $xml_info->appendChild(new DOMElement('config_version', PluginConfig::getConfig(PluginConfig::CONFIG_VERSION)));

        // xoctConf
        $xml_xoctConfs = $config->appendChild(new DOMElement('xoct_confs'));
        /**
         * @var $xoctConf PluginConfig
         */
        foreach (PluginConfig::getCollection()->get() as $xoctConf) {
            $xml_xoctConf = $xml_xoctConfs->appendChild(new DOMElement('xoct_conf'));
            $xml_xoctConf->appendChild(new DOMElement('name', $xoctConf->getName()));
            //			$xml_xoctConf->appendChild(new DOMElement('value'))->appendChild(new DOMCdataSection($xoctConf->getValue()));
            $value = PluginConfig::getConfig($xoctConf->getName());
            $value = is_array($value) ? json_encode($value) : $value;
            $xml_xoctConf->appendChild(new DOMElement('value'))->appendChild(new DOMCdataSection($value));
        }

        // xoctWorkflowParameters
        $xml_xoctWorkflowParameters = $config->appendChild(new DOMElement('xoct_workflow_parameters'));
        /**
         * @var $xoctWorkflowParameter WorkflowParameter
         */
        foreach (WorkflowParameter::get() as $xoctWorkflowParameter) {
            $xml_xoctPU = $xml_xoctWorkflowParameters->appendChild(new DOMElement('xoct_workflow_parameter'));
            $xml_xoctPU->appendChild(new DOMElement('id'))->appendChild(new DOMCdataSection($xoctWorkflowParameter->getId()));
            $xml_xoctPU->appendChild(new DOMElement('title'))->appendChild(new DOMCdataSection($xoctWorkflowParameter->getTitle()));
            $xml_xoctPU->appendChild(new DOMElement('type'))->appendChild(new DOMCdataSection($xoctWorkflowParameter->getType()));
            $xml_xoctPU->appendChild(new DOMElement('default_value_member'))->appendChild(new DOMCdataSection($xoctWorkflowParameter->getDefaultValueMember()));
            $xml_xoctPU->appendChild(new DOMElement('default_value_admin'))->appendChild(new DOMCdataSection($xoctWorkflowParameter->getDefaultValueAdmin()));
        }

        // xoctPublicationUsages
        $xml_xoctPublicationUsages = $config->appendChild(new DOMElement('xoct_publication_usages'));
        /**
         * @var $xoctPublicationUsage PublicationUsage
         */
        foreach (PublicationUsage::get() as $xoctPublicationUsage) {
            $xml_xoctPU = $xml_xoctPublicationUsages->appendChild(new DOMElement('xoct_publication_usage'));
            $xml_xoctPU->appendChild(new DOMElement('usage_id'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getUsageId()));
            $xml_xoctPU->appendChild(new DOMElement('title'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getTitle()));
            $xml_xoctPU->appendChild(new DOMElement('description'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getDescription()));
            $xml_xoctPU->appendChild(new DOMElement('channel'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getChannel()));
            $xml_xoctPU->appendChild(new DOMElement('flavor'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getFlavor()));
            $xml_xoctPU->appendChild(new DOMElement('tag'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getTag()));
            $xml_xoctPU->appendChild(new DOMElement('search_key'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getSearchKey()));
            $xml_xoctPU->appendChild(new DOMElement('md_type'))->appendChild(new DOMCdataSection($xoctPublicationUsage->getMdType()));
        }

        return $domxml->saveXML();
    }

    /**
     * @var array
     */
    protected static $cache = array();
    /**
     * @var array
     */
    protected static $cache_loaded = array();
    /**
     * @var bool
     */
    protected $ar_safe_read = false;

    /**
     * @return bool
     */
    public static function isConfigUpToDate()
    {
        return self::getConfig(self::F_CONFIG_VERSION) == self::CONFIG_VERSION;
    }

    public static function load()
    {
        $null = parent::get();
    }

    /**
     * @param $name
     * @return mixed
     */
    public static function getConfig($name)
    {
        if (!self::$cache_loaded[$name]) {
            $obj = new self($name);
            self::$cache[$name] = json_decode($obj->getValue());
            self::$cache_loaded[$name] = true;
        }

        return self::$cache[$name];
    }

    /**
     * @param $name
     * @param $value
     */
    public static function set($name, $value)
    {
        $obj = new self($name);

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
        if (self::where(array('name' => $name))->hasSets()) {
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

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
