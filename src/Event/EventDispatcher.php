<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Event;

use Psr\Container\ContainerInterface;

final class EventDispatcher
{
    /** @var array<string, list<class-string>> */
    private array $listeners = [];

    public function __construct(private readonly ContainerInterface $container) {}

    /**
     * @param class-string $eventClass
     * @param class-string $listenerClass
     */
    public function addListener(string $eventClass, string $listenerClass): void
    {
        $this->listeners[$eventClass][] = $listenerClass;
    }

    public function dispatch(object $event): object
    {
        foreach ($this->listeners[$event::class] ?? [] as $listenerClass) {
            /** @var object $listener */
            $listener = $this->container->get($listenerClass);
            if (method_exists($listener, 'handle')) {
                $listener->handle($event);
            }
        }

        return $event;
    }
}
