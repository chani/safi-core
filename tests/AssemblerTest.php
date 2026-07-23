<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Safi\Core\Assembler;
use Safi\Core\Logger;

final class AssemblerTest extends TestCase
{
    private Assembler $assembler;

    protected function setUp(): void
    {
        $logger = new Logger(false);
        $this->assembler = new Assembler($logger);
    }

    public function testAutowiresConcreteClassWithoutDependencies(): void
    {
        $instance = $this->assembler->get(DummyService::class);
        $this->assertInstanceOf(DummyService::class, $instance);
    }

    public function testAutowiresRecursiveDependencies(): void
    {
        /** @var DependentService $instance */
        $instance = $this->assembler->get(DependentService::class);
        $this->assertInstanceOf(DependentService::class, $instance);
        $this->assertInstanceOf(DummyService::class, $instance->dummy);
    }

    public function testResolvesMappedInterface(): void
    {
        $this->assembler->setInterfaceMap([
            DummyInterface::class => MappedDummyImplementation::class,
        ]);

        $instance = $this->assembler->get(DummyInterface::class);
        $this->assertInstanceOf(MappedDummyImplementation::class, $instance);
    }

    public function testThrowsExceptionForUnresolvableService(): void
    {
        $this->expectException(RuntimeException::class);
        $this->assembler->get('NonExistingClass');
    }
}

interface DummyInterface {}
class MappedDummyImplementation implements DummyInterface {}
class DummyService {}
class DependentService
{
    public function __construct(public DummyService $dummy) {}
}
