<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\FileTextExtractor;
use App\Services\TextChunker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Enums\Lab;
use RuntimeException;

class DocumentUploadController extends Controller
{
    public function store(Request $request, FileTextExtractor $extractor): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,txt,md', 'max:20480'],
        ]);

        $file = $validated['file'];

        try {
            $text = $extractor->extract($file);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        $chunks = TextChunker::chunk($text);
        $embeddingModel = config('ai.providers.ollama.models.embeddings.default');
        $embeddings = Embeddings::for($chunks)
            ->generate(Lab::Ollama, $embeddingModel)
            ->embeddings;

        if (count($embeddings) !== count($chunks)) {
            throw new RuntimeException('Ollama retornou uma quantidade inesperada de embeddings.');
        }

        foreach ($embeddings as $embedding) {
            if (count($embedding) !== 768) {
                throw new RuntimeException('O modelo de embeddings precisa retornar vetores com 768 dimensões.');
            }
        }

        $sourceFile = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $sourceFile = Str::limit($sourceFile, 255, '');

        DB::transaction(function () use ($chunks, $embeddings, $sourceFile): void {
            foreach ($chunks as $index => $chunk) {
                Document::create([
                    'source_file' => $sourceFile,
                    'chunk_index' => $index,
                    'content' => $chunk,
                    'embedding' => $embeddings[$index],
                ]);
            }
        });

        return response()->json([
            'message' => 'Documento processado e indexado com sucesso.',
            'source_file' => $sourceFile,
            'chunks_created' => count($chunks),
        ], 201);
    }
}
