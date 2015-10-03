<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $this->call(UserTableSeeder::class);
        Model::reguard();
    }
}

class UserTableSeeder extends Seeder 
{
    public function run()
    {
        DB::table('users')->delete();
        User::create(array('name' => 'admin', 'email' => 'admin@example.com', 'is_admin' => true, 'password'=> bcrypt('admin')));
        User::create(array('name' => 'huff', 'email' => 'huff@example.com', 'is_admin' => false, 'password'=> bcrypt('huff')));
        User::create(array('name' => 'prince', 'email' => 'prince@example.com', 'is_admin' => false, 'password'=> bcrypt('prince')));
        User::create(array('name' => 'ballenger', 'email' => 'ballenger@example.com', 'is_admin' => false, 'password'=> bcrypt('ballenger')));
        User::create(array('name' => 'lopez', 'email' => 'lopez@example.com', 'is_admin' => false, 'password'=> bcrypt('lopez')));
        User::create(array('name' => 'maxfield', 'email' => 'maxfield@example.com', 'is_admin' => false, 'password'=> bcrypt('maxfield')));
    }
}
