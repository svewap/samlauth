<?php

declare(strict_types=1);

namespace WapplerSystems\Samlauth\Enricher;

use WapplerSystems\Samlauth\Model\FrontendUser;

/**
 *
 * Class SimpleAttributeEnricher
 * @package WapplerSystems\Samlauth\Enricher
 */
class SimpleAttributeEnricher implements EnricherInterface
{

    private $map = [
        'urn:oid:2.5.4.42' => 'first_name',
        'urn:oid:2.5.4.4' => 'last_name',
        'urn:oid:0.9.2342.19200300.100.1.1' => 'email',
    ];

    public function process(FrontendUser $user, array $context)
    {
        foreach ($context['attributes'] as $key => $value) {
            if (!array_key_exists($key, $this->map)) {
                continue;
            }

            $user->setProperty($this->map[$key], $value[0]);
        }
    }
}
