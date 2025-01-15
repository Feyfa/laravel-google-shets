<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignInformation extends Model
{
    use HasFactory;

    protected $table = 'campaign_informations';

    protected $fillable = [
        'name',
        'type',
        'description',
        'start_index',
        'end_index',
    ];
}
