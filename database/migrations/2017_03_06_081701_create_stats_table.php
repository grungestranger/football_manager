<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stats', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('match_id');
            $table->integer('user_id');
            $table->integer('player_id');
            $table->tinyInteger('in_time')->nullable();
            $table->tinyInteger('out_time')->nullable();
            $table->tinyInteger('goals_count');
            $table->text('goals_time');
            $table->tinyInteger('yellow_cards_count');
            $table->text('yellow_cards_time');
            $table->tinyInteger('red_card_time')->nullable();
            $table->text('setting');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('stats');
    }
}
