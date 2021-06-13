import api from './../api';
import Resource from './../baseResource';

class leagueResource extends Resource {
    constructor() {
        super('leagues');
    }

    matchesList(query) {
        return api
        ({
            url: '/' + this.uri + '/matches-list',
            method: 'get',
            params: query,
        });
    }

    newSession() {
        return api
        ({
            url: '/' + this.uri + '/new-session',
            method: 'post',
        });
    }

    play() {
        return api
        ({
            url: '/' + this.uri + '/play',
            method: 'put',
        });
    }

    playAllGames() {
        return api
        ({
            url: '/' + this.uri + '/play-all-games',
            method: 'put',
        });
    }

}

export {leagueResource as default};
