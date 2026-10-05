<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\Image;

class ImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $properties = Property::all();

        if ($properties->isEmpty()) {
            return;
        }

        // Add exactly 3 images for every property
        foreach ($properties as $property) {
            for ($i = 1; $i <= 3; $i++) {
                
                // Set the first image as the primary image
                $isPrimary = ($i === 1);
                
                // We use Picsum for reliable placeholder images, seeded by property ID so they don't change randomly on refresh
                $imageUrl = "https://picsum.photos/seed/property_{$property->id}_{$i}/800/600";
                
                Image::firstOrCreate(
                    [
                        'imageable_id'   => $property->id,
                        'imageable_type' => Property::class,
                        'sort_order'     => $i, // Ensure we don't duplicate the same sort order
                    ],
                    [
                        'image_url'  => $imageUrl,
                        'image_name' => "Property Image {$i}",
                        'image_hash' => md5("property_{$property->id}_{$i}"),
                        'is_primary' => $isPrimary,
                    ]
                );
            }
        }

        // RoomType Images
        $roomTypes = RoomType::all();
        if ($roomTypes->isNotEmpty()) {
            foreach ($roomTypes as $roomType) {
                for ($i = 1; $i <= 3; $i++) {
                    $isPrimary = ($i === 1);
                    $imageUrl = "https://picsum.photos/seed/roomtype_{$roomType->id}_{$i}/800/600";
                    
                    Image::firstOrCreate(
                        [
                            'imageable_id'   => $roomType->id,
                            'imageable_type' => RoomType::class,
                            'sort_order'     => $i,
                        ],
                        [
                            'image_url'  => $imageUrl,
                            'image_name' => "Room Type Image {$i}",
                            'image_hash' => md5("roomtype_{$roomType->id}_{$i}"),
                            'is_primary' => $isPrimary,
                        ]
                    );
                }
            }
        }
    }
}
