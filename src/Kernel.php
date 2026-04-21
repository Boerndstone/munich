<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        $bundlesPath = $this->getProjectDir().'/config/bundles.php';
        if (!is_file($bundlesPath)) {
            yield new FrameworkBundle();

            return;
        }

        $contents = require $bundlesPath;
        foreach ($contents as $class => $envs) {
            if (!($envs[$this->environment] ?? $envs['all'] ?? false)) {
                continue;
            }

            if (\Symfony\UX\Toolkit\UXToolkitBundle::class === $class) {
                $uxToolkitFile = $this->getProjectDir().'/vendor/symfony/ux-toolkit/src/UXToolkitBundle.php';
                if (!is_file($uxToolkitFile)) {
                    throw new \LogicException(sprintf(
                        'symfony/ux-toolkit is enabled in config/bundles.php but is not installed (%s missing). Run from the project that contains composer.json: composer require symfony/ux-toolkit:^2',
                        $uxToolkitFile
                    ));
                }
                if (!class_exists($class, false)) {
                    require_once $uxToolkitFile;
                }
                if (!class_exists($class)) {
                    throw new \LogicException(sprintf(
                        'Failed to load UXToolkitBundle from %s after require_once.',
                        $uxToolkitFile
                    ));
                }
            }

            yield new $class();
        }
    }
}
