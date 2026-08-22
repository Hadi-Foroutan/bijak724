<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

class StatesSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            ['name' => 'تهران', 'code' => 11],
            ['name' => 'قم', 'code' => 14],
            ['name' => 'قزوین', 'code' => 15],
            ['name' => 'مازندران', 'code' => 16],
            ['name' => 'البرز', 'code' => 18],
            ['name' => 'اصفهان', 'code' => 21],
            ['name' => 'آذربایجان شرقی', 'code' => 26],
            ['name' => 'خراسان رضوی', 'code' => 31],
            ['name' => 'خراسان شمالی', 'code' => 32],
            ['name' => 'خراسان جنوبی', 'code' => 33],
            ['name' => 'خوزستان', 'code' => 36],
            ['name' => 'فارس', 'code' => 41],
            ['name' => 'کرمان', 'code' => 45],
            ['name' => 'مرکزی', 'code' => 51],
            ['name' => 'گیلان', 'code' => 54],
            ['name' => 'آذربایجان غربی', 'code' => 57],
            ['name' => 'سیستان و بلوچستان', 'code' => 61],
            ['name' => 'هرمزگان', 'code' => 64],
            ['name' => 'زنجان', 'code' => 67],
            ['name' => 'کرمانشاه', 'code' => 71],
            ['name' => 'کردستان', 'code' => 73],
            ['name' => 'همدان', 'code' => 75],
            ['name' => 'چهارمحال و بختیاری', 'code' => 77],
            ['name' => 'لرستان', 'code' => 81],
            ['name' => 'ایلام', 'code' => 83],
            ['name' => 'کهگیلویه و بویراحمد', 'code' => 85],
            ['name' => 'سمنان', 'code' => 87],
            ['name' => 'اردبیل', 'code' => 91],
            ['name' => 'یزد', 'code' => 93],
            ['name' => 'بوشهر', 'code' => 95],
            ['name' => 'گلستان', 'code' => 97],
        ];

        foreach ($states as $state) {
            State::create($state);
        }
    }
}
