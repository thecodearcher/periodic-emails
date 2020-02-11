<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Faker $faker)
    {
        DB::table("users")->insert([
            [
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail, //use real email here
                'email_verified_at' => now(),
                'password' => Hash::make($faker->password()), // password
            ],
            [
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail, //use real email here
                'email_verified_at' => now(),
                'password' => Hash::make($faker->password()), // password
            ],
            [
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail, //use real email here
                'email_verified_at' => now(),
                'password' => Hash::make($faker->password()), // password
            ],
        ]);
    }
}
