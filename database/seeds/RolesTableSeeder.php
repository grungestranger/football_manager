<?php

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$arr = ['Вр', 'КЗЛ', 'ЦЗ', 'КЗП', 'ОП', 'КПЛ', 'ЦП', 'КПП', 'АП', 'ОФ', 'КФЛ', 'ЦФ', 'КФП'];
    	$insert = [];
    	foreach ($arr as $item) {
    		$insert[] = ['name' => $item];
    	}
        DB::table('roles')->insert($insert);
    }
}
