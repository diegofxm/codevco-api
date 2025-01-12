<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_type_liabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('type_liability_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['company_id', 'type_liability_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_type_liabilities');
    }
};
