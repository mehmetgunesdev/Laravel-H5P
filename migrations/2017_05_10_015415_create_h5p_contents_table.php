<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateH5pContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('h5p_contents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->string('title');
            $table->bigInteger('library_id')->unsigned();
            $table->text('parameters');
            $table->text('filtered');
            $table->string('slug', 127);
            $table->string('embed_type', 127);
            $table->bigInteger('disable')->unsigned()->default(0);
            $table->string('content_type', 127)->nullable();
            $table->string('author', 127)->nullable();
            $table->string('license', 7)->nullable();
            $table->text('keywords', 65535)->nullable();
            $table->text('description', 65535)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::drop('h5p_contents');
    }
}
