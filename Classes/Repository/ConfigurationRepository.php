<?php

declare(strict_types=1);

namespace WapplerSystems\Samlauth\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;

final class ConfigurationRepository
{
    /**
     * @var ConnectionPool
     */
    private $pool;

    public function __construct(ConnectionPool $pool)
    {
        $this->pool = $pool;
    }

    public function findByHostname(string $host)
    {
        $qb = $this->pool->getConnectionForTable('tx_samlauth_domain_model_configuration')
            ->createQueryBuilder();

        $qb->select('i.*');
        $qb->from('tx_samlauth_domain_model_configuration', 'i');
        $qb->where($qb->expr()->eq(
            'i.domain',
            $qb->createNamedParameter($host)
        ));

        $configuration = $qb->execute()->fetch();
        if (is_array($configuration)) {

            $qb2 = $this->pool->getConnectionForTable('tx_samlauth_domain_model_role_group_mapping')
                ->createQueryBuilder();

            $qb2->select('i.*');
            $qb2->from('tx_samlauth_domain_model_role_group_mapping', 'i');
            $qb2->where($qb->expr()->eq(
                'i.configuration',
                $qb2->createNamedParameter($configuration['uid'])
            ));
            $configuration['mappings'] = $qb2->execute()->fetchAll();

        }

        return $configuration;
    }
}
