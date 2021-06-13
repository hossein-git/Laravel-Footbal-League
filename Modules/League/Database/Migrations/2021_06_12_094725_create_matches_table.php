<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('week');
            //0 lose , 1 draw , 2 win
//            $table->unsignedTinyInteger('status')->default(0);
            $table->foreignId('first_team_id')->constrained('teams')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('second_team_id')->constrained('teams')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('first_result')->nullable();
            $table->unsignedInteger('second_result')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('matches');
    }
}
