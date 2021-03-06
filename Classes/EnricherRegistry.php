<?php
declare(strict_types=1);
namespace WapplerSystems\Samlauth;

use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\Samlauth\Enricher\EnricherInterface;
use WapplerSystems\Samlauth\Model\FrontendUser;
use function is_a;
use function ksort;

class EnricherRegistry implements EnricherInterface
{
    private static $objects = [];

    public static function register($class, $priority = 0)
    {
        if (!is_a($class, EnricherInterface::class, true)) {
            throw new \LogicException(sprintf(
                'The class "%s" does not implements "%s".',
                $class,
                EnricherInterface::class
            ));
        }

        if (!array_key_exists($priority, self::$objects)) {
            self::$objects[$priority] = [];
        }

        self::$objects[$priority][] = $class;
    }

    public function process(FrontendUser $user, array $context)
    {
        foreach ($this->flatten(self::$objects) as $className) {
            GeneralUtility::makeInstance($className)->process($user, $context);
        }
    }

    private function flatten($list)
    {
        ksort($list);

        foreach ($list as $_ => $objects) {
            yield from $objects;
        }
    }
}
