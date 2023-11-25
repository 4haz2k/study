<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participants extends Model
{
    protected $table = 'participants';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'chat_id';

    protected $fillable = [
        'chat_id',
        'subscribed'
    ];
}
