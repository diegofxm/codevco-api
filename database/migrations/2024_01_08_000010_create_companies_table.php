<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_organization_id')->constrained();
            $table->foreignId('type_document_id')->constrained();
            $table->string('document_number');
            $table->integer('dv');
            $table->string('business_name');
            $table->string('trade_name')->nullable();
            $table->foreignId('type_regime_id')->constrained();
            $table->json('type_liabilities');
            $table->json('economic_activities');
            $table->string('merchant_registration');
            $table->string('address')->nullable();
            $table->foreignId('location_id')->constrained('cities')->onDelete('cascade');
            $table->string('postal_code')->nullable();
            $table->string('phone');
            $table->string('email');
            $table->string('logo_path')->nullable();
            $table->string('software_id')->nullable();
            $table->string('software_pin')->nullable();
            $table->string('test_set_id')->nullable();
            $table->string('certificate_path')->nullable();
            $table->string('certificate_password')->nullable();
            $table->tinyInteger('environment')->default(1);
            $table->boolean('status')->default(true);
            $table->string('subdomain', 12)->unique();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
