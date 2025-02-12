<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wildside\Userstamps\Userstamps;

class Offer extends Model
{
    use HasFactory, Userstamps;

    public const FILE_QUEUE_LIMIT = 20;

    protected $fillable = [
        "name", "notes",
        "positions",
    ];

    protected $casts = [
        "positions" => "array",
    ];

    #region relations
    public function files()
    {
        return $this->hasMany(OfferFile::class);
    }
}
