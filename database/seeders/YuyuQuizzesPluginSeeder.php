<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class YuyuQuizzesPluginSeeder extends Seeder
{
    public function run(): void
    {
        if (!DB::table('plugins')->whereRaw('LOWER(plugin_name) = ?', ['yuyuquizzes'])->exists()) {
            DB::table('plugins')->insert([
                'plugin_name' => 'Yuyuquizzes',
                'plugin_name_full' => '小テスト',
                'display_flag' => 1,
                'display_sequence' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
