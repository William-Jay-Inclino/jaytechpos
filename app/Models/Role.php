<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * Protects against mass assignment attacks.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Example relationship:
     * A role can belong to many users (many-to-many).
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
