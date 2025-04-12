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
      Schema::create('credential_files', function (Blueprint $table) {
         $table->id();
         $table->string('name');
         $table->string('hash');
         $table->dateTime('collection_date')->nullable();
         $table->string('description')->nullable();
         $table->timestamps();
      });
   }

   /**
    * Reverse the migrations.
    */
   public function down(): void
   {
      Schema::dropIfExists('credential_files');
   }
};
