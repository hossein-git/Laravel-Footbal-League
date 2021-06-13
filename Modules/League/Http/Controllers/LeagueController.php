<?php

namespace Modules\League\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Modules\Base\Http\Controllers\BaseController;
use Modules\League\API\NextGameResource;
use Modules\League\API\TeamResourceCollection;
use Modules\League\Http\Requests\UpdateMatchRequest;
use Modules\League\Services\LeagueService;

class LeagueController extends BaseController
{

    /**
     * @var LeagueService
     */
    private $service;

    public function __construct()
    {
        $this->service = resolve(LeagueService::class);
    }


    /**
     * Display a listing of the resource.
     * @return JsonResponse|TeamResourceCollection
     */
    public function index()
    {
        try {
            return $this->service->index();
        } catch (Exception $exception) {
            return $this->handleException(\request(), $exception);
        }
    }

    /**
     * get match list
     * @return JsonResponse
     */
    public function matchList()
    {
        try {
            return $this->service->matchList();
        } catch (Exception $exception) {
            return $this->handleException(\request(), $exception);
        }
    }

    /**
     * play next weeks games
     * @return JsonResponse|NextGameResource
     */
    public function play()
    {
        try {
            return $this->service->play();
        } catch (Exception $exception) {
            return $this->handleException(\request(), $exception);
        }
    }

    /**
     * Start New Session and make match schedule
     * @return JsonResponse
     */
    public function initialsNewSession()
    {
        try {
            return $this->service->initialsNewSession();
        } catch (Exception $exception) {
            return $this->handleException(\request(), $exception);
        }
    }

    /**
     * Play all remaining games
     * @return JsonResponse
     */
    public function playAllGames(): JsonResponse
    {
        try {
            return $this->service->playAllGames();
        } catch (Exception $exception) {
            return $this->handleException(\request(), $exception);
        }
    }

    /**
     * update match
     * @param  UpdateMatchRequest  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(UpdateMatchRequest $request, int $id)
    {
        try {
            return $this->service->update($request->validated(), $id);
        } catch (Exception $exception) {
            return $this->handleException($request, $exception);
        }
    }


}
