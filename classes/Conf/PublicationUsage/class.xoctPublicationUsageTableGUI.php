<?php

declare(strict_types=1);

use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageGroup;
use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;
use srag\Plugins\Opencast\Container\Container;
use srag\Plugins\Opencast\Container\Init;

/**
 * Class xoctEventTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.00
 *
 */
class xoctPublicationUsageTableGUI extends ilTable2GUI
{
    use LocaleTrait {
        LocaleTrait::getLocaleString as _getLocaleString;
    }

    private Container $container;

    public function getLocaleString(string $string, ?string $module = '', ?string $fallback = null): string
    {
        return $this->_getLocaleString($string, empty($module) ? 'publication_usage' : $module, $fallback);
    }

    public const TBL_ID = 'tbl_xoct_pub_u';
    protected OpencastDIC $legacy_container;
    protected ilOpenCastPlugin $plugin;
    protected array $filter = [];
    protected PublicationUsageRepository $repository;

    /**
     * @param string $a_parent_cmd
     */
    public function __construct(xoctPublicationUsageGUI $a_parent_obj, string $a_parent_cmd)
    {
        global $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->container = Init::init($DIC);
        $this->legacy_container = $this->container->legacy();
        $this->plugin = $this->container->plugin();
        $this->repository = new PublicationUsageRepository();
        $this->setId(self::TBL_ID);
        $this->setPrefix(self::TBL_ID);
        $this->setFormName(self::TBL_ID);
        $this->ctrl->saveParameter($a_parent_obj, $this->getNavParameter());
        parent::__construct($a_parent_obj, $a_parent_cmd);
        $this->parent_obj = $a_parent_obj;
        $this->setTitle($this->getLocaleString('table_title_usage'));
        $this->setRowTemplate(
            'tpl.publication_usage.html',
            'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast'
        );
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        $this->initColumns();
        $this->parseData();
    }

    #[ReturnTypeWillChange]
    protected function fillRow(/*array*/ $a_set): void
    {
        /**
         * @var $publication_usage PublicationUsage
         */
        $publication_usage = $this->repository->getUsage($a_set['usage_id']);
        $this->tpl->setVariable('USAGE_ID', $publication_usage->getUsageId());
        $this->tpl->setVariable('TITLE', $publication_usage->getTitle());
        $this->tpl->setVariable('DISPLAY_NAME', $publication_usage->getDisplayName());
        $this->tpl->setVariable('DESCRIPTION', $publication_usage->getDescription());
        $this->tpl->setVariable('CHANNEL', $publication_usage->getChannel());
        $this->tpl->setVariable('MD_TYPE', $this->getLocaleString('md_type_' . $publication_usage->getMdType()));
        if ($publication_usage->getMdType() === PublicationUsage::MD_TYPE_PUBLICATION_ITSELF) {
            $this->tpl->setVariable('FLAVOR', '&nbsp');
            $this->tpl->setVariable('TAG', '&nbsp');
        } elseif ($publication_usage->getSearchKey() == xoctPublicationUsageFormGUI::F_FLAVOR) {
            $this->tpl->setVariable('FLAVOR', $publication_usage->getFlavor());
            $this->tpl->setVariable('TAG', '&nbsp');
        } else {
            $this->tpl->setVariable('TAG', $publication_usage->getTag());
            $this->tpl->setVariable('FLAVOR', '&nbsp');
        }
        $group_name = '';
        if (!is_null($publication_usage->getGroupId())) {
            $publication_usage_group = PublicationUsageGroup::find($publication_usage->getGroupId());
            $group_name = $publication_usage_group ? $publication_usage_group->getName() : $group_name;
        }
        $this->tpl->setVariable('GROUP_NAME', $group_name);

        $extras = [];
        if ($publication_usage->getUsageId() == PublicationUsage::USAGE_DOWNLOAD ||
            $publication_usage->getUsageId() == PublicationUsage::USAGE_DOWNLOAD_FALLBACK) {
            if ($publication_usage->isExternalDownloadSource()) {
                $extras[] = $this->getLocaleString('ext_dl_source');
            }
        }
        $this->tpl->setVariable('EXTRA_CONFIG', implode('<br>', $extras));

        $this->addActionMenu($publication_usage);
    }

    protected function initColumns()
    {
        $this->addColumn($this->getLocaleString('usage_id'));
        $this->addColumn($this->getLocaleString('title'));
        $this->addColumn($this->getLocaleString('display_name'));
        $this->addColumn($this->getLocaleString('description'));
        $this->addColumn($this->getLocaleString('channel'));
        $this->addColumn($this->getLocaleString('md_type'));
        $this->addColumn($this->getLocaleString('flavor'));
        $this->addColumn($this->getLocaleString('tag'));
        $this->addColumn($this->getLocaleString('group_th'));
        $this->addColumn($this->getLocaleString('extra_config'));

        $this->addColumn($this->getLocaleString('actions', 'common'), '', '150px');
    }

    protected function addActionMenu(PublicationUsage $xoctPublicationUsage)
    {
        $current_selection_list = new ilAdvancedSelectionListGUI();
        $current_selection_list->setListTitle($this->getLocaleString('actions', 'common'));
        $current_selection_list->setId(self::TBL_ID . '_actions_' . $xoctPublicationUsage->getUsageId());
        $current_selection_list->setUseImages(false);

        $this->ctrl->setParameter(
            $this->parent_obj,
            xoctPublicationUsageGUI::IDENTIFIER,
            $xoctPublicationUsage->getUsageId()
        );
        $current_selection_list->addItem(
            $this->getLocaleString(xoctGUI::CMD_EDIT),
            xoctGUI::CMD_EDIT,
            $this->ctrl->getLinkTarget($this->parent_obj, xoctGUI::CMD_EDIT)
        );
        $current_selection_list->addItem(
            $this->getLocaleString(xoctGUI::CMD_DELETE),
            xoctGUI::CMD_DELETE,
            $this->ctrl->getLinkTarget($this->parent_obj, xoctGUI::CMD_CONFIRM)
        );

        $this->tpl->setVariable('ACTIONS', $current_selection_list->getHTML());
    }

    protected function parseData(): void
    {
        $this->setData($this->repository->getArray());
    }

    /**
     * @param $item
     */
    protected function addAndReadFilterItem(ilFormPropertyGUI $item): void
    {
        $this->addFilterItem($item);
        $item->readFromSession();
        $this->filter[$item->getPostVar()] = $item instanceof ilCheckboxInputGUI ? $item->getChecked(
        ) : $item->getValue();
    }
}
