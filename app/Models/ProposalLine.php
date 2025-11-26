<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_id',
        'article_id',
        'reference',
        'name',
        'description',
        'quantity',
        'unit_price',
        'cost_price',
        'supplier_id',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Entity::class, 'supplier_id');
    }
}
