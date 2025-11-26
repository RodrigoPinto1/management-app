<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'date',
        'valid_until',
        'entity_id',
        'status',
        'closed_at',
        'total',
    ];

    protected $casts = [
        'date' => 'date',
        'valid_until' => 'date',
        'closed_at' => 'datetime',
        'total' => 'decimal:2',
    ];

    public function lines()
    {
        return $this->hasMany(ProposalLine::class);
    }

    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }
}
