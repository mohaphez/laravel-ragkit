<?php

namespace RagKit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RagCollection extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'rag_account_id',
        'name',
        'collection_id',
        'collection_metadata',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'collection_metadata' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the account that owns the collection.
     */
    public function account()
    {
        return $this->belongsTo(RagAccount::class, 'rag_account_id');
    }

    /**
     * Get the documents for this collection.
     */
    public function documents()
    {
        return $this->hasMany(RagDocument::class, 'rag_collection_id');
    }

    /**
     * Get the user that indirectly owns this collection through the account.
     */
    public function user()
    {
        return $this->hasOneThrough(
            config('ragkit.user_model', 'App\\Models\\User'),
            RagAccount::class,
            'id',
            'id',
            'rag_account_id',
            'user_id'
        );
    }
} 