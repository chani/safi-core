<?php

declare(strict_types=1);

namespace Safi\Core\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Safi\Core\ComponentManager;
use Safi\Core\Contracts\RouterInterface;
use Safi\Core\Contracts\ViewEngineInterface;

final class ComponentManagerTest extends TestCase
{
    public function testRegisterComponentViewsIgnoresNonExistingDirectories(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $logger = new NullLogger();
        $viewEngine = $this->createMock(ViewEngineInterface::class);

        $viewEngine->expects($this->never())->method('registerNamespace');

        $manager = new ComponentManager($container, $logger);
        $manager->registerComponentViews($viewEngine, '/non/existing/directory');
    }

    public function testRegisterAttributeRoutesIgnoresNonExistingDirectories(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $logger = new NullLogger();
        $router = $this->createMock(RouterInterface::class);

        $router->expects($this->never())->method('addRoute');

        $manager = new ComponentManager($container, $logger);
        $manager->registerAttributeRoutes($router, '/non/existing/directory');
    }
}
