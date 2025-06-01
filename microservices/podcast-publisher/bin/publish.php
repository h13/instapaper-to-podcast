#!/usr/bin/env php
<?php

declare(strict_types=1);

use BEAR\Package\Bootstrap;
use BEAR\Resource\ResourceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

require dirname(__DIR__) . '/vendor/autoload.php';

class PublishCommand extends Command
{
    private ResourceInterface $resource;

    public function __construct(ResourceInterface $resource)
    {
        parent::__construct();
        $this->resource = $resource;
    }

    protected function configure(): void
    {
        $this
            ->setName('publish')
            ->setDescription('Generate and publish podcast feed')
            ->addOption(
                'info',
                'i',
                InputOption::VALUE_NONE,
                'Show feed info instead of generating'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $infoOnly = $input->getOption('info');

        try {
            if ($infoOnly) {
                $io->title('Podcast Feed Information');
                
                $response = $this->resource->get('/feed');
                $body = $response->body;
                
                if (!isset($body['feed'])) {
                    $io->error('Failed to get feed information');
                    return Command::FAILURE;
                }
                
                $feed = $body['feed'];
                
                if ($feed['exists']) {
                    $io->section('Current Feed Status');
                    $io->listing([
                        sprintf('Episodes: %d', $feed['episodes']),
                        sprintf('Last Updated: %s', $feed['last_updated'] ?? 'Unknown'),
                        sprintf('Size: %s', $this->formatBytes($feed['size'] ?? 0)),
                        sprintf('URL: %s', $feed['url'] ?? 'N/A')
                    ]);
                } else {
                    $io->warning('No podcast feed exists yet. Run without --info to generate one.');
                }
            } else {
                $io->title('Generating Podcast Feed');
                $io->info('Collecting audio files and generating RSS feed...');
                
                $response = $this->resource->post('/feed/generate');
                $body = $response->body;
                
                if ($body['success'] ?? false) {
                    $result = $body['result'];
                    
                    if ($result['generated']) {
                        $io->success([
                            sprintf('Podcast feed generated with %d episodes', $result['episodes']),
                            sprintf('Feed URL: %s', $result['url'])
                        ]);
                    } else {
                        $io->warning('No audio files found. Generate audio files first.');
                    }
                } else {
                    $io->error('Failed to generate podcast feed: ' . ($body['error'] ?? 'Unknown error'));
                    return Command::FAILURE;
                }
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = $bytes;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}

// Bootstrap application
$app = (new Bootstrap())->getApp('PodcastPublisher');
$resource = $app->resource;

// Create console application
$console = new Application('Podcast Publisher CLI', '1.0.0');
$console->add(new PublishCommand($resource));
$console->setDefaultCommand('publish', true);
$console->run();