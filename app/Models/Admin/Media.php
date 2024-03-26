<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;
    protected $fillable = [
        'blog_id', 'media'
    ];
    protected $appends = ['media_url'];

    public function blog()
    {
        return $this->belongsTo(Blog::class, 'blog_id');
    }

    public function getMediaUrlAttribute(){
        return asset('assets/img/blogs/'.$this->media);
    }
}
