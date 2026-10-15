<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->string('footer_background', 7)->default('#202328');
            $table->string('footer_text_color', 7)->default('#9B9DA1');
            $table->string('footer_accent_color', 7)->default('#FFFFFF');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropColumn(['footer_background', 'footer_text_color', 'footer_accent_color']);
        });
    }
};
