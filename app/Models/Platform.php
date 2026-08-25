<?php

namespace App\Models;

use App\Enums\Platforms\PlatformEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'nickname',
        'platform'
    ];

    protected $casts = [
        'platform' => PlatformEnum::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
