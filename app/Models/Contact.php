<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'last_name', 'phone'];

    protected $dates = ['deleted_at'];
    public function getFullNameAttribute()
    {
        return $this->name . ' ' . $this->last_name;
    }
}
