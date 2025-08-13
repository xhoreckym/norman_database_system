<?php

namespace App\Models\SLE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuspectListExchangeSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'link_full_list',
        'link_inchikey_list',
        'link_references',
    ];

    // public function getNameAttribute($value)
    // {
    //     return $this->attributes['code']. '-'. $this->attributes['name'];
    // }
}
