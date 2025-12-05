<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('logo')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('locality')->nullable();
            $table->string('tax_number')->nullable();
            $table->timestamps();
        });

        // seed a single empty row so the app has a record to edit
        \DB::table('companies')->insert([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
