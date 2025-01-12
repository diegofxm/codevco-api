<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('resolution_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('number');
            $table->string('prefix');
            $table->string('cufe')->unique();
            $table->timestamp('issue_date');
            $table->text('notes')->nullable();
            $table->string('correction_concept');
            $table->string('discrepancy_code');
            $table->decimal('total_discount', 12, 2);
            $table->decimal('total_tax', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('credit_notes');
    }
};
