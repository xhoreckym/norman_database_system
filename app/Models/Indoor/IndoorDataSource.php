<?php

declare(strict_types=1);

namespace App\Models\Indoor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndoorDataSource extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'indoor_data_source';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id_data';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'dts_id',
        'title_project',
        'organisation',
        'email',
        'laboratory_name',
        'laboratory_id',
        'literature1',
        'literature2',
        'author',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'dts_id' => 'integer',
    ];

    /**
     * Get the type of data source lookup.
     */
    public function typeOfDataSource(): BelongsTo
    {
        return $this->belongsTo(IndoorDataDts::class, 'dts_id', 'id');
    }
}
