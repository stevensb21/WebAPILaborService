<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Certificate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'expiry_date'
    ];

    protected $casts = [
        'expiry_date' => 'integer'
    ];

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(People::class, 'people_certificates')
                    ->withPivot('assigned_date', 'certificate_number', 'status', 'notes')
                    ->withTimestamps();
    }

    /**
     * Связь с порядком сертификата
     */
    public function order(): HasOne
    {
        return $this->hasOne(CertificateOrder::class);
    }

 

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            1 => 'Активный',
            2 => 'Скоро просрочится',
            3 => 'Просрочен',
            4 => 'Отсутствует',
            default => 'Неизвестно'
        };
    }
}
