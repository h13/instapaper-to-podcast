# Text Summarizer Service

BEAR.Sunday microservice that summarizes texts from Cloud Storage using OpenAI.

## Features

- Processes raw texts from Cloud Storage
- Generates summaries using OpenAI API
- Stores summarized texts back to Cloud Storage
- RESTful API endpoints
- CLI command for batch processing

## API Endpoints

- `GET /summaries` - List existing summaries
- `POST /summaries/process` - Process unprocessed texts

## Setup

1. Copy `.env.example` to `.env` and configure:
   ```bash
   cp .env.example .env
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Run the service:
   ```bash
   php -S localhost:8082 -t public/
   ```

## CLI Usage

Process texts:
```bash
php bin/summarize.php --limit=10
```

List summaries:
```bash
php bin/summarize.php --list --limit=20
```

## Docker

Build:
```bash
docker build -t text-summarizer .
```

Run:
```bash
docker run -p 8082:8082 --env-file .env text-summarizer
```

## Environment Variables

- `OPENAI_API_KEY` - OpenAI API key
- `OPENAI_MODEL` - Model to use (default: gpt-3.5-turbo)
- `OPENAI_MAX_TOKENS` - Max tokens for summaries (default: 500)
- `STORAGE_BUCKET_NAME` - GCS bucket name
- `GCP_PROJECT_ID` - Google Cloud project ID
- `GOOGLE_APPLICATION_CREDENTIALS` - Path to GCP credentials