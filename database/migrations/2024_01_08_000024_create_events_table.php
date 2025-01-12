<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_event_id')->constrained();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('credit_note_id')->nullable()->constrained('credit_notes')->onDelete('cascade');
            $table->foreignId('debit_note_id')->nullable()->constrained('debit_notes')->onDelete('cascade');
            $table->string('description');
            $table->text('notes')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('event_date');
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('events');
    }
};
