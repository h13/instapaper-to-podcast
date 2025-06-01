#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use TextSummarizer\Bootstrap;

// Bootstrap application
$app = Bootstrap::getApp('cli-hal-app');
$injector = $app->getInjector();

// Get services
$summarizer = $injector->getInstance(TextSummarizer\Service\SummarizationService::class);
$logger = $injector->getInstance(Psr\Log\LoggerInterface::class);

// RabbitMQ connection
$connection = new AMQPStreamConnection(
    $_ENV['RABBITMQ_HOST'] ?? 'localhost',
    $_ENV['RABBITMQ_PORT'] ?? 5672,
    $_ENV['RABBITMQ_USER'] ?? 'guest',
    $_ENV['RABBITMQ_PASS'] ?? 'guest'
);

$channel = $connection->channel();
$channel->queue_declare('summarization_queue', false, true, false, false);

$logger->info('Text Summarizer Queue Worker started. Waiting for messages...');

$callback = function (AMQPMessage $msg) use ($summarizer, $logger, $channel) {
    $data = json_decode($msg->body, true);
    $logger->info('Processing message', ['bookmark_id' => $data['bookmark_id']]);
    
    try {
        // Process the summarization
        $result = $summarizer->processSingle($data['bookmark_id']);
        
        // Acknowledge the message
        $channel->basic_ack($msg->delivery_info['delivery_tag']);
        
        // Publish to next queue
        $nextMessage = new AMQPMessage(json_encode([
            'bookmark_id' => $data['bookmark_id'],
            'summary_path' => $result['path']
        ]));
        $channel->basic_publish($nextMessage, '', 'tts_queue');
        
        $logger->info('Successfully processed', ['bookmark_id' => $data['bookmark_id']]);
    } catch (\Exception $e) {
        $logger->error('Processing failed', [
            'bookmark_id' => $data['bookmark_id'],
            'error' => $e->getMessage()
        ]);
        
        // Reject and requeue
        $channel->basic_reject($msg->delivery_info['delivery_tag'], true);
    }
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('summarization_queue', '', false, false, false, false, $callback);

// Keep the worker running
while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();