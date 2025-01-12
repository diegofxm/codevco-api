<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('type_organization_id')->constrained();
            $table->foreignId('type_document_id')->constrained();
            $table->string('document_number');
            $table->integer('dv')->nullable(); // Dígito de verificación (2 dígitos enteros)
            $table->string('business_name');
            $table->string('trade_name')->nullable();
            $table->foreignId('type_regime_id')->constrained();
            $table->json('type_liabilities'); // Códigos de responsabilidades fiscales para DIAN XML
            $table->json('economic_activities')->nullable(); // Hasta 4 actividades económicas permitidas por DIAN
            $table->string('merchant_registration')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('location_id')->constrained('cities')->onDelete('cascade');
            $table->string('postal_code')->nullable();
            $table->string('phone');
            $table->string('email');
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'type_document_id', 'document_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
};
