<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatFile extends Model
{
    use HasFactory;

    protected $fillable = ['chat_id', 'filename', 'original_name', 'file_path'];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }
}
