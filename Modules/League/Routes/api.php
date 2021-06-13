<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(
    [
        'prefix' => 'leagues',
        'where' => ['id' => '[0-9]+'],
//        'middleware' => [
//            'auth:api',
//        ],
    ],
    function () {
        Route::get('/', 'LeagueController@index');
        Route::put('/{id}', 'LeagueController@update');
        Route::get('/matches-list', 'LeagueController@matchList');
        Route::post('/new-session', 'LeagueController@initialsNewSession');
        Route::put('/play', 'LeagueController@play');
        Route::put('/play-all-games', 'LeagueController@playAllGames');
    }
);
