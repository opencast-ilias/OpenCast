<?php

use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;

class xoctMetadataConfigGUI
{
    /**
     * @var MDFieldConfigRepository
     */
    private $mdFieldRepository;

    /**
     * @param MDFieldConfigRepository $mdFieldRepository
     */
    public function __construct(MDFieldConfigRepository $mdFieldRepository)
    {
        $this->mdFieldRepository = $mdFieldRepository;
    }



}