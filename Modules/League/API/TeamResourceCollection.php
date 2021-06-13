<?php

namespace Modules\League\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;


class TeamResourceCollection extends ResourceCollection
{

    public $collects = TeamResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
