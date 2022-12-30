<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateH5pTmpfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('h5p_tmpfiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('path');
            $table->timestamps();
            $table->index(['created_at'], 'created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::drop('h5p_tmpfiles');
    }
}
