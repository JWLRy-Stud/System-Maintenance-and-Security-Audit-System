<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $t) {
            $t->string('ip_address', 45)->nullable()->after('request_id'); // IPv6 max length
            $t->string('detail', 255)->nullable()->after('ip_address');    // e.g. attempted login email, or "GET users" for a 403. Never secrets.
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', fn (Blueprint $t) => $t->dropColumn(['ip_address', 'detail']));
    }
};
