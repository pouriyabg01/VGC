<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
    protected $fillable = ['title','description','price'];

    public function user()
    {
        return $this->belongsToMany(User::class ,
            'subscriptions' ,
            'plan_id' ,
            'user_id'
        )->withTimestamps()
            ->withPivot('status');
    }
}
