<?php

namespace RagKit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class UserRagAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'provider',
        'collection_name',
        'collection_id',
        'api_key',
        'account_id',
        'collection_metadata',
        'settings',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'collection_metadata' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the RAG account.
     */
    public function user()
    {
        return $this->belongsTo(config('ragkit.user_model', 'App\\Models\\User'));
    }

    /**
     * Get the documents associated with this account.
     */
    public function documents()
    {
        return $this->hasMany(RagDocument::class);
    }

    /**
     * Get the API key, falling back to configuration if not set.
     *
     * @return string
     */
    public function getApiKey()
    {
        if (! empty($this->api_key)) {
            return $this->api_key;
        }

        return Config::get("ragkit.connections.{$this->provider}.api_key");
    }

    /**
     * Get the account ID, falling back to configuration if not set.
     *
     * @return string
     */
    public function getAccountId()
    {
        if (! empty($this->account_id)) {
            return $this->account_id;
        }

        return Config::get("ragkit.connections.{$this->provider}.account_id");
    }

    /**
     * Get accounts by provider.
     *
     * @param  string  $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function byProvider($provider)
    {
        return static::where('provider', $provider)
            ->where('is_active', true);
    }
} 