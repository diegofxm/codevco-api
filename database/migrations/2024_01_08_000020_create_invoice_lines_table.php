<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('credit_note_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('debit_note_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->text('description');
            $table->decimal('quantity', 12, 2);
            $table->decimal('price', 12, 2);
            $table->decimal('discount_rate', 5, 2);
            $table->decimal('discount_amount', 12, 2);
            $table->foreignId('unit_measure_id')->constrained();
            $table->foreignId('tax_id')->constrained();
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->date('period_start_date')->nullable();
            $table->date('period_end_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoice_lines');
    }
};
