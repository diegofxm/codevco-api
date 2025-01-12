<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
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
            $table->string('cufe')->unique();
            $table->timestamp('issue_date');
            $table->timestamp('payment_due_date');
            $table->text('notes')->nullable();
            $table->decimal('payment_exchange_rate', 12, 2)->default(1);
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
        Schema::dropIfExists('invoices');
    }
};
