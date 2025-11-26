<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('number')->nullable()->index();
            $table->date('date')->nullable();
            $table->date('valid_until')->nullable();
            $table->foreignId('entity_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->string('status')->default('draft'); // draft|closed
            $table->timestamp('closed_at')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('proposals');
    }
};
