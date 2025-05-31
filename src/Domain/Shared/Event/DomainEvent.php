<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Domain\Shared\Event;

/**
 * Base interface for domain events
 */
interface DomainEvent
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getAggregateId(): string;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getOccurredAt(): \DateTimeImmutable;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getEventName(): string;

    /**
     * @return array<string, mixed>
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function toArray(): array;
}
