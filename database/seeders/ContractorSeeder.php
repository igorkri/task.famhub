<?php

namespace Database\Seeders;

use App\Models\Contractor;
use Illuminate\Database\Seeder;

class ContractorSeeder extends Seeder
{
    /**
     * Seed the contractors table with requisites from the act.
     */
    public function run(): void
    {
        // ФОП Кривошей І.О. (Виконавець)
        Contractor::updateOrCreate(
            ['email' => 'igorkri26@gmail.com'],
            [
            'sort' => 1,
            'name' => 'ФОП Кривошей І.О.',
            'phone' => '+380965212323',
            'email' => 'igorkri26@gmail.com',
            'type' => Contractor::TYPE_FOP,
            'full_name' => 'Кривошей Ігор Олексійович',
            'in_the_person_of' => 'ФОП Кривошей Ігоря Олексійовича',
            'is_active' => true,
            'my_company' => true,
            'description' => null,
            'dogovor' => [
                'number' => '',
                'date' => '',
            ],
            'dogovor_files' => null,
            'requisites' => [
                'name' => 'ФОП Кривошей Ігор Олексійович',
                'identification_code' => '3137600777',
                'legal_address' => 'Чернігівська область, Варвинський район, с. Кухарка вул. Перемоги, буд. 34',
                'physical_address' => 'м. Полтава вул. Пушкіна 22/14 офіс 204',
                'bank_name' => 'ПАТ "БАНК ВОСТОК"',
                'mfo' => '307123',
                'iban' => 'UA543071230000026000010537441',
                'vat_certificate' => null,
                'taxation_note' => 'Виконавець працює за спрощеною системою оподаткування. ПДВ не сплачується.',
            ],
        ]);

        // ТОВ «ІНГСОТ» (Замовник / Моя компанія)
        Contractor::updateOrCreate(
            ['email' => 'sergii.gryniuk@gmail.com'],
            [
            'sort' => 2,
            'name' => 'ТОВ «ІНГСОТ»',
            'phone' => '+380683777272',
            'email' => 'sergii.gryniuk@gmail.com',
            'type' => Contractor::TYPE_TOV,
            'full_name' => 'Гринюк Сергій Анатолійович',
            'in_the_person_of' => 'Гринюк Сергія Анатолійовича',
            'is_active' => true,
            'my_company' => false,
            'description' => null,
            'dogovor' => [
                'number' => 'IT/01',
                'date' => '2023-05-20',
            ],
            'dogovor_files' => null,
            'requisites' => [
                'name' => 'ТОВ «ІНГСОТ»',
                'identification_code' => '37400930',
                'legal_address' => '01001, м. Київ, вул. Еспланадна, 20',
                'physical_address' => '01001, м. Київ, вул. Еспланадна, 20',
                'bank_name' => 'ПАТ «УкрСиббанк»',
                'mfo' => '351005',
                'iban' => 'UA073510050000026000301435500',
                'vat_certificate' => '№100311766',
                'individual_tax_number' => '№374009326557',
                'director' => 'Гринюк Сергій Анатолійович',
                'taxation_note' => null,
            ],
        ]);
    }
}
