# Instapaper to Podcast

A microservices architecture implementation using BEAR.Sunday framework that converts Instapaper articles to a podcast feed.

## Overview

This project transforms your Instapaper bookmarks into a podcast feed through a pipeline of specialized microservices:
- Fetches articles from Instapaper
- Summarizes content using OpenAI
- Converts text to speech using Google Cloud TTS
- Publishes as an iTunes-compatible podcast RSS feed

## Architecture

The system is built with 4 independent microservices using BEAR.Sunday framework:

```
┌─────────────────────┐
│ instapaper-fetcher  │ → Fetches articles from Instapaper
└──────────┬──────────┘
           │
           ↓ (raw texts to Cloud Storage)
┌─────────────────────┐
│  text-summarizer    │ → Summarizes articles using OpenAI
└──────────┬──────────┘
           │
           ↓ (summaries to Cloud Storage)
┌─────────────────────┐
│  text-to-speech     │ → Converts summaries to audio
└──────────┬──────────┘
           │
           ↓ (audio files to Cloud Storage)
┌─────────────────────┐
│ podcast-publisher   │ → Generates RSS podcast feed
└─────────────────────┘
```

## Requirements

- PHP 8.3+
- Composer
- Google Cloud account with Text-to-Speech API enabled
- OpenAI API key
- Instapaper API credentials
- Google Cloud Storage buckets

## Quick Start

1. Clone the repository:
```bash
git clone https://github.com/h13/instapaper-to-podcast.git
cd instapaper-to-podcast
```

2. Install dependencies for each service:
```bash
cd microservices/instapaper-fetcher && composer install && cd ../..
cd microservices/text-summarizer && composer install && cd ../..
cd microservices/text-to-speech && composer install && cd ../..
cd microservices/podcast-publisher && composer install && cd ../..
```

3. Configure each service:
```bash
# Copy .env.json.example to .env.json in each service directory
# Configure with your API credentials
# Environment variables are validated using JSON Schema for type safety
```

4. Run the pipeline:
```bash
# Step 1: Fetch articles
cd microservices/instapaper-fetcher && php bin/fetch.php --limit=10

# Step 2: Generate summaries
cd ../../microservices/text-summarizer && php bin/summarize.php --limit=10

# Step 3: Create audio files
cd ../../microservices/text-to-speech && php bin/generate-audio.php --limit=10

# Step 4: Publish podcast
cd ../../microservices/podcast-publisher && php bin/publish.php
```

## Docker Support

Run all services with Docker Compose:
```bash
docker compose up
```

## Configuration

Each service requires specific environment variables. See `.env.example` in each service directory:

- **instapaper-fetcher**: Instapaper API credentials
- **text-summarizer**: OpenAI API key
- **text-to-speech**: Google TTS configuration
- **podcast-publisher**: Podcast metadata

## Development

Each service can be run independently for development:
```bash
# Terminal 1: Instapaper Fetcher
cd microservices/instapaper-fetcher
php -S localhost:8081 -t public/

# Terminal 2: Text Summarizer
cd microservices/text-summarizer
php -S localhost:8082 -t public/

# Terminal 3: Text-to-Speech
cd microservices/text-to-speech
php -S localhost:8083 -t public/

# Terminal 4: Podcast Publisher
cd microservices/podcast-publisher
php -S localhost:8084 -t public/
```

## API Documentation

Each service exposes RESTful endpoints:
- Instapaper Fetcher: `GET /bookmarks`, `POST /bookmarks/fetch`
- Text Summarizer: `GET /summaries`, `POST /summaries/process`
- Text-to-Speech: `GET /audio`, `POST /audio/generate`
- Podcast Publisher: `GET /feed`, `POST /feed/generate`

## Testing

Run tests for each service:
```bash
cd microservices/[service-name]
composer test
```

## Project Structure

```
.
├── microservices/
│   ├── instapaper-fetcher/    # Fetches articles from Instapaper
│   ├── text-summarizer/       # Summarizes articles using OpenAI
│   ├── text-to-speech/        # Converts text to audio
│   ├── podcast-publisher/     # Generates podcast RSS feed
│   └── common/               # Shared components and contracts
├── scripts/                   # Deployment and utility scripts
├── compose.yml               # Docker Compose configuration
└── README.md                 # This file
```

## License

MIT