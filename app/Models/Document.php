<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsVector;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['source_file', 'chunk_index', 'content', 'embedding'];

    protected function casts(): array
    {
        return [
            'embedding' => AsVector::class,
        ];
    }
}
