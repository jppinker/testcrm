<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed only a new installation; never change an existing schedule or its templates.
        if (DB::table('employees')->exists() || DB::table('schedule_templates')->exists() || DB::table('shifts')->exists()) return;
        DB::transaction(function () {
            $employees = [];
            foreach (['Кузнецова Алина','Волков Тимур','Морозова Виктория','Соколов Артём','Лебедева Полина','Новиков Кирилл','Ерохина Дарья','Гаврилов Егор','Смирнова Анна'] as $name) {
                $employees[] = DB::table('employees')->insertGetId(['name' => $name, 'role' => 'агент · 24 ч']);
            }
            $templates = [];
            foreach ([['Утро','07:00','15:00','cornflower'],['День','10:00','18:00','lavender'],['Вечер','15:00','23:00','mint']] as [$name,$start,$end,$color]) {
                $id = DB::table('schedule_templates')->insertGetId(['name'=>$name,'start_time'=>$start,'end_time'=>$end,'color'=>$color]);
                $templates[] = ['template_id'=>$id,'name'=>$name,'start_time'=>$start,'end_time'=>$end,'color'=>$color];
            }
            $monday = Carbon::today()->startOfWeek();
            foreach ($employees as $index => $employee) {
                foreach ([0, 2, 4] as $offset) {
                    $day = ($offset + $index) % 7;
                    DB::table('shifts')->insert(['employee_id'=>$employee, 'date'=>$monday->copy()->addDays($day)->toDateString()] + $templates[($index + $offset) % 3]);
                }
            }
        });
    }
}
