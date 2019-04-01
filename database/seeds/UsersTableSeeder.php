<?php

use App\Core\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(User::class)->create([
            'first_name' => 'Justin',
            'last_name' => 'Christenson',
            'email' => 'info@justinc.me',
            'email_verified_at' => now(),
            'password' => 'staging',
            'remember_token' => str_random(10),
        ]);
    }
}
