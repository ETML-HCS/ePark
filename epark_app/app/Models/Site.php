<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = [
        'nom',
        'adresse',
        'user_id',
    ];

    public function proprietaire()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
