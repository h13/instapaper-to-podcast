#!/usr/bin/env php
<?php

declare(strict_types=1);

use BEAR\Package\Bootstrap;
use BEAR\Resource\ResourceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

require dirname(__DIR__) . '/vendor/autoload.php';

class SummarizeCommand extends Command
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
            ->setName('summarize')
            ->setDescription('Process texts from Cloud Storage and generate summaries')
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Number of texts to process',
                '10'
            )
            ->addOption(
                'list',
                null,
                InputOption::VALUE_NONE,
                'List existing summaries instead of processing'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = (int) $input->getOption('limit');
        $listOnly = $input->getOption('list');

        try {
            if ($listOnly) {
                $io->title('Listing Summaries');
                
                $response = $this->resource->get('/summaries', ['limit' => $limit]);
                $body = $response->body;
                
                if (!isset($body['summaries']) || empty($body['summaries'])) {
                    $io->info('No summaries found.');
                    return Command::SUCCESS;
                }
                
                $io->section("Found {$body['count']} summaries:");
                
                $rows = [];
                foreach ($body['summaries'] as $summary) {
                    $rows[] = [
                        $summary['bookmark_id'] ?? 'N/A',
                        $summary['title'] ?? 'N/A',
                        $summary['summarized_at'] ?? 'N/A'
                    ];
                }
                
                $io->table(['Bookmark ID', 'Title', 'Summarized At'], $rows);
            } else {
                $io->title('Processing Texts for Summarization');
                $io->info("Processing up to {$limit} texts...");
                
                $response = $this->resource->post('/summaries/process', ['limit' => $limit]);
                $body = $response->body;
                
                if ($body['success'] ?? false) {
                    $result = $body['result'];
                    
                    $io->success([
                        "Processed: {$result['processed']} texts",
                        "Failed: {$result['failed']} texts"
                    ]);
                    
                    if (!empty($result['errors'])) {
                        $io->error('The following errors occurred:');
                        foreach ($result['errors'] as $error) {
                            $io->writeln(" - {$error['file']}: {$error['error']}");
                        }
                    }
                } else {
                    $io->error('Failed to process texts: ' . ($body['error'] ?? 'Unknown error'));
                    return Command::FAILURE;
                }
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

// Bootstrap application
$app = (new Bootstrap())->getApp('TextSummarizer');
$resource = $app->resource;

// Create console application
$console = new Application('Text Summarizer CLI', '1.0.0');
$console->add(new SummarizeCommand($resource));
$console->setDefaultCommand('summarize', true);
$console->run();