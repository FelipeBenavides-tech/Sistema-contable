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
        'is_admin',
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
