<?php
namespace WapplerSystems\Samlauth\Backend;

use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Service\FluxService;
use FluidTYPO3\Flux\Service\PageService;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use FluidTYPO3\Flux\Utility\MiscellaneousUtility;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;


class DomainProvider
{


    /**
     * @param array $parameters
     */
    public function addItems(array $parameters)
    {

        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $sites = $siteFinder->getAllSites();

        foreach ($sites as $site) {

            $base = $site->getBase();
            $parse = parse_url($base);
            $host = $parse['host'];

            $parameters['items'] = array_merge(
                $parameters['items'],
                [[$host,$host]]
            );
        }

    }

}
