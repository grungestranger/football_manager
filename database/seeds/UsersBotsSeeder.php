<?php

use Illuminate\Database\Seeder;

use App\PlayerModel;
use App\User;

class UsersBotsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $unique = FALSE;
        while (!$unique) {
            $name = str_random(rand(3, 15));
            if (!DB::table('users')->where('name', $name)->count()) {
                $unique = TRUE;
            }
        }

        $unique = FALSE;
        while (!$unique) {
            $email = str_random(rand(3, 15)).'@gmail.com';
            if (!DB::table('users')->where('email', $email)->count()) {
                $unique = TRUE;
            }
        }

		$user = User::create([
			'confirmed' => 1,
			'type' => 'bot',
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('secret'),
		]);

		PlayerModel::createTeam($user->id);
    }
}
