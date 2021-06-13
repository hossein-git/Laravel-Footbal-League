<?php

namespace Modules\League\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class LeagueMatch
 * @package Modules\League\Models
 * @author Hossein Haghparst
 */
class LeagueMatch extends Model
{
    protected $table = 'matches';
    protected $fillable = [
        'week',
        'first_team_id',
        'second_team_id',
        'first_result',
        'second_result',
    ];

    /**
     * @return BelongsTo
     */
    public function firstTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'first_team_id');
    }

    /**
     * @return BelongsTo
     */
    public function secondTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'second_team_id');
    }


}
