<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    use HasFactory;

    protected $table = 'entities';

    protected $fillable = [
        'number',
        'is_client',
        'is_supplier',
        'nif',
        'name',
        'address',
        'postal_code',
        'city',
        'country_id',
        'phone',
        'mobile',
        'website',
        'email',
        'rgpd_consent',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_client' => 'boolean',
        'is_supplier' => 'boolean',
        'rgpd_consent' => 'boolean',
        'is_active' => 'boolean',
    ];
}
