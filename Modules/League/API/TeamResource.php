<?php

namespace Modules\League\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class TeamResource extends JsonResource
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
            'name' => (string)$this->name,
            'played' => (int)$this->played,
            'pts' => (int)$this->pts,
            'loose' => (int)$this->loose,
            'win' => (int)$this->win,
            'draw' => (int)$this->draw,
            'gf' => (int)$this->gf,
            'ga' => (int)$this->ga,
            'gd' => (int)$this->gd()
        ];
    }
}
