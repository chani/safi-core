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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use RegexIterator;
use Safi\Core\Attributes\Route;
use Safi\Core\Contracts\ContainerRegistrarInterface;
use Safi\Core\Contracts\RouterInterface;
use Safi\Core\Contracts\ServiceProviderInterface;
use Safi\Core\Contracts\ViewEngineInterface;
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
        private readonly LoggerInterface $logger,
    ) {}

    public function registerComponentViews(ViewEngineInterface $viewEngine, string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $dirs = scandir($directory);
        if (!is_array($dirs)) {
            return;
        }

        foreach ($dirs as $dir) {
            if ($dir === '.') {
                continue;
            }
            if ($dir === '..') {
                continue;
            }
            $viewsPath = $directory . '/' . $dir . '/Views';
            if (is_dir($viewsPath)) {
                $viewEngine->registerNamespace($dir, $viewsPath);
            }
        }
    }

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
     * @param array<int, ServiceProviderInterface> $providers
     */
    public function bootProviders(array $providers): void
    {
        foreach ($providers as $provider) {
            try {
                if ($this->container instanceof ContainerRegistrarInterface) {
                    $provider->register($this->container);
                }

                $provider->boot($this->container);

                $providerClass = $provider::class;
                $this->loadedComponents[$providerClass] = $provider;
            } catch (Throwable $e) {
                $this->logger->error("Failed to boot service provider " . $provider::class . ": " . $e->getMessage());
            }
        }
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
}
