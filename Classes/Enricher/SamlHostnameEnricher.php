<?php

declare(strict_types=1);

namespace WapplerSystems\Samlauth\Enricher;

use WapplerSystems\Samlauth\Model\FrontendUser;

class SamlHostnameEnricher implements EnricherInterface
{
    public function process(FrontendUser $user, array $context)
    {
        if (null !== $user->getUid()) {
            return;
        }
        $user->setProperty('samlauth_host', $context['idp']['name']);
    }
}
