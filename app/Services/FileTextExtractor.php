<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

class FileTextExtractor
{
    /**
     * Extract searchable text from a supported upload.
     *
     * Supported formats are PDF, plain text, and Markdown.
     */
    public function extract(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $text = match ($extension) {
            'pdf' => $this->extractPdf($file->getRealPath()),
            'txt', 'md' => $this->extractPlainText($file->getRealPath()),
            default => throw new RuntimeException('Envie um arquivo PDF, TXT ou Markdown.'),
        };

        $text = trim(preg_replace('/\R/u', "\n", str_replace("\0", '', $text)) ?? $text);

        if ($text === '') {
            throw new RuntimeException('O arquivo não contém texto extraível.');
        }

        return $text;
    }

    private function extractPdf(string|false $path): string
    {
        if ($path === false) {
            throw new RuntimeException('Não foi possível acessar o arquivo enviado.');
        }

        try {
            return (new Parser)->parseFile($path)->getText();
        } catch (Throwable $exception) {
            throw new RuntimeException('Não foi possível extrair texto do PDF enviado.', previous: $exception);
        }
    }

    private function extractPlainText(string|false $path): string
    {
        if ($path === false) {
            throw new RuntimeException('Não foi possível acessar o arquivo enviado.');
        }

        $text = file_get_contents($path);

        if ($text === false) {
            throw new RuntimeException('Não foi possível ler o arquivo enviado.');
        }

        return $text;
    }
}
