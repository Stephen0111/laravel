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
        Schema::table('posts', function (Blueprint $table) {
            // Ensure the 'users' table exists and has an 'id' primary key
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            // You might also want to add a 'title' column if you don't have it
            $table->string('title')->nullable()->after('id'); // Add a title column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropColumn('title'); // Drop title if added in this migration
        });
    }
};
