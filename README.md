# Laravel OpenAI Image Prompt Generator API

This repository contains a **Laravel 12** API that lets you upload an image and automatically generate a **high‑quality textual prompt** using **OpenAI GPT‑4o with vision capabilities**.  
The API is designed to be a solid starting point for AI‑assisted content tools, supporting **authentication**, **versioned REST endpoints**, and **image metadata storage**.

---

## High‑level architecture

The diagram below shows how a request flows through the system:

```mermaid
flowchart LR
    C[Client (Web / Mobile / Postman)] -->|HTTP request| A[Laravel 12 API]

    subgraph API[Laravel App]
        A -->|Sanctum auth, validation| U[(Users & Tokens)]
        A -->|Store file| S[(Storage disk)]
        A -->|Call OpenAI client| O[OpenAI GPT‑4o]
        O -->|Prompt text| A
        A -->|JSON response| C
        A -->|Create record| D[(Database)]
    end
```

- **Client**: Any HTTP client (SPA, mobile app, Postman, curl, etc.).
- **Laravel API**: Handles routing, validation, authentication (via Sanctum), and calls the OpenAI PHP client.
- **Storage**: Receives the sanitized uploaded image and stores it under `public` storage.
- **Database**: Stores an `image_generations` record associated with the user (path, prompt, filename, size, MIME type).
- **OpenAI GPT‑4o**: Analyzes the uploaded image and returns a rich, descriptive prompt.

---

## Features

- **Image → Prompt generation**
  - Upload an image and receive a **detailed, ready‑to‑use prompt** that describes:
    - Style, lighting, composition, subject, colors, and important visual details.
  - Uses `gpt-4o` via the official `openai-php/client` package.

- **Sanctum‑based authentication**
  - Registration and login endpoints that issue API tokens.
  - Authenticated routes grouped under middleware `auth:sanctum`.

- **Versioned REST API**
  - `v1` namespace for `posts` resource.
  - `image-generations` resource for image prompt generation and simple health check.

- **Robust validation for images**
  - Dedicated `GeneratePromptRequest` form request:
    - Validates uploaded file is an image.
    - Restricts MIME types: `jpeg`, `png`, `jpg`, `gif`, `svg`.
    - Enforces file size limit and basic dimension checks.

- **Modern Laravel 12 stack**
  - PHP 8.2+, Laravel 12, Pest 3 for testing.
  - Laravel Sanctum, Tinker, Pint, Sail, and Boost pre‑configured.

---

## API overview

All routes are prefixed by `/api` (Laravel’s default).  
Below is a high‑level summary of the most important endpoints.

### Authentication

- `POST /api/login`
- `POST /api/register`
- `POST /api/logout` (protected with `auth:sanctum`)
- `GET /api/user` (protected with `auth:sanctum`)

### Posts (v1)

Inside the authenticated `auth:sanctum` group:

- `GET    /api/v1/posts`
- `GET    /api/v1/posts/{id}`
- `POST   /api/v1/posts`
- `PUT    /api/v1/posts/{id}`
- `PATCH  /api/v1/posts/{id}`
- `DELETE /api/v1/posts/{id}`

> Note: There is both a versioned `apiResource('posts')` and additional explicit routes under `v1`. Treat `v1` as the main versioned API namespace.

### Image prompt generation

Public `apiResource` (index and store only):

- `GET  /api/image-generations`  
  Simple health/status endpoint. Returns a JSON message confirming the API is working.

- `POST /api/image-generations`  
  Accepts a single image file and returns a JSON payload with:
  - Stored image path
  - Generated textual prompt from OpenAI
  - Original filename
  - File size
  - MIME type

**Request (multipart/form-data):**

- Field: `image` – required image file (`jpeg`, `jpg`, `png`, `gif`, or `svg`), within size and dimension limits defined in `GeneratePromptRequest`.

---

## Getting started

### Prerequisites

- **PHP**: ^8.2
- **Composer**
- **Node.js & npm**
- **SQLite / MySQL / Postgres** (any Laravel‑supported database)
- **OpenAI API key**

### Installation

1. **Clone the repository**

   ```bash
   git clone <your-repo-url> laravel-api
   cd laravel-api
   ```

2. **Install PHP dependencies**

   ```bash
   composer install
   ```

3. **Create `.env` and generate app key**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure your database**  
   Update the database settings in `.env` (for example, `DB_CONNECTION`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

5. **Run migrations**

   ```bash
   php artisan migrate
   ```

6. **Install frontend / build assets**

   ```bash
   npm install
   npm run build
   ```

> Tip: There is also a convenience Composer script `"setup"` that chains many of these steps:
> 
> ```bash
> composer setup
> ```

### Running the app in development

You can either run services manually or use the provided `dev` script.

- **Manual**

  ```bash
  php artisan serve
  # In a separate terminal:
  npm run dev
  # (and optionally: php artisan queue:listen)
  ```

- **Using the Composer dev script**

  ```bash
  composer dev
  ```

This uses `concurrently` to run:

- `php artisan serve`
- `php artisan queue:listen --tries=1`
- `npm run dev`

---

## Environment configuration

The OpenAI configuration lives in `config/services.php`:

```php
'openai' => [
    'key' => env('OPENAI_API_KEY'),
],
```

Set your key in `.env`:

```env
OPENAI_API_KEY=sk-...
```

The `OpenAiService` uses this configuration to build the OpenAI client:

- Encodes the uploaded image as base64.
- Sends both **text instructions** and an **image URL** (data URI) to the `gpt-4o` model.
- Returns the generated prompt text back to the controller.

---

## Image generation flow (step by step)

1. **Client uploads an image**
   - Calls `POST /api/image-generations` with a multipart `image` field.

2. **Validation**
   - `GeneratePromptRequest` ensures:
     - The file is present.
     - It is a valid image and matches the allowed MIME types.
     - It meets size and dimension constraints.

3. **Safe file storage**
   - The original filename is sanitized (non‑alphanumeric characters replaced with `_`).
   - A random suffix is appended to avoid collisions.
   - File is stored at `storage/app/public/uploads/images/...` via the `public` disk.

4. **OpenAI analysis**
   - `OpenAiService::generatePromptFromImage()`:
     - Encodes the image in base64.
     - Calls OpenAI `chat()->create()` with the `gpt-4o` model.
     - Asks for a detailed prompt that could recreate a similar image in an AI image generator.

5. **Database record**
   - The authenticated user’s `imageGenerations()` relationship creates a record containing:
     - `image_path`
     - `generated_prompt`
     - `original_filename`
     - `file_size`
     - `mime_type`

6. **JSON response**
   - The API returns the created record as JSON with HTTP status `201 Created`.

---

## Example request (curl)

```bash
curl -X POST http://localhost:8000/api/image-generations \
  -H "Accept: application/json" \
  -F "image=@/absolute/path/to/your-image.png"
```

Example truncated response:

```json
{
  "id": 1,
  "image_path": "uploads/images/my_image_abCDeFg123.png",
  "generated_prompt": "A highly detailed prompt describing style, lighting, composition, colors, subject, and key visual elements...",
  "original_filename": "my_image.png",
  "file_size": 123456,
  "mime_type": "image/png",
  "created_at": "2026-03-06T12:00:00.000000Z",
  "updated_at": "2026-03-06T12:00:00.000000Z"
}
```

---

## Testing

This project is configured to use **Pest**:

```bash
php artisan test --compact
```

You can also use the Composer `test` script:

```bash
composer test
```

---

## License

This project is open‑sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

