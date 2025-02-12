<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class OfferFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_id',
        'type',
        'file_path',
    ];

    public const WORKER_DELAY_MINUTES = 5;

    #region scopes
    public function scopePrepareQueue($query)
    {
        return $query->whereNull("file_path");
    }
    #endregion

    #region relations
    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
    #endregion

    #region attributes
    public function file(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::disk("public")->get($this->file_path),
        );
    }
    #endregion
}
