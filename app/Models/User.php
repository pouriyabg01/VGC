<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable , HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * player's platform info
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function platforms()
    {
        return $this->hasMany(Platform::class);
    }

    public function plan()
    {
        return $this->belongsToMany(Plan::class ,
            'subscriptions' ,
            'user_id' ,
            'plan_id'
        )->withTimestamps()
            ->withPivot('status');
    }
    public function latestActiveSub()
    {
        return $this->hasOne(Subscription::class)
            ->where('status' ,'1')
            ->latestOfMany();
    }
    public function activeSub()
    {
        return $this->hasOne(Subscription::class)
            ->where('status' , '1');
    }

//    public function getLatestActiveSubAttribute()
//    {
//        return $this->activeSub()->latest('id')->first();
//    }

    public function tournaments()
    {
        return $this->belongsToMany(
            Tournament::class ,
            'tournament_user' ,
            'user_id' ,
            'tournament_id'
        )->withTimestamps();
    }

}
