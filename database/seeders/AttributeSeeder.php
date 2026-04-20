<?php
namespace Database\Seeders;

use App\Models\AttributeGroup;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            // group name => [type, values]
            'Color' => [
                'type'   => 'color',
                'values' => [
                    ['value' => 'Black',  'meta' => '#000000'],
                    ['value' => 'White',  'meta' => '#FFFFFF'],
                    ['value' => 'Red',    'meta' => '#FF0000'],
                    ['value' => 'Blue',   'meta' => '#0000FF'],
                    ['value' => 'Green',  'meta' => '#008000'],
                    ['value' => 'Yellow', 'meta' => '#FFFF00'],
                ],
            ],
            'Size' => [
                'type'   => 'button',
                'values' => [
                    ['value' => 'XS',  'meta' => null],
                    ['value' => 'S',   'meta' => null],
                    ['value' => 'M',   'meta' => null],
                    ['value' => 'L',   'meta' => null],
                    ['value' => 'XL',  'meta' => null],
                    ['value' => 'XXL', 'meta' => null],
                ],
            ],
            'Storage' => [
                'type'   => 'select',
                'values' => [
                    ['value' => '64GB',  'meta' => null],
                    ['value' => '128GB', 'meta' => null],
                    ['value' => '256GB', 'meta' => null],
                    ['value' => '512GB', 'meta' => null],
                    ['value' => '1TB',   'meta' => null],
                ],
            ],
            'RAM' => [
                'type'   => 'select',
                'values' => [
                    ['value' => '4GB',  'meta' => null],
                    ['value' => '6GB',  'meta' => null],
                    ['value' => '8GB',  'meta' => null],
                    ['value' => '12GB', 'meta' => null],
                    ['value' => '16GB', 'meta' => null],
                ],
            ],
        ];

        foreach ($attributes as $groupName => $data) {
            $group = AttributeGroup::create([
                'name' => $groupName,
                'type' => $data['type'],
            ]);

            foreach ($data['values'] as $val) {
                AttributeValue::create([
                    'group_id' => $group->id,
                    'value'    => $val['value'],
                    'meta'     => $val['meta'],
                ]);
            }
        }

        $this->command->info('✅ Attributes seeded');
    }
}