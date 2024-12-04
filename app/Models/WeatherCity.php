<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherCity extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'city',
        'country',
        'current_weather',
        'forecast_weather',
        'last_try'
    ];

    protected $casts = [
        'current_weather' => 'json',
        'forecast_weather' => 'json',
        'last_try' => 'datetime'
    ];

    public function groups()
    {
        return $this->hasMany(Group::class);
    }
}
