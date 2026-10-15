<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->string('header_background', 7)->default('#FFFFFF');
            $table->string('site_background', 7)->default('#F8F7F4');
            $table->string('text_color', 7)->default('#202226');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropColumn(['header_background', 'site_background', 'text_color']);
        });
    }
};
