<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    public $incrementing = true;

    public $timestamps = false;

    protected $table = 'schedule';

    protected $fillable = [
        'subject',
        'theme',
        'link',
        'created_at',
        'notified'
    ];
}
