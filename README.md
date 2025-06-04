# Instapaper to Podcast - Orchestration

Orchestration and deployment tools for the Instapaper to Podcast microservices system.

## Overview

This repository provides Docker Compose orchestration for running the Instapaper to Podcast pipeline. The microservices are maintained in separate repositories:

- **[instapaper-fetcher](https://github.com/h13/instapaper-fetcher)** - Fetches bookmarks from Instapaper
- **[text-summarizer](https://github.com/h13/text-summarizer)** - Summarizes articles using OpenAI
- **[text-to-speech](https://github.com/h13/text-to-speech)** - Converts text to speech
- **[podcast-publisher](https://github.com/h13/podcast-publisher)** - Generates RSS podcast feeds

## Quick Start

1. Clone this repository:
```bash
git clone https://github.com/h13/instapaper-to-podcast.git
cd instapaper-to-podcast
```

2. Run the services:
```bash
docker compose up -d
```

The services will be available at:
- Instapaper Fetcher: http://localhost:8081
- Text Summarizer: http://localhost:8082
- Text-to-Speech: http://localhost:8083
- Podcast Publisher: http://localhost:8084

## Architecture

The system follows a pipeline architecture where each service processes data and passes it to the next via Google Cloud Storage:

```
Instapaper API → Fetcher → GCS → Summarizer → GCS → TTS → GCS → Publisher → Podcast Feed
```

## Scripts

- `setup.sh` - Initial setup and dependency installation
- `run-pipeline.sh` - Execute the complete pipeline
- `deploy.sh` - Deploy services to various platforms

## License

MIT License