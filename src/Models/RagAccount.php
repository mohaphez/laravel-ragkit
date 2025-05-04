<?php

namespace RagKit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class RagAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'name',
        'provider',
        'account_id',
        'settings',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the account.
     */
    public function user()
    {
        return $this->belongsTo(config('ragkit.user_model', 'App\\Models\\User'));
    }

    /**
     * Get the collections for this account.
     */
    public function collections()
    {
        return $this->hasMany(RagCollection::class, 'rag_account_id');
    }

    /**
     * Get all documents across all collections for this account.
     */
    public function documents()
    {
        return $this->hasManyThrough(
            RagDocument::class,
            RagCollection::class,
            'rag_account_id',
            'rag_collection_id'
        );
    }

    /**
     * Get the account ID, falling back to configuration if not set.
     */
    public function getExternalAccountId()
    {
        if (!empty($this->account_id)) {
            return $this->account_id;
        }

        return Config::get("ragkit.connections.{$this->provider}.account_id");
    }

    /**
     * Get the API key from configuration.
     */
    public function getApiKey()
    {
        return Config::get("ragkit.connections.{$this->provider}.api_key");
    }

    /**
     * Get accounts by provider.
     */
    public static function byProvider($provider)
    {
        return static::where('provider', $provider)
            ->where('is_active', true);
    }
} 