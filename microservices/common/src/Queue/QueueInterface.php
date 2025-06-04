<?php

declare(strict_types=1);

namespace Common\Queue;

interface QueueInterface
{
    /**
     * Publish a message to the queue
     */
    public function publish(string $queue, array $message): void;
    
    /**
     * Consume messages from the queue
     */
    public function consume(string $queue, callable $callback): void;
    
    /**
     * Acknowledge a message
     */
    public function ack(mixed $message): void;
    
    /**
     * Reject a message and optionally requeue it
     */
    public function reject(mixed $message, bool $requeue = true): void;
}