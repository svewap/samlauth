<?php
declare(strict_types=1);

namespace WapplerSystems\Samlauth\Enricher;


use WapplerSystems\Samlauth\Model\FrontendUser;

class RoleGroupMapperEnricher implements EnricherInterface
{

    public function process(FrontendUser $user, array $context)
    {

        //DebugUtility::debug($context['configuration'],__CLASS__);
        //DebugUtility::debug($context['attributes'],__CLASS__);

        $usergroups = [];

        if (isset($context['attributes']['Role'])) {

            foreach ($context['attributes']['Role'] as $role) {

                foreach ($context['configuration']['mappings'] as $roleMapping) {
                    if ($roleMapping['role'] == $role) {
                        $usergroups[] = $roleMapping['group_ids'];
                    }
                }
            }
        }
        $user->addUsergroups($usergroups);
    }

}