<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TopNavPage extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'ordering', "show_in_top_nav", 'content'];

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy("ordering")
            ->orderBy("id");
    }

    public function getSlugAttribute(): string
    {
        return Str::slug($this->name);
    }
}
