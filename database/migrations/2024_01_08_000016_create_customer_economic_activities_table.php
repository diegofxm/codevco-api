<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customer_economic_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('economic_activity_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Nombre de índice más corto
            $table->unique(['customer_id', 'economic_activity_id'], 'customer_economic_activity_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_economic_activities');
    }
};
