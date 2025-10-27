<?php

namespace App\Models\List;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TissueSubcategory extends Model
{
    use HasFactory;

    protected $table = 'list_tissues_subcategory';

    protected $fillable = [
        'tissue_id',
        'name',
    ];

    /**
     * Get the parent tissue
     */
    public function tissue()
    {
        return $this->belongsTo(Tissue::class, 'tissue_id');
    }
}
