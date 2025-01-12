<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customer_type_liabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('type_liability_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['customer_id', 'type_liability_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_type_liabilities');
    }
};
