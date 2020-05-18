<?php
namespace WapplerSystems\Samlauth\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

abstract class AbstractController extends ActionController {


    /** @var \WapplerSystems\Samlauth\ConfigurationProvider */
    protected $configurationProvider = null;


    protected $samlSettings;


    public function initializeAction() {


        $this->configurationProvider = $this->objectManager->get(\WapplerSystems\Samlauth\ConfigurationProvider::class);


    }


}