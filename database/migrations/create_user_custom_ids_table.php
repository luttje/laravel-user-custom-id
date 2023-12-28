<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_custom_ids', function (Blueprint $table) {
            $table->id();

            // The morph owner (usually a user or tenant) to which the custom id belongs.
            // Large enough for any type of id, be it an int or uuid.
            $table->string('owner_id');
            $table->string('owner_type');

            // The morph target (usually a model) to which the custom id points.
            $table->string('target_type');
            $table->string('target_attribute');

            // The custom id configuration.
            $table->string('format');

            // The last value applied to a target.
            $table->json('last_target_custom_id')->nullable();

            // The owner can have only one custom id per target.
            $table->unique(['owner_id', 'owner_type', 'target_type']);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_custom_ids');
    }
};
