<?php

namespace Modules\League\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
/**
 * Class Team
 * @package Modules\League\Models
 *
 * @property string name
 * @property int strength
 * @property int played
 * @property int pts
 * @property int loose
 * @property int win
 * @property int draw
 * @property int gf
 * @property int ga
 */
class Team extends Model
{

    protected $table = 'teams';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'strength',
        'played',
        'pts',
        'loose',
        'draw',
        'win',
        'gf',
        'ga',
    ];

    /**
     * @return int
     */
    public function gd() :int
    {
        return $this->attributes['ga'] - $this->attributes['gf'];
    }

    public function strengthArray()
    {
        $result = [];
        for ($i = 1 ; $i <= $this->attributes['strength'] ; $i++ ){
            $result[] = $i;
        }
        return $result;
    }



}
