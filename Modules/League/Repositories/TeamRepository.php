<?php


namespace Modules\League\Repositories;


use Modules\Base\Repositories\BaseRepository;
use Modules\League\Models\Team;


/**
 * Class TeamRepository
 * @package Modules\League\Repositories
 */
class TeamRepository extends BaseRepository
{

    /**
     * @var string
     */
    protected $cacheKey = 'teams';

    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
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
        return Team::class;
    }


    /**
     * @return string
     */
    public function cacheKey(): string
    {
        return $this->cacheKey;
    }

}
