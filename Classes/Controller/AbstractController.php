<?php
namespace WapplerSystems\Samlauth\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

abstract class AbstractController extends ActionController {


    /** @var \WapplerSystems\Samlauth\Configuration */
    protected $configuration = null;


    protected $samlSettings;


    public function initializeAction() {


        $this->configuration = $this->objectManager->get(\WapplerSystems\Samlauth\Configuration::class);


    }


}