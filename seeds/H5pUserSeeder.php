<?php

use Illuminate\Database\Seeder;

class H5pUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'H5P Creator',
            'email' => 'admin@example.com',
            'password' => bcrypt('admin'),
        ]);
    }
}
