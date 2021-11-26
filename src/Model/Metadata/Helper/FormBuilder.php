<?php

namespace srag\Plugins\Opencast\Model\Metadata\Helper;

use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\UI\Factory as UIFactory;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository;
use srag\Plugins\Opencast\Util\Upload\UploadStorageService;
use xoctFileUploadHandler;
use xoctException;

class FormBuilder
{
    /**
     * @var UIFactory
     */
    protected $ui_factory;
    /**
     * @var RefineryFactory
     */
    private $refinery_factory;
    /**
     * @var MDFormItemBuilder
     */
    private $form_item_builder;
    /**
     * @var SeriesWorkflowParameterRepository
     */
    private $workflowParameterRepository;
    /**
     * @var UploadHandler
     */
    private $uploadHandler;
    /**
     * @var UploadStorageService
     */
    private $uploadStorageService;


    /**
     * @param UIFactory $ui_factory
     * @param RefineryFactory $refinery_factory
     * @param MDFormItemBuilder $form_item_builder
     */
    public function __construct(UIFactory                         $ui_factory,
                                RefineryFactory                   $refinery_factory,
                                MDFormItemBuilder                 $form_item_builder,
                                SeriesWorkflowParameterRepository $workflowParameterRepository,
                                UploadHandler                     $uploadHandler,
                                UploadStorageService              $uploadStorageService)
    {
        $this->ui_factory = $ui_factory;
        $this->refinery_factory = $refinery_factory;
        $this->form_item_builder = $form_item_builder;
        $this->workflowParameterRepository = $workflowParameterRepository;
        $this->uploadHandler = $uploadHandler;
        $this->uploadStorageService = $uploadStorageService;
    }

    /**
     * @param string $form_action
     * @param int $obj_id set if the context is a repository object, to use the object level configuration
     * @param bool $as_admin set if the context is a repository object, to use the object level configuration
     * @return StandardForm
     * @throws xoctException
     */
    public function buildUploadForm(string $form_action, int $obj_id = 0, bool $as_admin = false): StandardForm
    {
        $upload_storage_service = $this->uploadStorageService;
        $file_input = $this->ui_factory->input()->field()->file($this->uploadHandler, 'File')
            ->withAdditionalTransformation($this->refinery_factory->custom()->transformation(function ($file) use ($upload_storage_service) {
                $id = $file[0];
                return $upload_storage_service->getFileInfo($id);
            }));
        // todo: make required when https://mantis.ilias.de/view.php?id=31645 is fixed
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            ['file' => $file_input]
            + $this->form_item_builder->buildFormElements(true)
            + ($obj_id == 0 ?
                $this->workflowParameterRepository->getGeneralFormItems()
                : $this->workflowParameterRepository->getFormItemsForObjId($obj_id, $as_admin))
        );
    }

}