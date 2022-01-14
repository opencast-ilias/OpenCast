<?php

namespace srag\Plugins\Opencast\UI\Metadata\Config;

use ilOpenCastPlugin;
use srag\DataTableUI\OpenCast\Component\Data\Data;
use srag\DataTableUI\OpenCast\Component\Settings\Settings;
use srag\DataTableUI\OpenCast\Implementation\Data\Fetcher\AbstractDataFetcher;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;

class MDConfigDataFetcher extends AbstractDataFetcher
{
    const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    /**
     * @var MDFieldConfigRepository
     */
    private $repository;
    /**
     * @var string
     */
    private $updateUrl;
    /**
     * @var string
     */
    private $deleteUrl;

    public function __construct(MDFieldConfigRepository $repository, string $updateUrl, string $deleteUrl)
    {
        $this->repository = $repository;
        $this->updateUrl = $updateUrl;
        $this->deleteUrl = $deleteUrl;
    }

    public function fetchData(Settings $settings): Data
    {
        $data = array_map(function (array $set) {
            $set['actions'] = [
                self::dic()->ui()->factory()->link()->standard(self::plugin()->translate('action_update'), $this->updateUrl)
            ];
            if (!$set['required']) {
                $set['actions'][] =
                    self::dic()->ui()->factory()->link()->standard(self::plugin()->translate('action_delete'), $this->deleteUrl);
            }
            $set['prefill'] = $set['prefill']->getValue();
            return self::dataTableUI()->data()->row()->property($set['field_id'], (object) $set);
        }, $this->repository->getArray());
        return self::dataTableUI()->data()->data($data, count($data));
    }
}