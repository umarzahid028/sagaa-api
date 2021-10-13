<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHeart extends Model
{
    use HasFactory;
    public function user(){
        return $this->hasOne(User::class, 'id', 'requesting_person_to');
    }
    public function requested_heart(){
        return $this->hasOne(User::class, 'id', 'requesting_person_from');
    }
}
