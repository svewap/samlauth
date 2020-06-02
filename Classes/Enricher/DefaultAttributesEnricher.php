<?php

declare(strict_types=1);

namespace WapplerSystems\Samlauth\Enricher;

use WapplerSystems\Samlauth\Model\FrontendUser;

class DefaultAttributesEnricher implements EnricherInterface
{
    public function process(FrontendUser $user, array $context)
    {
        $user->setProperty('tx_extbase_type', 'Tx_Extbase_Domain_Model_FrontendUser');
    }
}
