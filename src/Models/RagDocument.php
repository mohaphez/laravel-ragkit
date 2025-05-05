<?php

namespace RagKit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RagDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'rag_collection_id',
        'uuid',
        'document_id',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'status',
        'status_message',
        'metadata',
        'external_metadata',
        'outlines',
        'faqs',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'external_metadata' => 'array',
        'outlines' => 'array',
        'faqs' => 'array',
        'file_size' => 'integer',
        'processed_at' => 'datetime',
    ];

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            if (empty($document->uuid)) {
                $document->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the collection that owns the document.
     */
    public function collection()
    {
        return $this->belongsTo(RagCollection::class, 'rag_collection_id');
    }

    /**
     * Get the account that owns this document through the collection.
     */
    public function account()
    {
        return $this->hasOneThrough(
            RagAccount::class,
            RagCollection::class,
            'id',
            'id',
            'rag_collection_id',
            'rag_account_id'
        );
    }

    /**
     * Get the user that owns this document through the collection and account.
     */
    public function user()
    {
        return $this->hasOneThrough(
            config('ragkit.user_model', 'App\\Models\\User'),
            RagAccount::class,
            'id',
            'id',
            'rag_collection_id',
            'user_id'
        );
    }
} 