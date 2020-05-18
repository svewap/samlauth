<?php

declare(strict_types=1);

namespace WapplerSystems\Samlauth\Enricher;

use WapplerSystems\Samlauth\Model\FrontendUser;

class DefaultGroupEnricher implements EnricherInterface
{
    public function process(FrontendUser $user, array $context)
    {

        if ($context['configuration']['default_groups_enable'] && $context['configuration']['default_groups']) {
            $user->setProperty('usergroup', $context['configuration']['default_groups']);
        }
    }
}
