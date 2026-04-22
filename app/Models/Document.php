<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'original_filename',
        'stored_filename',
        'file_path',
        'file_size',
        'document_title',
        'document_author',
        'document_creator',
        'document_producer',
        'pdf_created_at',
        'pdf_modified_at',
        'uploaded_by',
    ];

    protected $casts = [
        'pdf_created_at' => 'datetime',
        'pdf_modified_at' => 'datetime',
    ];

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        return number_format($bytes / 1024, 2) . ' KB';
    }
}
