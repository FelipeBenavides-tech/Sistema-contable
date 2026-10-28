<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'barberia_id',
        'barbero_id',
        'is_admin',
        'rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_admin'          => 'boolean',
    ];

    public function barberia(): BelongsTo
    {
        return $this->belongsTo(Barberia::class);
    }

    public function barbero(): BelongsTo
    {
        return $this->belongsTo(Barbero::class);
    }

    // ¿Es un barbero que solo ve su agenda?
    public function esBarbero(): bool
    {
        return $this->rol === 'barbero';
    }

    // ¿Es administrador del SaaS?
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    // ¿Tiene barbería asignada?
    public function tieneBarberia(): bool
    {
        return !is_null($this->barberia_id);
    }
}
