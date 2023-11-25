<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participants extends Model
{
    protected $table = 'participants';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'chat_id',
        'subscribed'
    ];
}
