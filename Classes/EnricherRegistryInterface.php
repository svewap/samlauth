<?php
declare(strict_types=1);
namespace WapplerSystems\Samlauth;


use WapplerSystems\Samlauth\Model\FrontendUser;

interface EnricherRegistryInterface
{
    public function process(FrontendUser $user, array $context);
}
