<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'location',
        'latitude',
        'longitude',
        'gender',
        'profession',
        'date_of_birth',
        'phone',
        'instagram',
        'email_verified_at',
        'otp',
        'otp_type',
        'otp_validity',
        'otp_is_used',
        'vaccinated',
        'profile',
        'password',
        'is_calling',
        'agora_channel',
        'agora_token',
        'fcm_token',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pivot',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function cuisines(){
        return $this->belongsToMany(Cuisine::class,'user_cuisines','user_id','cuisine_id')
            ->select('cuisines.id', 'cuisines.name', 'cuisines.image');
    }
    public function interests(){
        return $this->belongsToMany(Interest::class,'user_interests','user_id','interest_id')
            ->select('interests.id', 'interests.name', 'interests.image');
    }
    public function virtual_date_from(){
        return $this->hasOne(VirtualDate::class, 'from_id', 'id');
    }
    public function virtual_date_to(){
        return $this->hasOne(VirtualDate::class, 'to_id', 'id');

    }
    public function heart_request(){
        return $this->hasMany(UserHeart::class, 'requesting_person_from', 'id');
    }

}
