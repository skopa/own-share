<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->string('identity')->unique();
            $table->tinyInteger('type_id');
            $table->index(['identity', 'type_id']);

            $table->string('name');
            $table->string('internal_identity');
            $table->unsignedInteger('size')->nullable();
            $table->unsignedInteger('reviews_count');

            $table->boolean('is_public')->default(1);
            $table->boolean('is_private')->default(0);

            $table->softDeletes();
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
        Schema::dropIfExists('resources');
    }
}
