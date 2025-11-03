<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Filipino first names
        $filipinoFirstNames = [
            'Juan', 'Maria', 'Jose', 'Ana', 'Antonio', 'Rosa', 'Francisco', 'Elena', 'Pedro', 'Carmen',
            'Manuel', 'Luz', 'Roberto', 'Esperanza', 'Ricardo', 'Dolores', 'Eduardo', 'Teresa', 'Carlos', 'Josefa',
            'Luis', 'Mercedes', 'Alejandro', 'Soledad', 'Miguel', 'Concepcion', 'Rafael', 'Pilar', 'Daniel', 'Victoria',
            'Fernando', 'Rosario', 'Jorge', 'Gloria', 'Raul', 'Cristina', 'Enrique', 'Remedios', 'Mario', 'Milagros',
            'Alberto', 'Natividad', 'Sergio', 'Felicidad', 'Arturo', 'Angelita', 'Ruben', 'Corazon', 'Ernesto', 'Pacita',
            'Romeo', 'Leticia', 'Rodolfo', 'Erlinda', 'Armando', 'Lourdes', 'Felipe', 'Norma', 'Emilio', 'Nenita',
        ];

        // Filipino last names
        $filipinoLastNames = [
            'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Mendoza', 'Torres', 'Tañedo', 'Castillo',
            'Lim', 'Tan', 'Gonzales', 'Lopez', 'Hernandez', 'Aquino', 'Valdez', 'Ramos', 'Flores', 'Santiago',
            'Jimenez', 'Morales', 'Diaz', 'Castro', 'Dela Cruz', 'Pascual', 'Soriano', 'Mercado', 'Aguilar', 'Medina',
            'Villanueva', 'Alvarez', 'Romero', 'Gutierrez', 'Navarro', 'Perez', 'Rivera', 'Fernandez', 'Marquez', 'Luna',
            'Cabrera', 'Vargas', 'De Leon', 'Salazar', 'Miranda', 'Velasco', 'Tolentino', 'Valencia', 'Francisco', 'Domingo',
        ];

        // Get all users
        $users = User::all();

        foreach ($users as $user) {
            // Generate 50+ customers per user
            $customerCount = rand(50, 65);

            for ($i = 0; $i < $customerCount; $i++) {
                $firstName = $filipinoFirstNames[array_rand($filipinoFirstNames)];
                $lastName = $filipinoLastNames[array_rand($filipinoLastNames)];
                $fullName = $firstName.' '.$lastName;

                // Generate realistic Philippine mobile number
                $mobilePrefix = ['0917', '0918', '0919', '0920', '0921', '0928', '0929', '0939', '0938', '0905', '0906', '0915', '0916', '0926', '0927', '0935', '0936', '0937', '0994', '0999'];
                $mobileNumber = $mobilePrefix[array_rand($mobilePrefix)].str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);

                // Interest rate between 2-5%
                $interestRate = rand(200, 500) / 100;

                // Some customers will have utang (30% chance)
                $hasUtang = rand(1, 100) <= 30;

                Customer::factory()
                    ->for($user)
                    ->withCustomInterestRate($interestRate)
                    ->create([
                        'name' => $fullName,
                        'mobile_number' => $mobileNumber,
                        'has_utang' => $hasUtang,
                    ]);
            }

            $this->command->info("Created {$customerCount} customers for user: {$user->name}");
        }
    }
}
