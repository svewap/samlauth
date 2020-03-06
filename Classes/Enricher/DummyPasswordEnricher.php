<?php

declare(strict_types=1);

namespace WapplerSystems\Samlauth\Enricher;

use WapplerSystems\Samlauth\Model\FrontendUser;

class DummyPasswordEnricher implements EnricherInterface
{
    public function process(FrontendUser $user, array $context)
    {
        if ($user->hasProperty('password') && $user->getProperty('password')) {
            return;
        }

        $password = base64_encode(random_bytes(32));
        $user->setProperty('password', $password);
    }
}
