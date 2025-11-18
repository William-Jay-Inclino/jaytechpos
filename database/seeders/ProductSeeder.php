<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', '!=', UserRole::Admin->value)->get();

        foreach ($users as $user) {
            $this->createProductsForUser($user);
        }
    }

    private function createProductsForUser(User $user): void
    {
        if ($user->email === 'wjay.inclino@gmail.com') {
            // Original demo products for Jay
            $this->createDemoProducts($user);
        } elseif ($user->email === 'maria.santos@demo.com') {
            // Fruits, Vegetables, and Rice Store
            $this->createFruitVegetableRiceProducts($user);
        } elseif ($user->email === 'roberto.cruz@demo.com') {
            // Mini Grocery Store
            $this->createMiniGroceryProducts($user);
        } elseif ($user->email === 'luz.reyes@demo.com') {
            // Sari-Sari Store
            $this->createSariSariProducts($user);
        }
    }

    private function createDemoProducts(User $user): void
    {
        $products = [
            ['product_name' => 'Orange Juice', 'unit_id' => 17, 'cost_price' => 85.00, 'unit_price' => 95.00],
            ['product_name' => 'Cola Drink', 'unit_id' => 17, 'cost_price' => 25.00, 'unit_price' => 30.00],
            ['product_name' => 'Energy Drink', 'unit_id' => 18, 'cost_price' => 45.00, 'unit_price' => 55.00],
            ['product_name' => 'Milk', 'unit_id' => 9, 'cost_price' => 60.00, 'unit_price' => 70.00],
        ];

        foreach ($products as $productData) {
            Product::create(array_merge($productData, ['user_id' => $user->id]));
        }
    }

    private function createFruitVegetableRiceProducts(User $user): void
    {
        $products = [
            // Fruits (per kilo/piece) - Fruits & Vegetables category (5)
            ['product_name' => 'Banana Lakatan', 'unit_id' => 5, 'cost_price' => 80.00, 'unit_price' => 100.00], // kg
            ['product_name' => 'Mango Carabao', 'unit_id' => 5, 'cost_price' => 120.00, 'unit_price' => 150.00],
            ['product_name' => 'Pineapple Sweet Del Monte', 'unit_id' => 1, 'cost_price' => 80.00, 'unit_price' => 100.00], // piece
            ['product_name' => 'Apple Fuji', 'unit_id' => 5, 'cost_price' => 160.00, 'unit_price' => 200.00],
            ['product_name' => 'Grapes Red Globe', 'unit_id' => 5, 'cost_price' => 200.00, 'unit_price' => 250.00],
            ['product_name' => 'Orange Valencia', 'unit_id' => 5, 'cost_price' => 100.00, 'unit_price' => 130.00],
            ['product_name' => 'Papaya Solo', 'unit_id' => 1, 'cost_price' => 40.00, 'unit_price' => 60.00],
            ['product_name' => 'Watermelon', 'unit_id' => 5, 'cost_price' => 25.00, 'unit_price' => 35.00],

            // Vegetables (per kilo/bundle) - Fruits & Vegetables category (5)
            ['product_name' => 'Tomato Fresh', 'unit_id' => 5, 'cost_price' => 60.00, 'unit_price' => 80.00],
            ['product_name' => 'Onion Red', 'unit_id' => 5, 'cost_price' => 80.00, 'unit_price' => 100.00],
            ['product_name' => 'Potato Highland', 'unit_id' => 5, 'cost_price' => 70.00, 'unit_price' => 90.00],
            ['product_name' => 'Carrots', 'unit_id' => 5, 'cost_price' => 80.00, 'unit_price' => 100.00],
            ['product_name' => 'Cabbage', 'unit_id' => 1, 'cost_price' => 40.00, 'unit_price' => 60.00],
            ['product_name' => 'Kangkong', 'unit_id' => 15, 'cost_price' => 15.00, 'unit_price' => 25.00], // bundle
            ['product_name' => 'Pechay Baguio', 'unit_id' => 15, 'cost_price' => 20.00, 'unit_price' => 30.00],
            ['product_name' => 'Sitaw (String Beans)', 'unit_id' => 5, 'cost_price' => 100.00, 'unit_price' => 120.00],

            // Rice (per sack and kilo) - Rice & Grains category (6)
            ['product_name' => 'Rice Dinorado Premium', 'unit_id' => 29, 'cost_price' => 2200.00, 'unit_price' => 2500.00], // sack (50kg)
            ['product_name' => 'Rice Sinandomeng', 'unit_id' => 29, 'cost_price' => 2000.00, 'unit_price' => 2300.00],
            ['product_name' => 'Rice Jasmine', 'unit_id' => 29, 'cost_price' => 1800.00, 'unit_price' => 2100.00],
            ['product_name' => 'Rice Dinorado (per kilo)', 'unit_id' => 5, 'cost_price' => 45.00, 'unit_price' => 52.00],
            ['product_name' => 'Rice Sinandomeng (per kilo)', 'unit_id' => 5, 'cost_price' => 42.00, 'unit_price' => 48.00],
        ];

        foreach ($products as $productData) {
            Product::create(array_merge($productData, ['user_id' => $user->id]));
        }
    }

    private function createMiniGroceryProducts(User $user): void
    {
        $products = [
            // Beverages
            ['product_name' => 'Coca Cola 1.5L', 'unit_id' => 17, 'cost_price' => 65.00, 'unit_price' => 75.00],
            ['product_name' => 'Sprite 1.5L', 'unit_id' => 17, 'cost_price' => 65.00, 'unit_price' => 75.00],
            ['product_name' => 'Royal Tru Orange 1L', 'unit_id' => 17, 'cost_price' => 45.00, 'unit_price' => 55.00],
            ['product_name' => 'Red Bull Energy Drink', 'unit_id' => 18, 'cost_price' => 45.00, 'unit_price' => 55.00],
            ['product_name' => 'C2 Green Tea Solo', 'unit_id' => 17, 'cost_price' => 18.00, 'unit_price' => 25.00],

            // Dairy Products
            ['product_name' => 'Alaska Fresh Milk 1L', 'unit_id' => 17, 'cost_price' => 85.00, 'unit_price' => 95.00],
            ['product_name' => 'Bear Brand Powdered Milk', 'unit_id' => 18, 'cost_price' => 180.00, 'unit_price' => 210.00],
            ['product_name' => 'Nestle Yogurt Cups', 'unit_id' => 1, 'cost_price' => 25.00, 'unit_price' => 35.00],
            ['product_name' => 'Eden Cheese Block', 'unit_id' => 1, 'cost_price' => 85.00, 'unit_price' => 100.00],

            // Snacks & Confectionery
            ['product_name' => 'Chippy Barbecue', 'unit_id' => 12, 'cost_price' => 35.00, 'unit_price' => 45.00],
            ['product_name' => 'Piattos Cheese', 'unit_id' => 12, 'cost_price' => 40.00, 'unit_price' => 50.00],
            ['product_name' => 'Oreo Cookies Original', 'unit_id' => 12, 'cost_price' => 45.00, 'unit_price' => 55.00],
            ['product_name' => 'Ricoa Chocolate Bar', 'unit_id' => 1, 'cost_price' => 15.00, 'unit_price' => 20.00],

            // Frozen Foods (9)
            ['product_name' => 'CDO Hotdog Jumbo', 'unit_id' => 12, 'cost_price' => 180.00, 'unit_price' => 210.00],
            ['product_name' => 'Purefoods Corned Beef', 'unit_id' => 18, 'cost_price' => 85.00, 'unit_price' => 100.00], // Canned
            ['product_name' => 'San Marino Tuna Flakes', 'unit_id' => 18, 'cost_price' => 35.00, 'unit_price' => 45.00], // Canned
            ['product_name' => 'Magnolia Ice Cream 1.5L', 'unit_id' => 1, 'cost_price' => 180.00, 'unit_price' => 220.00],

            // Household Items (14 - Cleaning Supplies)
            ['product_name' => 'Tide Powder 1kg', 'unit_id' => 12, 'cost_price' => 180.00, 'unit_price' => 210.00],
            ['product_name' => 'Joy Dishwashing Liquid', 'unit_id' => 17, 'cost_price' => 85.00, 'unit_price' => 100.00],
            ['product_name' => 'Downy Fabric Conditioner', 'unit_id' => 17, 'cost_price' => 120.00, 'unit_price' => 140.00],
        ];

        foreach ($products as $productData) {
            Product::create(array_merge($productData, ['user_id' => $user->id]));
        }
    }

    private function createSariSariProducts(User $user): void
    {
        $products = [
            // Single serve items (per piece)
            ['product_name' => 'Lucky Me Instant Pancit Canton', 'unit_id' => 1, 'cost_price' => 12.00, 'unit_price' => 15.00],
            ['product_name' => 'Nissin Cup Noodles', 'unit_id' => 1, 'cost_price' => 18.00, 'unit_price' => 22.00],
            ['product_name' => 'Skyflakes Crackers', 'unit_id' => 12, 'cost_price' => 25.00, 'unit_price' => 30.00],
            ['product_name' => 'Boy Bawang Cornick', 'unit_id' => 1, 'cost_price' => 8.00, 'unit_price' => 12.00],

            // Beverages (single serve)
            ['product_name' => 'Coca Cola 240ml', 'unit_id' => 17, 'cost_price' => 12.00, 'unit_price' => 18.00],
            ['product_name' => 'Sprite 240ml', 'unit_id' => 17, 'cost_price' => 12.00, 'unit_price' => 18.00],
            ['product_name' => 'Zesto Orange Juice', 'unit_id' => 21, 'cost_price' => 8.00, 'unit_price' => 12.00], // pouch
            ['product_name' => 'Cobra Energy Drink', 'unit_id' => 1, 'cost_price' => 10.00, 'unit_price' => 15.00],

            // Cigarettes & Tobacco (use Miscellaneous category 28)
            ['product_name' => 'Marlboro Red (per stick)', 'unit_id' => 1, 'cost_price' => 6.00, 'unit_price' => 8.00],
            ['product_name' => 'Philip Morris (per stick)', 'unit_id' => 1, 'cost_price' => 5.50, 'unit_price' => 7.50],
            ['product_name' => 'Hope Cigarettes (per stick)', 'unit_id' => 1, 'cost_price' => 4.00, 'unit_price' => 6.00],

            // Candies & Small snacks
            ['product_name' => 'Mentos Mint', 'unit_id' => 1, 'cost_price' => 3.00, 'unit_price' => 5.00],
            ['product_name' => 'Ricoa Flat Tops', 'unit_id' => 1, 'cost_price' => 2.00, 'unit_price' => 3.00],
            ['product_name' => 'White Rabbit Candy', 'unit_id' => 1, 'cost_price' => 2.50, 'unit_price' => 4.00],
            ['product_name' => 'Chocnut', 'unit_id' => 1, 'cost_price' => 1.50, 'unit_price' => 2.50],

            // Sachets & Small packs
            ['product_name' => '3-in-1 Coffee Sachet', 'unit_id' => 31, 'cost_price' => 8.00, 'unit_price' => 12.00], // sachet
            ['product_name' => 'Milo Sachet', 'unit_id' => 31, 'cost_price' => 10.00, 'unit_price' => 15.00],
            ['product_name' => 'Magic Sarap Seasoning', 'unit_id' => 31, 'cost_price' => 2.00, 'unit_price' => 3.00],
            ['product_name' => 'Ajinomoto Seasoning', 'unit_id' => 31, 'cost_price' => 1.50, 'unit_price' => 2.50],

            // Load/Credits (use Electronics category 21)
            ['product_name' => 'Smart Load P15', 'unit_id' => 1, 'cost_price' => 14.00, 'unit_price' => 15.00],
            ['product_name' => 'Smart Load P30', 'unit_id' => 1, 'cost_price' => 28.50, 'unit_price' => 30.00],
            ['product_name' => 'Globe Load P15', 'unit_id' => 1, 'cost_price' => 14.00, 'unit_price' => 15.00],
            ['product_name' => 'Globe Load P30', 'unit_id' => 1, 'cost_price' => 28.50, 'unit_price' => 30.00],
        ];

        foreach ($products as $productData) {
            Product::create(array_merge($productData, ['user_id' => $user->id]));
        }
    }
}
