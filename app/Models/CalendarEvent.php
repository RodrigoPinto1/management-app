<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entity_id',
        'calendar_type_id',
        'calendar_action_id',
        'start_at',
        'end_at',
        'duration_minutes',
        'shared',
        'knowledge',
        'description',
        'state',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'shared' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function entity()
    {
        return $this->belongsTo(\App\Models\Entity::class);
    }

    public function type()
    {
        return $this->belongsTo(CalendarType::class, 'calendar_type_id');
    }

    public function action()
    {
        return $this->belongsTo(CalendarAction::class, 'calendar_action_id');
    }
}
