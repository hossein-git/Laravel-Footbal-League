<?php

namespace Modules\League\Services;


use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Modules\Base\Traits\ApiResponse;
use Modules\League\API\NextGameResource;
use Modules\League\API\TeamResourceCollection;
use Modules\League\Helpers\RoundRobinScheduler;
use Modules\League\Repositories\LeagueMatchRepository;
use Modules\League\Repositories\TeamRepository;

class LeagueService
{
    use ApiResponse;

    /**
     * @var TeamRepository
     */
    private $repo;

    /**
     * @var LeagueMatchRepository
     */
    private $matchRepo;

    public function __construct()
    {
        $this->repo = resolve(TeamRepository::class);
        $this->matchRepo = resolve(LeagueMatchRepository::class);
    }

    /**
     * league table
     * @return TeamResourceCollection
     */
    public function index(): TeamResourceCollection
    {
        //in case of real application we hava use pagination
        //here we can get search and per page from request and fetch relevant rows
//        ->paginate('search','per_page');
        return TeamResourceCollection::make($this->repo->allQuery()->orderByDesc('pts')->get());
    }

    /**
     * list of the match
     * @return AnonymousResourceCollection
     */
    public function matchList()
    {
        //TODO: paginate in real app
        return NextGameResource::collection($this->matchRepo->all());
    }

    /**
     *  delete all rows and
     *  make all matches in session
     * @return AnonymousResourceCollection
     */
    public function initialsNewSession()
    {
        $teams = config('league.teams');
        //delete all rows
        $this->truncateTables();

        //create teams with strength
        $this->createTeams($teams);

        //get list of session matches
        $weeks = RoundRobinScheduler::round($this->getTeamIds($teams));
        $matches = [];
        //create matches
        foreach ($weeks as $week) {
            foreach ($week as $match) {
                $matches[] = $this->matchRepo->create($match);
            }
        }
        return NextGameResource::collection($matches);
    }

    private function truncateTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $this->matchRepo->makeModel()->truncate();
        $this->repo->makeModel()->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function createTeams(array $teams): void
    {
        foreach ($teams as $teamName => $strength) {
            $this->repo->create(
                [
                    'name' => $teamName,
                    'strength' => $strength,
                    'played' => 0,
                    'pts' => 0,
                    'loose' => 0,
                    'draw' => 0,
                    'win' => 0,
                    'gf' => 0,
                    'ga' => 0,
                ]
            );
        }
    }

    private function getTeamIds($teams): array
    {
        $teamIds = [];
        for ($i = 1; $i <= count($teams); $i++) {
            $teamIds[] = $i;
        }
        return $teamIds;
    }

    /**
     * play all remaining games
     * @return JsonResponse
     * @throws Exception
     */
    public function playAllGames(): JsonResponse
    {
        $lastWeek = $this->matchRepo->makeModel()->orderByDesc('id')->first(['week']);
        $currentWeek = $this->matchRepo->makeModel()->whereNull('first_result')->first(['week']);
        if (!$lastWeek || !$currentWeek) {
            return $this->customResponse('Please Run New Session', 'Not Acceptable', 406);
        }
        if ($lastWeek->week === $currentWeek->week) {
            return $this->customResponse(
                'Session has being already finished , Please Run New Session',
                'Not Acceptable',
                406
            );
        }
        $currentWeek = $currentWeek->week;
        while ($currentWeek <= $lastWeek->week) {
            $this->play();
            $currentWeek++;
        }
        return $this->customResponse('All match Have Played', 'success', 200);
    }

    /**
     * play next week game
     * @return NextGameResource|JsonResponse
     * @throws Exception
     */
    public function play()
    {
        //get latest week
        $week = $this->matchRepo->makeModel()->whereNull('first_result')->first(['week']);
        if (!$week) {
            return $this->customResponse('Please start new session', 'Not Allowed', 406);
        }
        //get matches of this week with relations
        $matches = $this->matchRepo->where('week', $week->week, ['firstTeam', 'secondTeam'])->get();
        //play, according to team strength
        foreach ($matches as $match) {
            $firstTeam = $match->firstTeam;
            $secondTeam = $match->secondTeam;
            $first_result = array_rand($firstTeam->strengthArray());
            $second_result = array_rand($secondTeam->strengthArray());
            $match->update(
                [
                    'first_result' => $first_result,
                    'second_result' => $second_result,
                ]
            );

            if ($first_result > $second_result) {
                $this->updateTeam($firstTeam, $secondTeam, $first_result, $second_result, 'win');
            } elseif ($first_result < $second_result) {
                $this->updateTeam($firstTeam, $secondTeam, $first_result, $second_result, 'loose');
            } elseif ($first_result === $second_result) {
                $this->updateTeam($firstTeam, $secondTeam, $first_result, $second_result, 'draw');
            }
        }
        return NextGameResource::collection($matches);
    }

    private function updateTeam($firstTeam, $secondTeam, int $first_result, int $second_result, string $status): void
    {
        $firstTeam->fill(
            [
                'played' => $firstTeam->played + 1,
                'ga' => $firstTeam->ga + $first_result,
                'gf' => $firstTeam->gf + $second_result,
            ]
        );
        $secondTeam->fill(
            [
                'played' => $secondTeam->played + 1,
                'ga' => $secondTeam->ga + $second_result,
                'gf' => $secondTeam->gf + $first_result,
            ]
        );

        switch ($status) {
            //win first team
            case 'win':
                $firstTeam->fill(
                    [
                        'pts' => $firstTeam->pts + (int)config('league.pointsPerWinMatch'),
                        'win' => $firstTeam->win + 1,
                    ]
                );
                $secondTeam->fill(
                    [
                        'loose' => $secondTeam->loose + 1,
                    ]
                );
                break;
            //win second team
            case 'loose':
                $firstTeam->fill(
                    [
                        'loose' => $firstTeam->loose + 1,
                    ]
                );
                $secondTeam->fill(
                    [
                        'pts' => (int)$secondTeam->pts + (int)config('league.pointsPerWinMatch'),
                        'win' => $secondTeam->win + 1,
                    ]
                );
                break;
            //win third team
            case 'draw':
                $firstTeam->fill(
                    [
                        'pts' => $firstTeam->pts + (int)config('league.pointsPerDraw'),
                        'draw' => $firstTeam->draw + 1,
                    ]
                );
                $secondTeam->fill(
                    [
                        'pts' => (int)$secondTeam->pts + (int)config('league.pointsPerDraw'),
                        'draw' => $secondTeam->draw + 1,
                    ]
                );
        }
        $firstTeam->save();
        $secondTeam->save();
    }

    /**
     * update match results
     * @param $inputs
     * @param $id
     * @return JsonResponse
     */
    public function update($inputs, $id)
    {
        $oldMatch = $this->matchRepo->find($id);
        $firstTeam = $oldMatch->firstTeam;
        $secondTeam = $oldMatch->secondTeam;
        $perWinPoint = (int)config('league.pointsPerWinMatch');
        $perDrawnPoint = (int)config('league.pointsPerDraw');

        $first_result = (int)$inputs['first_result'];
        $second_result = (int)$inputs['second_result'];

        $firstUpdate = [
            'ga' => ($firstTeam->ga - $oldMatch->first_result) + $first_result,
            'gf' => ($firstTeam->gf - $oldMatch->second_result) + $second_result,
        ];
        $secondUpdate = [
            'ga' => ($secondTeam->ga - $oldMatch->second_result) + $second_result,
            'gf' => ($secondTeam->gf - $oldMatch->first_result) + $first_result,
        ];

        //if it was win for first team
        if ((int)$oldMatch->first_result > (int)$oldMatch->second_result) {
            //if first team has loosed in update
            if ($first_result < $second_result) {
                $firstUpdate['win'] = $firstTeam->win - 1;
                $firstUpdate['loose'] = $firstTeam->loose + 1;
                $firstUpdate['pts'] = (int)$firstTeam->pts - $perWinPoint;

                $secondUpdate['win'] = $secondTeam->win + 1;
                $secondUpdate['loose'] = $secondTeam->loose - 1;
                $secondUpdate['pts'] = (int)$secondTeam->pts + $perWinPoint;
            } elseif ($first_result === $second_result) {
                $firstUpdate['draw'] = $firstTeam->draw + 1;
                $firstUpdate['win'] = $firstTeam->win - 1;
                $firstUpdate['pts'] = ((int)$firstTeam->pts - $perWinPoint) + $perDrawnPoint;

                $secondUpdate['draw'] = $secondTeam->draw + 1;
                $secondUpdate['loose'] = $secondTeam->loose - 1;
                $secondUpdate['pts'] = (int)$secondTeam->pts + $perDrawnPoint;
            }
        } //if it was draw
        elseif ((int)$oldMatch->first_result === (int)$oldMatch->second_result) {
            if ($first_result < $second_result) {
                $firstUpdate['loose'] = $firstTeam->loose + 1;
                $firstUpdate['draw'] = $firstTeam->draw - 1;
                $firstUpdate['pts'] = (int)$firstTeam->pts - $perDrawnPoint;

                $secondUpdate['win'] = $secondTeam->win + 1;
                $secondUpdate['draw'] = $secondTeam->draw - 1;
                $secondUpdate['pts'] = (int)$secondTeam->pts + $perWinPoint;
            } elseif ($first_result > $second_result) {
                $firstUpdate['win'] = $firstTeam->win + 1;
                $firstUpdate['draw'] = $firstTeam->draw - 1;
                $firstUpdate['pts'] = (int)$firstTeam->pts + ($perWinPoint - $perDrawnPoint);

                $secondUpdate['loose'] = $secondTeam->loose + 1;
                $secondUpdate['draw'] = $secondTeam->draw - 1;
                $secondUpdate['pts'] = (int)$secondTeam->pts - $perDrawnPoint;
            }
        } //it first team lost
        elseif ((int)$oldMatch->first_result < (int)$oldMatch->second_result) {
            if ($first_result > $second_result) {
                $firstUpdate['win'] = $firstTeam->win + 1;
                $firstUpdate['loose'] = $firstTeam->loose - 1;
                $firstUpdate['pts'] = (int)$firstTeam->pts + $perWinPoint;

                $secondUpdate['win'] = $secondTeam->win - 1;
                $secondUpdate['loose'] = $secondTeam->loose + 1;
                $secondUpdate['pts'] = (int)$secondTeam->pts - $perWinPoint;
            } elseif ($first_result === $second_result) {
                $firstUpdate['draw'] = $firstTeam->draw + 1;
                $firstUpdate['loose'] = $firstTeam->loose - 1;
                $firstUpdate['pts'] = (int)$firstTeam->pts + $perDrawnPoint;

                $secondUpdate['draw'] = $secondTeam->draw + 1;
                $secondUpdate['win'] = $secondTeam->win - 1;
                $firstUpdate['pts'] = (int)$firstTeam->pts + ($perWinPoint - $perDrawnPoint);
            }
        }

        $firstTeam->fill($firstUpdate);
        $firstTeam->save();
        $secondTeam->fill($secondUpdate);
        $secondTeam->save();

        //TODO add model lang
        return $this->matchRepo->passViewAfterUpdated((bool)$oldMatch->update($inputs), 'matches');
    }

}
