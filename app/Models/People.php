<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class People extends Model
{
    protected $fillable = [
        'full_name',
        'phone',
        'snils',
        'inn',
        'position',
        'birth_date',
        'address',
        'status',
        'passport_page_1',
        'passport_page_1_original_name',
        'passport_page_1_mime_type',
        'passport_page_1_size',
        'passport_page_5',
        'passport_page_5_original_name',
        'passport_page_5_mime_type',
        'passport_page_5_size',
        'photo',
        'photo_original_name',
        'photo_mime_type',
        'photo_size',
        'certificates_file',
        'certificates_file_original_name',
        'certificates_file_mime_type',
        'certificates_file_size'
    ];

    protected $casts = [
        'birth_date' => 'date'
    ];

    public function certificates(): BelongsToMany
    {
        return $this->belongsToMany(Certificate::class, 'people_certificates')
                    ->withPivot('assigned_date', 'certificate_number', 'status', 'notes')
                    ->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return $this->attributes['full_name'] ?? '';
    }
}
