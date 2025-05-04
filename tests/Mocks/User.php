<?php

namespace RagKit\Tests\Mocks;

use Illuminate\Foundation\Auth\User as Authenticatable;
use RagKit\Traits\HasRagAccounts;

class User extends Authenticatable
{
    use HasRagAccounts;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
} 