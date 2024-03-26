<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;
    protected $fillable = [
        'image', 'image_mime', 'image_size', 'status'
         
    ];
    protected $appends = ['img_url'];
    public function getImgUrlAttribute(){
        return asset('assets/img/banners/'.$this->image);
    }
}