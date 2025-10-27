<?php

namespace App\Models\Literature;

use App\Models\List\TissueSubcategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tissue extends Model
{
    use HasFactory;

    protected $table = 'list_tissues';

    protected $fillable = [
        'name',
    ];

    /**
     * Get the subcategories for this tissue
     */
    public function subcategories()
    {
        return $this->hasMany(TissueSubcategory::class, 'tissue_id');
    }
}
