<?php


namespace Modules\League\Repositories;


use Modules\Base\Repositories\BaseRepository;
use Modules\League\Models\LeagueMatch;
use Modules\League\Models\Team;


/**
 * Class LeagueMatchRepository
 * @package Modules\League\Repositories
 */
class LeagueMatchRepository extends BaseRepository
{

    /**
     * @var string
     */
    protected $cacheKey = 'matches';

    /**
     * @var array
     */
    protected $fieldSearchable = [
        '',
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     */
    public function model(): string
    {
        return LeagueMatch::class;
    }


    /**
     * @return string
     */
    public function cacheKey(): string
    {
        return $this->cacheKey;
    }

}
