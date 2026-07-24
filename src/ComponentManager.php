<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use RegexIterator;
use Safi\Core\Attributes\Route;
use Safi\Core\Contracts\ContainerRegistrarInterface;
use Safi\Core\Contracts\RouterInterface;
use Safi\Core\Contracts\ServiceProviderInterface;
use Safi\Core\Exception\AmbiguousInterfaceException;
use Safi\Core\Util\ClassFinder;
use SplFileInfo;
use Throwable;

final class ComponentManager
{
    /** @var array<string, object> */
    private array $loadedComponents = [];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {}

    public function registerAttributeRoutes(RouterInterface $router, string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $regex = new RegexIterator($iterator, '/\.php$/i');

        /** @var mixed $file */
        foreach ($regex as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }

            $filePath = $file->getPathname();

            // Ignore nested vendor directories relative to the target scan path
            $relativePath = substr($filePath, strlen($directory));
            if (str_contains($relativePath, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $content = file_get_contents($filePath);
            if ($content === false) {
                continue;
            }

            $className = ClassFinder::extractClassName($content);
            if (!$className) {
                continue;
            }
            if (!class_exists($className)) {
                continue;
            }

            $reflect = new ReflectionClass($className);
            foreach ($reflect->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes(Route::class);
                foreach ($attributes as $attribute) {
                    $route = $attribute->newInstance();
                    if ($route instanceof Route) {
                        $router->addRoute(
                            $route->method,
                            $route->path,
                            [$className, $method->getName()],
                            $route->name !== '' ? $route->name : null,
                            ['public' => $route->public],
                        );
                    }
                }
            }
        }
    }

    /**
     * @param array<int, class-string<ServiceProviderInterface>> $providers
     */
    public function bootProviders(array $providers): void
    {
        $cacheKey = 'safi.booted_providers';
        $bootedList = [];

        foreach ($providers as $providerClass) {
            try {
                /** @var ServiceProviderInterface $provider */
                $provider = $this->container->get($providerClass);

                if ($this->container instanceof ContainerRegistrarInterface) {
                    $provider->register($this->container);
                }

                $provider->boot($this->container);
                $this->loadedComponents[$providerClass] = $provider;
                $bootedList[] = $providerClass;
            } catch (Throwable $e) {
                $this->logger->error("Failed to boot service provider {$providerClass}: " . $e->getMessage());
            }
        }

        $this->cache->set($cacheKey, $bootedList, 3600);
    }

    /**
     * @param array<int, array{name: string, dir: string}> $componentData
     * @return array<string, string>
     */
    public function buildInterfaceMap(array $componentData): array
    {
        $interfaceMap = [];
        foreach ($componentData as $comp) {
            $compDir = $comp['dir'];
            if (!is_dir($compDir)) {
                continue;
            }

            $directoryIterator = new RecursiveDirectoryIterator($compDir);
            $iterator = new RecursiveIteratorIterator($directoryIterator);
            $regex = new RegexIterator($iterator, '/\.php$/i');

            /** @var mixed $file */
            foreach ($regex as $file) {
                if (!$file instanceof SplFileInfo) {
                    continue;
                }

                $filePath = $file->getPathname();
                $relativePath = substr($filePath, strlen($compDir));
                if (str_contains($relativePath, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
                    continue;
                }

                $content = file_get_contents($filePath);
                if ($content === false) {
                    continue;
                }

                $className = ClassFinder::extractClassName($content);
                if (!$className) {
                    continue;
                }
                if (!class_exists($className)) {
                    continue;
                }

                $reflect = new ReflectionClass($className);
                foreach ($reflect->getInterfaceNames() as $interfaceName) {
                    if (isset($interfaceMap[$interfaceName]) && $interfaceMap[$interfaceName] !== $className) {
                        throw new AmbiguousInterfaceException(
                            "Ambiguous interface discovery: Interface '{$interfaceName}' is implemented by both '{$interfaceMap[$interfaceName]}' and '{$className}'.",
                        );
                    }
                    $interfaceMap[$interfaceName] = $className;
                }
            }
        }

        return $interfaceMap;
    }

    /**
     * @return array<string, object>
     */
    public function getLoadedComponents(): array
    {
        return $this->loadedComponents;
    }

    public function clearProviderCache(): void
    {
        $this->cache->delete('safi.booted_providers');
    }
}
