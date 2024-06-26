<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Books extends Model
{
    use HasFactory;
    protected $fillable = [
        "books_id",
        'books',
        'agama',
        "API_Gateaway",
        "api_key"
    ];
}
