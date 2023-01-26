<?php

use Illuminate\Database\Migrations\Migration;

class CreateTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('language_translations', function ($table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('locale', 10);
            $table->string('group', 150)->nullable();
            $table->string('item', 150);
            $table->text('text');
            $table->foreign('locale')->references('locale')->on('languages');
            $table->unique(['locale', 'group', 'item']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('language_translations');
    }
}
