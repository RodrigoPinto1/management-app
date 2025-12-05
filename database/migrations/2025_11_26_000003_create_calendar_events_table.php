<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('entity_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->foreignId('calendar_type_id')->nullable()->constrained('calendar_types')->nullOnDelete();
            $table->foreignId('calendar_action_id')->nullable()->constrained('calendar_actions')->nullOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->boolean('shared')->default(false);
            $table->string('knowledge')->nullable();
            $table->text('description')->nullable();
            $table->string('state')->default('planned'); // planned, done, cancelled
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('calendar_events');
    }
};
