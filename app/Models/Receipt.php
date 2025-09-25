<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;

    const PRINTED = 'printed';
    const REPRINTED = 'reprinted';
    const FAILED = 'failed';

    protected $fillable = [
        'sale_id',
        'receipt_number',
        'print_status',
    ];

    /**
     * A receipt belongs to a sale.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
