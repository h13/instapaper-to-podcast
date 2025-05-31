<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Domain\Shared;

use InstapaperToPodcast\Domain\Shared\Event\DomainEvent;

/**
 * Base class for aggregate roots
 */
abstract class AggregateRoot
{
    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    final protected function raise(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return list<DomainEvent>
     * @psalm-suppress PossiblyUnusedMethod
     */
    final public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
