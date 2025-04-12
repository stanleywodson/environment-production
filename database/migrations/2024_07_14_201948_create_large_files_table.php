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
        Schema::create('large_files', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('credential_file_id')->unsigned();
            $table->foreign('credential_file_id')->references('id')->on('credential_files')->onDelete('cascade');
            $table->text('url')->nullable();
            $table->text('credential_file_ids')->nullable();
            $table->text('access');
            $table->text('password');
            $table->text('application')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('large_files');
    }
};
