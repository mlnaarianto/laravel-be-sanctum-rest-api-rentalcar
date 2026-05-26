<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['user_id', 'phone', 'birth_date', 'address', 'ktp_image'])]
class PersonalData extends Model
{
    protected function casts(): array
    {
        return [
            'birth_date' => 'date', 
        ];
    }

    /**
     * Relasi kembali ke User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    
}