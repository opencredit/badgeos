<?php

namespace BadgeOS;

use Illuminate\Database\Eloquent\Model;

class LogEntry extends Model {
    protected $table = 'badgeos_logs';
    public $timestamps = false;

    public function scopeUser($query, $user_id) {
        return $query->where('user_id', $user_id);
    }
}
