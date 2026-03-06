<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Service\OpenAiService;
use App\Http\Requests\GeneratePromptRequest;

class ImageGenerationController extends Controller
{
    private OpenAiService $openAiService;

    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
    }

    public function index()
    {
        return response()->json([
            'message' => 'Image generation API working'
        ]);
    }

    public function store(GeneratePromptRequest $request)
    {
        $user = $request->user();
        $image = $request->file('image');

        if (!$image) {
            return response()->json([
                'error' => 'No image uploaded'
            ], 400);
        }

        $originalName = $image->getClientOriginalName();

        $sanitizedName = preg_replace(
            '/[^a-zA-Z0-9._-]/',
            '_',
            pathinfo($originalName, PATHINFO_FILENAME)
        );

        $extension = $image->getClientOriginalExtension();

        $safeFilename = $sanitizedName . '_' . Str::random(10) . '.' . $extension;

        $imagePath = $image->storeAs('uploads/images', $safeFilename, 'public');

        $generatedPrompt = $this->openAiService->generatePromptFromImage($image);

        $imageGeneration = $user->imageGenerations()->create([
            'image_path' => $imagePath,
            'generated_prompt' => $generatedPrompt,
            'original_filename' => $originalName,
            'file_size' => $image->getSize(),
            'mime_type' => $image->getMimeType(),
        ]);

        return response()->json($imageGeneration, 201);
    }
}