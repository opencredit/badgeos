<?php

namespace BadgeOS;

use Illuminate\Database\Eloquent\Model;

class LogEntry extends Model {
    protected $table = 'badgeos_logs';
    public $timestamps = false;
}
