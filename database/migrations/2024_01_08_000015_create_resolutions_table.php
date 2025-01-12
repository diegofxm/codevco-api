<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('type_document_id')->constrained();
            $table->string('prefix');
            $table->string('resolution');
            $table->date('resolution_date');
            $table->date('expiration_date');
            $table->string('technical_key');
            $table->integer('from');
            $table->integer('to');
            $table->integer('current_number');
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'type_document_id', 'prefix']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('resolutions');
    }
};
