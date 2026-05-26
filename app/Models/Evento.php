<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Evento extends Model
{
    protected $table = 'eventos';

    protected $fillable = [
        'tipo',
        'descripcion',
        'lat',
        'lng',
        'fecha',
        'user_id',
        'ir',
        'estado'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}