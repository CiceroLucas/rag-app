<?php

namespace App\Http\Controllers\Api;

use App\Ai\Agents\DocumentAskAgent;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Ai\Enums\Lab;

class DocumentAskController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:4000'],
        ]);

        $question = $validated['question'];
        $documents = Document::query()
            ->whereVectorSimilarTo('embedding', $question, minSimilarity: 0.35)
            ->limit(6)
            ->get(['source_file', 'chunk_index', 'content']);

        if ($documents->isEmpty()) {
            return response()->json([
                'answer' => 'Não encontrei trechos relevantes nos documentos indexados para responder.',
                'sources' => [],
            ]);
        }

        $context = $documents
            ->map(fn (Document $document) => sprintf(
                "[Arquivo: %s | trecho %d]\n%s",
                $document->source_file,
                $document->chunk_index + 1,
                $document->content,
            ))
            ->implode("\n\n---\n\n");

        $answer = (new DocumentAskAgent($context))->prompt(
            $question,
            provider: Lab::Ollama,
            model: config('ai.providers.ollama.models.text.default'),
        )->text;

        return response()->json([
            'answer' => $answer,
            'sources' => $documents
                ->pluck('source_file')
                ->unique()
                ->values(),
        ]);
    }
}
