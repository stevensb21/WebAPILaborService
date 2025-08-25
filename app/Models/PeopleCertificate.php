<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeopleCertificate extends Model
{
    protected $table = 'people_certificates';

    protected $fillable = [
        'people_id',
        'certificate_id',
        'assigned_date',
        'certificate_number',
        'status',
        'notes',
        'certificate_file',
        'certificate_file_original_name',
        'certificate_file_mime_type',
        'certificate_file_size'
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'status' => 'integer'
    ];

    public function people(): BelongsTo
    {
        return $this->belongsTo(People::class);
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }
}
