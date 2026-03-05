<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Service\OpenAiService;

class ImageGenerationController extends Controller
{

    public function __construct(private OpenAiService $openAiService)
    {

    }
    public function index() 
    {

    }

    public function store(GeneratePromptRequest $request)
    {

        $user = $request->user();
        $image = $request->file('image');

        $originalName = $image->getClientOriginalName();
        $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $extension = $image->getClientOriginalExtension();
        $safeFilename = $sanitizedName . '_' . Str::random(10) . $extension;

        $imagePath = $image->storeAs('uploads/images', $safeFilename, 'public');

        $generatedPrompt = $this->OpenAiService->generatePromptFromImage($image);

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
