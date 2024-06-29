<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopNavPage extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'ordering', 'content'];

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy("ordering")
            ->orderBy("id");
    }
}
