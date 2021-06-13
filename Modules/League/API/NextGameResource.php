<?php

namespace Modules\League\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class NextGameResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => (int)$this->id,
            'week' => (string)$this->week,
            'firstTeam' => (string)$this->firstTeam->name,
            'secondTeam' => (string)$this->secondTeam->name,
            'firstResult' => $this->first_result ?? '',
            'secondResult' => $this->second_result ?? '',
            'created_at' => $this->created_at,
        ];
    }
}
