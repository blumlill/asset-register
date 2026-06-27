<?php declare(strict_types=1);

namespace App\Core\Logging;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'api_logs';

    protected $fillable = [
        'method',
        'path',
        'request_headers',
        'request_body',
        'response_status',
        'response_body',
        'duration_ms',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_body' => 'array',
    ];
}
