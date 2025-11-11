<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            // Basic counting units
            ['unit_name' => 'Piece', 'abbreviation' => 'pc'],
            ['unit_name' => 'Pair', 'abbreviation' => 'pr'],
            ['unit_name' => 'Set', 'abbreviation' => 'set'],
            ['unit_name' => 'Dozen', 'abbreviation' => 'dz'],

            // Weight units
            ['unit_name' => 'Kilogram', 'abbreviation' => 'kg'],
            ['unit_name' => 'Gram', 'abbreviation' => 'g'],
            ['unit_name' => 'Pound', 'abbreviation' => 'lb'],
            ['unit_name' => 'Ounce', 'abbreviation' => 'oz'],

            // Volume units
            ['unit_name' => 'Liter', 'abbreviation' => 'L'],
            ['unit_name' => 'Milliliter', 'abbreviation' => 'mL'],
            ['unit_name' => 'Gallon', 'abbreviation' => 'gal'],

            // Packaging units
            ['unit_name' => 'Pack', 'abbreviation' => 'pack'],
            ['unit_name' => 'Box', 'abbreviation' => 'box'],
            ['unit_name' => 'Case', 'abbreviation' => 'case'],
            ['unit_name' => 'Bundle', 'abbreviation' => 'bundle'],
            ['unit_name' => 'Bag', 'abbreviation' => 'bag'],
            ['unit_name' => 'Bottle', 'abbreviation' => 'btl'],
            ['unit_name' => 'Can', 'abbreviation' => 'can'],
            ['unit_name' => 'Jar', 'abbreviation' => 'jr'],
            ['unit_name' => 'Tube', 'abbreviation' => 'tube'],
            ['unit_name' => 'Pouch', 'abbreviation' => 'pouch'],

            // Length/Area units
            ['unit_name' => 'Meter', 'abbreviation' => 'm'],
            ['unit_name' => 'Centimeter', 'abbreviation' => 'cm'],
            ['unit_name' => 'Inch', 'abbreviation' => 'in'],
            ['unit_name' => 'Foot', 'abbreviation' => 'ft'],
            ['unit_name' => 'Yard', 'abbreviation' => 'yd'],
            ['unit_name' => 'Roll', 'abbreviation' => 'roll'],
            ['unit_name' => 'Sheet', 'abbreviation' => 'sheet'],

            // Special units common in Philippines
            ['unit_name' => 'Sack', 'abbreviation' => 'sack'],
            ['unit_name' => 'Tray', 'abbreviation' => 'tray'],
            ['unit_name' => 'Sachet', 'abbreviation' => 'sachet'],
        ];

        DB::table('units')->insert($units);
    }
}
