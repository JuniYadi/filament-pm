<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialiteUser extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
    ];

    /**
     * Get the user that owns the socialite account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
