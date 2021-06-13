<?php

namespace Modules\League\Helpers;


class RoundRobinScheduler
{

    /**
     * @param array $teamsInRound
     * @return array
     */
    public static function round(array $teamsInRound): array
    {
        if (!is_array($teamsInRound)) {
            throw new \InvalidArgumentException(' expects an array of teams');
        }

        $teamsCount = count($teamsInRound);

        if ($teamsCount === 0) {
            throw new \InvalidArgumentException(' expects an array of at least 1 team');
        }

        // if odd, add a dummy team
        if ($teamsCount % 2 == 1) {
            $teamsInRound[] = 'REST';
            $teamsCount++;
        }

        //if just 2 teams, skip the whole process
        if (!($teamsCount > 2)) {
            return [
                [$teamsInRound[0], $teamsInRound[1]],
            ];
        }

        $gamesCount = $teamsCount - 1;

        $home = [];
        $away = [];

        for ($i = 0; $i < $teamsCount / 2; $i++) {
            $home[$i] = $teamsInRound[$i];
            $away[$i] = $teamsInRound[$teamsCount - 1 - $i];
        }

        $calendar = [];
        for ($i = 0; $i < $gamesCount; $i++) {
            $calendar = self::createCalender($teamsCount, $away, $home, $i, $calendar);

            $pivot = $home[0];
            array_unshift($away, $home[1]);
            $carryover = array_pop($away);
            array_shift($home);
            array_push($home, $carryover);
            $home[0] = $pivot;
        }//endfor

        $sorting = [];
        foreach ($calendar as $key => $row) {
            $sorting[$key] = $row[0]['week'];
        }
        array_multisort($sorting, SORT_ASC, $calendar);

        return $calendar;
    }

    /**
     * @param  int  $teamsCount
     * @param  array  $away
     * @param  array  $home
     * @param  int  $i
     * @param  array  $calendar
     * @return array
     */
    private static function createCalender(int $teamsCount, array $away, array $home, int $i, array $calendar): array
    {
        for ($j = 0; $j < $teamsCount / 2; $j++) {
            $calendar[$i][] = [
                'first_team_id' => $away[$j],
                'second_team_id' => $home[$j],
                'week' => $i + 1,
            ];
            $calendar[$i + $teamsCount][] = [
                'first_team_id' => $home[$j],
                'second_team_id' => $away[$j],
                'week' => $i + $teamsCount,
            ];
        }
        return $calendar;
    }
}
