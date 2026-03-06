<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use OpenAI\Factory;

class OpenAiService
{
    public function generatePromptFromImage(UploadedFile $image): string
    {
        // Convert image to base64
        $imageData = base64_encode(file_get_contents($image->getPathname()));
        $mimeType = $image->getMimeType();

        // Create OpenAI client
        $client = (new Factory())
            ->withApiKey(config('services.openai.key'))
            ->make();

        // Send request to OpenAI
        $response = $client->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Analyze this image and generate a detailed prompt that could recreate a similar image using AI image generation tools. Describe style, lighting, composition, colors, subject, and important visual details. Preserve the original aspect ratio.'
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:' . $mimeType . ';base64,' . $imageData,
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        return $response->choices[0]->message->content;
    }
}