<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('players', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('name');
            $table->tinyInteger('speed');
            $table->tinyInteger('acceleration');
            $table->tinyInteger('coordination');
            $table->tinyInteger('power');
            $table->tinyInteger('accuracy');
            $table->tinyInteger('vision');
            $table->tinyInteger('reaction');
            $table->tinyInteger('in_gate');
            $table->tinyInteger('on_out');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('players');
    }
}
