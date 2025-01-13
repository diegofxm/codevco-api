<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('resolution_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('currency_id')->constrained();
            $table->foreignId('payment_method_id')->constrained();
            $table->foreignId('type_operation_id')->constrained();
            $table->string('number');
            $table->string('prefix');
            $table->string('cufe')->nullable();
            $table->timestamp('issue_date');
            $table->timestamp('payment_due_date');
            $table->text('notes')->nullable();
            $table->decimal('payment_exchange_rate', 10, 2)->default(1.00);
            $table->decimal('total_discount', 10, 2)->default(0.00);
            $table->decimal('total_charges', 10, 2)->default(0.00);
            $table->decimal('total_tax', 10, 2)->default(0.00);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->string('order_reference')->nullable();
            $table->text('delivery_terms')->nullable();
            $table->timestamp('delivery_date')->nullable();
            $table->enum('status', ['draft', 'issued', 'voided'])->default('draft');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoices');
    }
};
