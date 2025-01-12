<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_economic_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('economic_activity_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Nombre de índice más corto
            $table->unique(['company_id', 'economic_activity_id'], 'company_economic_activity_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_economic_activities');
    }
};
