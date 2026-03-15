<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'User'; // Explicitly map to the User table (case-sensitive)

    protected $fillable = [
        'name',
        'email',
        'password',
        'portal_role',
        'createdAt',
        'updatedAt',
        // Add other customer-specific fields as needed
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
