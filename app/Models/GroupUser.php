<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\Traits\LogsActivity;

final class GroupUser extends Pivot
{
    use HasFactory, LogsActivity;

    public $incrementing = true;

    protected $table 	= 'group_user';
    protected $fillable = ['user_id', 'group_id', 'group_role', 'accepted_at', 'note'];
    protected $dates = ['created_at','updated_at'];

    // Activity Log
    protected static $logFillable = true;
    protected static $submitEmptyLogs = false;
    protected static $logOnlyDirty = true;
    protected static $logName = 'groupUser';
}