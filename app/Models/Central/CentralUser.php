<?php

declare(strict_types=1);

namespace App\Models\Central;

use Database\Factories\Central\CentralUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class CentralUser extends Authenticatable
{
    /** @use HasFactory<CentralUserFactory> */
    use HasFactory, Notifiable;

    protected static function newFactory(): CentralUserFactory
    {
        return CentralUserFactory::new();
    }

    protected $table = 'central_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_super_admin' => 'boolean',
        'password' => 'hashed',
    ];
}
