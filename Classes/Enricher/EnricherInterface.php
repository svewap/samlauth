<?php

declare(strict_types=1);

namespace WapplerSystems\Samlauth\Enricher;

use WapplerSystems\Samlauth\Model\FrontendUser;

interface EnricherInterface
{
    public function process(FrontendUser $user, array $context);
}
