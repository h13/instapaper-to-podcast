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

class GenerateAudioCommand extends Command
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
            ->setName('generate')
            ->setDescription('Generate audio files from summarized texts')
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Number of summaries to process',
                '10'
            )
            ->addOption(
                'list',
                null,
                InputOption::VALUE_NONE,
                'List existing audio files instead of generating'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = (int) $input->getOption('limit');
        $listOnly = $input->getOption('list');

        try {
            if ($listOnly) {
                $io->title('Listing Audio Files');
                
                $response = $this->resource->get('/audio', ['limit' => $limit]);
                $body = $response->body;
                
                if (!isset($body['audio_files']) || empty($body['audio_files'])) {
                    $io->info('No audio files found.');
                    return Command::SUCCESS;
                }
                
                $io->section("Found {$body['count']} audio files:");
                
                $rows = [];
                foreach ($body['audio_files'] as $audio) {
                    $rows[] = [
                        $audio['bookmark_id'] ?? 'N/A',
                        $audio['title'] ?? 'N/A',
                        $this->formatDuration($audio['duration'] ?? 0),
                        $this->formatBytes($audio['size'] ?? 0),
                        $audio['created_at'] ?? 'N/A'
                    ];
                }
                
                $io->table(['Bookmark ID', 'Title', 'Duration', 'Size', 'Created At'], $rows);
            } else {
                $io->title('Generating Audio Files');
                $io->info("Processing up to {$limit} summaries...");
                
                $response = $this->resource->post('/audio/generate', ['limit' => $limit]);
                $body = $response->body;
                
                if ($body['success'] ?? false) {
                    $result = $body['result'];
                    
                    $io->success([
                        "Processed: {$result['processed']} summaries",
                        "Failed: {$result['failed']} summaries"
                    ]);
                    
                    if (!empty($result['errors'])) {
                        $io->error('The following errors occurred:');
                        foreach ($result['errors'] as $error) {
                            $io->writeln(" - {$error['file']}: {$error['error']}");
                        }
                    }
                } else {
                    $io->error('Failed to generate audio: ' . ($body['error'] ?? 'Unknown error'));
                    return Command::FAILURE;
                }
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return "{$minutes}m {$remainingSeconds}s";
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
$app = (new Bootstrap())->getApp('TextToSpeech');
$resource = $app->resource;

// Create console application
$console = new Application('Text-to-Speech CLI', '1.0.0');
$console->add(new GenerateAudioCommand($resource));
$console->setDefaultCommand('generate', true);
$console->run();