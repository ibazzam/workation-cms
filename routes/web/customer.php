<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $connection = null;

    protected $table = 'User'; // Explicitly map to the User table (case-sensitive)
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

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

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $configuredConnection = trim((string) env('CUSTOMER_DB_CONNECTION', ''));
        if ($configuredConnection !== '') {
            $this->setConnection($configuredConnection);
        }
    }
}