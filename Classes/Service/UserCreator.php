<?php
declare(strict_types=1);

namespace WapplerSystems\Samlauth\Service;

use TYPO3\CMS\Core\SingletonInterface;
use WapplerSystems\Samlauth\EnricherRegistry;
use WapplerSystems\Samlauth\Manager\FrontendUserManager;
use WapplerSystems\Samlauth\Model\FrontendUser;

final class UserCreator implements SingletonInterface
{
    private EnricherRegistry $enrichers;

    private FrontendUserManager $manager;

    public function __construct(EnricherRegistry $registry, FrontendUserManager $manager)
    {
        $this->enrichers = $registry;
        $this->manager = $manager;
    }

    /**
     * @param array $attributes
     * @param array $configuration
     * @return FrontendUser
     */
    public function updateOrCreate(array $attributes, array $configuration): FrontendUser
    {
        $userFolder = $configuration['user_folder'];

        if (!($attributes['urn:oid:1.2.840.113549.1.9.1'] ?? false)) {
            throw new \LogicException('The idp does not return any "uid". Please check the configuration in the idp settings.');
        }

        $user = $this->manager->getRepository()->findByUsername($attributes['urn:oid:1.2.840.113549.1.9.1'][0], $userFolder);

        if (null === $user) {
            $user = new FrontendUser([
                'username' => $attributes['urn:oid:1.2.840.113549.1.9.1'][0],
                'pid' => $userFolder,
            ]);
        }

        unset($attributes['urn:oid:1.2.840.113549.1.9.1']);

        $this->enrichers->process($user, [
            'configuration' => $configuration,
            'attributes' => $attributes,
        ]);

        $this->manager->save($user);

        return $user;
    }
}
