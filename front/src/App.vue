<template>
  <div class="container">
    <h1 class="display-3">Laliga</h1>
    <div class="card">
      <div class="card-header">
        <button v-if="!gameList" class="btn btn-success float-start" @click="weeklyList">Games List</button>
        <button v-else class="btn btn-success float-start" @click="gameList = false">Back</button>
      </div>
      <div  class="card-body">
        <table v-show="!gameList" class="table-responsive-sm table table-striped">
          <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Club</th>
            <th scope="col">Played</th>
            <th scope="col">Won</th>
            <th scope="col">Drawn</th>
            <th scope="col">Lost</th>
            <th scope="col">GA</th>
            <th scope="col">GF</th>
            <th scope="col">GD</th>
            <th scope="col">Points</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(team,index) in teams" :key="team.id">
            <th scope="row">{{ index + 1 }}</th>
            <td>{{ team.name }}</td>
            <td>{{ team.played }}</td>
            <td>{{ team.win }}</td>
            <td>{{ team.draw }}</td>
            <td>{{ team.loose }}</td>
            <td>{{ team.ga }}</td>
            <td>{{ team.gf }}</td>
            <td>{{ team.gd }}</td>
            <td>{{ team.pts }}</td>
          </tr>
          </tbody>
        </table>
        <table v-show="gameList" class="table-responsive-sm table table-striped">
          <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Week</th>
            <th scope="col">Result</th>
            <th scope="col">Action</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(match,index) in matchListItems" :key="match.id">
            <th scope="row">{{ index + 1 }}</th>
            <td >{{  match.week }}</td>
            <td>
              <b> {{ match.firstTeam }}</b> {{ match.firstResult }} -
              <b>{{ match.secondTeam }}</b> {{ match.secondResult }}
            </td>
            <td>
              <button v-if="match.firstResult || match.secondResult" class="btn btn-warning btn-sm"
                      @click="editResult(match)">Edit
              </button>
            </td>
          </tr>
          </tbody>
        </table>

      </div>
      <div v-if="!gameList" class="card-footer d-flex justify-content-around">
        <button class="btn btn-warning" @click="play">Play Next Week Games</button>
        <button class="btn btn-outline-danger" @click="initialsNewSession">Initials NewSession</button>
        <button class="btn btn-outline-success" @click="playAll">Play Remains Game</button>
      </div>
    </div>

    <!-- play result modal  -->
    <div class="modal fade"
         :class="{'d-block d-opacity': playModal}"
         tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Game Result</h5>
            <button type="button" class="btn-close" @click="playModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="card mb-2" v-for="play in playList" :key="play.id">
              <div class="card-header">
                #Week {{ play.week }}
              </div>
              <div class="card-text py-1">
                <b> {{ play.firstTeam }}</b> {{ play.firstResult }} -
                <b>{{ play.secondTeam }}</b> {{ play.secondResult }}
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="playModal = false">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- edit  -->
    <div class="modal fade"
         :class="{'d-block d-opacity': editModal}"
         tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Result</h5>
            <button type="button" class="btn-close" @click="editModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="card mb-2">
              <div class="card-header">
                #Week {{ editedItem.week }}
              </div>
              <div class="card-text py-1">
                <b> {{ editedItem.firstTeam }}</b>
                <input type="number" v-model="editedItem.firstResult" class="form-control form-control-sm">
                <br>
                <b>{{ editedItem.secondTeam }}</b>
                <input type="number" v-model="editedItem.secondResult" class="form-control form-control-sm">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-success" @click="updateRow">Save</button>
          </div>
        </div>
      </div>
    </div>


  </div>
</template>

<script>
import LeagueResource from './Api/Resources/LeagueResource';

const Model = new LeagueResource();

export default {
  name: 'App',
  data() {
    return {
      loading: false,
      playModal: false,
      editModal: false,
      gameList: false,
      teams: [],
      playList: [],
      matchListItems: [],
      editedItem: [],
    }
  },
  created() {
    this.initials();
  },
  methods: {
    async initials() {
      this.loading = true;
      await Model.list()
          .then(({data}) => {
            this.teams = data.data;
          })
          .catch(err => {
            console.error(err);
            alert('Error to get list')
          })
          .finally(() => this.loading = false)
    },
    playAll() {
      this.loading = true;
      Model.playAllGames()
          .then(({data}) => {
            this.initials();
            alert('all game have being played')
          })
          .catch(err => {
            console.error(err);
            if (err.response) {
              alert(err.response.data.meta.msg);
              return;
            }
            alert('Error to play all games')
          })
          .finally(() => this.loading = false)
    },
    initialsNewSession() {
      this.loading = true;
      Model.newSession()
          .then(({data}) => {
            this.playList = data.data;
            this.initials();
            this.playModal = true;
          })
          .catch(err => {
            console.error(err);
            alert('Error to start new session')
          })
          .finally(() => this.loading = false)
    },
    play() {
      this.loading = true;
      Model.play()
          .then(({data}) => {
            this.playList = data.data;
            this.initials();
            this.playModal = true;
          })
          .catch(err => {
            console.error(err);
            if (err.response) {
              alert(err.response.data.meta.msg);
              return;
            }
            alert('Error to play game')
          })
          .finally(() => this.loading = false)
    },
    async weeklyList() {
      this.loading = true;
      await Model.matchesList()
          .then(({data}) => {
            this.matchListItems = data.data;
            this.gameList = true;
          })
          .catch(err => {
            console.error(err);
            alert('Error to get list')
          })
          .finally(() => this.loading = false)
    },
    editResult(item){
       this.editedItem = {...item};
       this.editModal = true;
    },
    updateRow(){
      this.loading = true;
      Model.update(this.editedItem.id,{first_result : this.editedItem.firstResult,second_result : this.editedItem.secondResult})
          .then(({data}) => {
            if (data){
              alert(data.message)
            }
            this.initials();
            this.weeklyList();
            this.editModal = false;
          })
          .catch(err => {
            console.error(err);
            alert('Error to get list')
          })
          .finally(() => this.loading = false)
    }
  }
}
</script>

<style>
.d-opacity {
  opacity: 1 !important;
}
</style>
