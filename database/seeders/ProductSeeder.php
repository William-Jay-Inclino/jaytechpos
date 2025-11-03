<?php

namespace Database\Seeders;

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
        $users = User::all();

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
            ['product_name' => 'Orange Juice', 'category_id' => 1, 'unit_id' => 17, 'cost_price' => 85.00, 'unit_price' => 95.00],
            ['product_name' => 'Cola Drink', 'category_id' => 1, 'unit_id' => 17, 'cost_price' => 25.00, 'unit_price' => 30.00],
            ['product_name' => 'Energy Drink', 'category_id' => 1, 'unit_id' => 18, 'cost_price' => 45.00, 'unit_price' => 55.00],
            ['product_name' => 'Milk', 'category_id' => 3, 'unit_id' => 9, 'cost_price' => 60.00, 'unit_price' => 70.00],
        ];

        foreach ($products as $productData) {
            Product::create(array_merge($productData, ['user_id' => $user->id]));
        }
    }

    private function createFruitVegetableRiceProducts(User $user): void
    {
        $products = [
            // Fruits (per kilo/piece) - Fruits & Vegetables category (5)
            ['product_name' => 'Banana Lakatan', 'category_id' => 5, 'unit_id' => 5, 'cost_price' => 80.00, 'unit_price' => 100.00], // kg
            ['product_name' => 'Mango Carabao', 'category_id' => 5, 'unit_id' => 5, 'cost_price' => 120.00, 'unit_price' => 150.00],
            ['product_name' => 'Pineapple Sweet Del Monte', 'category_id' => 5, 'unit_id' => 1, 'cost_price' => 80.00, 'unit_price' => 100.00], // piece
            ['product_name' => 'Apple Fuji', 'category_id' => 5, 'unit_id' => 5, 'cost_price' => 160.00, 'unit_price' => 200.00],
            ['product_name' => 'Grapes Red Globe', 'category_id' => 5, 'unit_id' => 5, 'cost_price' => 200.00, 'unit_price' => 250.00],
            ['product_name' => 'Orange Valencia', 'category_id' => 5, 'unit_id' => 5, 'cost_price' => 100.00, 'unit_price' => 130.00],
            ['product_name' => 'Papaya Solo', 'category_id' => 5, 'unit_id' => 1, 'cost_price' => 40.00, 'unit_price' => 60.00],
            ['product_name' => 'Watermelon', 'category_id' => 5, 'unit_id' => 5, 'cost_price' => 25.00, 'unit_price' => 35.00],

            // Vegetables (per kilo/bundle) - Fruits & Vegetables category (5)
            ['product_name' => 'Tomato Fresh', 'category_id' => 5, 'unit_id' => 5, 'cost_price' => 60.00, 'unit_price' => 80.00],
            ['product_name' => 'Onion Red', 'category_id' => 5, 'unit_id' => 5, 'cost_price' => 80.00, 'unit_price' => 100.00],
            ['product_name' => 'Potato Highland', 'category_id' => 5, 'unit_id' => 5, 'cost_price' => 70.00, 'unit_price' => 90.00],
            ['product_name' => 'Carrots', 'category_id' => 5, 'unit_id' => 5, 'cost_price' => 80.00, 'unit_price' => 100.00],
            ['product_name' => 'Cabbage', 'category_id' => 5, 'unit_id' => 1, 'cost_price' => 40.00, 'unit_price' => 60.00],
            ['product_name' => 'Kangkong', 'category_id' => 5, 'unit_id' => 15, 'cost_price' => 15.00, 'unit_price' => 25.00], // bundle
            ['product_name' => 'Pechay Baguio', 'category_id' => 5, 'unit_id' => 15, 'cost_price' => 20.00, 'unit_price' => 30.00],
            ['product_name' => 'Sitaw (String Beans)', 'category_id' => 5, 'unit_id' => 5, 'cost_price' => 100.00, 'unit_price' => 120.00],

            // Rice (per sack and kilo) - Rice & Grains category (6)
            ['product_name' => 'Rice Dinorado Premium', 'category_id' => 6, 'unit_id' => 29, 'cost_price' => 2200.00, 'unit_price' => 2500.00], // sack (50kg)
            ['product_name' => 'Rice Sinandomeng', 'category_id' => 6, 'unit_id' => 29, 'cost_price' => 2000.00, 'unit_price' => 2300.00],
            ['product_name' => 'Rice Jasmine', 'category_id' => 6, 'unit_id' => 29, 'cost_price' => 1800.00, 'unit_price' => 2100.00],
            ['product_name' => 'Rice Dinorado (per kilo)', 'category_id' => 6, 'unit_id' => 5, 'cost_price' => 45.00, 'unit_price' => 52.00],
            ['product_name' => 'Rice Sinandomeng (per kilo)', 'category_id' => 6, 'unit_id' => 5, 'cost_price' => 42.00, 'unit_price' => 48.00],
        ];

        foreach ($products as $productData) {
            Product::create(array_merge($productData, ['user_id' => $user->id]));
        }
    }

    private function createMiniGroceryProducts(User $user): void
    {
        $products = [
            // Beverages
            ['product_name' => 'Coca Cola 1.5L', 'category_id' => 1, 'unit_id' => 17, 'cost_price' => 65.00, 'unit_price' => 75.00],
            ['product_name' => 'Sprite 1.5L', 'category_id' => 1, 'unit_id' => 17, 'cost_price' => 65.00, 'unit_price' => 75.00],
            ['product_name' => 'Royal Tru Orange 1L', 'category_id' => 1, 'unit_id' => 17, 'cost_price' => 45.00, 'unit_price' => 55.00],
            ['product_name' => 'Red Bull Energy Drink', 'category_id' => 1, 'unit_id' => 18, 'cost_price' => 45.00, 'unit_price' => 55.00],
            ['product_name' => 'C2 Green Tea Solo', 'category_id' => 1, 'unit_id' => 17, 'cost_price' => 18.00, 'unit_price' => 25.00],

            // Dairy Products
            ['product_name' => 'Alaska Fresh Milk 1L', 'category_id' => 3, 'unit_id' => 17, 'cost_price' => 85.00, 'unit_price' => 95.00],
            ['product_name' => 'Bear Brand Powdered Milk', 'category_id' => 3, 'unit_id' => 18, 'cost_price' => 180.00, 'unit_price' => 210.00],
            ['product_name' => 'Nestle Yogurt Cups', 'category_id' => 3, 'unit_id' => 1, 'cost_price' => 25.00, 'unit_price' => 35.00],
            ['product_name' => 'Eden Cheese Block', 'category_id' => 3, 'unit_id' => 1, 'cost_price' => 85.00, 'unit_price' => 100.00],

            // Snacks & Confectionery
            ['product_name' => 'Chippy Barbecue', 'category_id' => 2, 'unit_id' => 12, 'cost_price' => 35.00, 'unit_price' => 45.00],
            ['product_name' => 'Piattos Cheese', 'category_id' => 2, 'unit_id' => 12, 'cost_price' => 40.00, 'unit_price' => 50.00],
            ['product_name' => 'Oreo Cookies Original', 'category_id' => 2, 'unit_id' => 12, 'cost_price' => 45.00, 'unit_price' => 55.00],
            ['product_name' => 'Ricoa Chocolate Bar', 'category_id' => 2, 'unit_id' => 1, 'cost_price' => 15.00, 'unit_price' => 20.00],

            // Frozen Foods (9)
            ['product_name' => 'CDO Hotdog Jumbo', 'category_id' => 9, 'unit_id' => 12, 'cost_price' => 180.00, 'unit_price' => 210.00],
            ['product_name' => 'Purefoods Corned Beef', 'category_id' => 7, 'unit_id' => 18, 'cost_price' => 85.00, 'unit_price' => 100.00], // Canned
            ['product_name' => 'San Marino Tuna Flakes', 'category_id' => 7, 'unit_id' => 18, 'cost_price' => 35.00, 'unit_price' => 45.00], // Canned
            ['product_name' => 'Magnolia Ice Cream 1.5L', 'category_id' => 9, 'unit_id' => 1, 'cost_price' => 180.00, 'unit_price' => 220.00],

            // Household Items (14 - Cleaning Supplies)
            ['product_name' => 'Tide Powder 1kg', 'category_id' => 14, 'unit_id' => 12, 'cost_price' => 180.00, 'unit_price' => 210.00],
            ['product_name' => 'Joy Dishwashing Liquid', 'category_id' => 14, 'unit_id' => 17, 'cost_price' => 85.00, 'unit_price' => 100.00],
            ['product_name' => 'Downy Fabric Conditioner', 'category_id' => 14, 'unit_id' => 17, 'cost_price' => 120.00, 'unit_price' => 140.00],
        ];

        foreach ($products as $productData) {
            Product::create(array_merge($productData, ['user_id' => $user->id]));
        }
    }

    private function createSariSariProducts(User $user): void
    {
        $products = [
            // Single serve items (per piece)
            ['product_name' => 'Lucky Me Instant Pancit Canton', 'category_id' => 2, 'unit_id' => 1, 'cost_price' => 12.00, 'unit_price' => 15.00],
            ['product_name' => 'Nissin Cup Noodles', 'category_id' => 2, 'unit_id' => 1, 'cost_price' => 18.00, 'unit_price' => 22.00],
            ['product_name' => 'Skyflakes Crackers', 'category_id' => 2, 'unit_id' => 12, 'cost_price' => 25.00, 'unit_price' => 30.00],
            ['product_name' => 'Boy Bawang Cornick', 'category_id' => 2, 'unit_id' => 1, 'cost_price' => 8.00, 'unit_price' => 12.00],

            // Beverages (single serve)
            ['product_name' => 'Coca Cola 240ml', 'category_id' => 1, 'unit_id' => 17, 'cost_price' => 12.00, 'unit_price' => 18.00],
            ['product_name' => 'Sprite 240ml', 'category_id' => 1, 'unit_id' => 17, 'cost_price' => 12.00, 'unit_price' => 18.00],
            ['product_name' => 'Zesto Orange Juice', 'category_id' => 1, 'unit_id' => 21, 'cost_price' => 8.00, 'unit_price' => 12.00], // pouch
            ['product_name' => 'Cobra Energy Drink', 'category_id' => 1, 'unit_id' => 1, 'cost_price' => 10.00, 'unit_price' => 15.00],

            // Cigarettes & Tobacco (use Miscellaneous category 28)
            ['product_name' => 'Marlboro Red (per stick)', 'category_id' => 28, 'unit_id' => 1, 'cost_price' => 6.00, 'unit_price' => 8.00],
            ['product_name' => 'Philip Morris (per stick)', 'category_id' => 28, 'unit_id' => 1, 'cost_price' => 5.50, 'unit_price' => 7.50],
            ['product_name' => 'Hope Cigarettes (per stick)', 'category_id' => 28, 'unit_id' => 1, 'cost_price' => 4.00, 'unit_price' => 6.00],

            // Candies & Small snacks
            ['product_name' => 'Mentos Mint', 'category_id' => 2, 'unit_id' => 1, 'cost_price' => 3.00, 'unit_price' => 5.00],
            ['product_name' => 'Ricoa Flat Tops', 'category_id' => 2, 'unit_id' => 1, 'cost_price' => 2.00, 'unit_price' => 3.00],
            ['product_name' => 'White Rabbit Candy', 'category_id' => 2, 'unit_id' => 1, 'cost_price' => 2.50, 'unit_price' => 4.00],
            ['product_name' => 'Chocnut', 'category_id' => 2, 'unit_id' => 1, 'cost_price' => 1.50, 'unit_price' => 2.50],

            // Sachets & Small packs
            ['product_name' => '3-in-1 Coffee Sachet', 'category_id' => 1, 'unit_id' => 31, 'cost_price' => 8.00, 'unit_price' => 12.00], // sachet
            ['product_name' => 'Milo Sachet', 'category_id' => 1, 'unit_id' => 31, 'cost_price' => 10.00, 'unit_price' => 15.00],
            ['product_name' => 'Magic Sarap Seasoning', 'category_id' => 8, 'unit_id' => 31, 'cost_price' => 2.00, 'unit_price' => 3.00],
            ['product_name' => 'Ajinomoto Seasoning', 'category_id' => 8, 'unit_id' => 31, 'cost_price' => 1.50, 'unit_price' => 2.50],

            // Load/Credits (use Electronics category 21)
            ['product_name' => 'Smart Load P15', 'category_id' => 21, 'unit_id' => 1, 'cost_price' => 14.00, 'unit_price' => 15.00],
            ['product_name' => 'Smart Load P30', 'category_id' => 21, 'unit_id' => 1, 'cost_price' => 28.50, 'unit_price' => 30.00],
            ['product_name' => 'Globe Load P15', 'category_id' => 21, 'unit_id' => 1, 'cost_price' => 14.00, 'unit_price' => 15.00],
            ['product_name' => 'Globe Load P30', 'category_id' => 21, 'unit_id' => 1, 'cost_price' => 28.50, 'unit_price' => 30.00],
        ];

        foreach ($products as $productData) {
            Product::create(array_merge($productData, ['user_id' => $user->id]));
        }
    }
}
