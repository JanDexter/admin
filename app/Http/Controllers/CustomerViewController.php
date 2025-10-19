<?php

namespace App\Http\Controllers;

use App\Models\SpaceType;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CustomerViewController extends Controller
{
    public function __invoke()
    {
        $spaceTypes = SpaceType::select(
                'id',
                'name',
                'description',
                'default_price',
                'hourly_rate',
                'default_discount_hours',
                'default_discount_percentage',
                'available_slots',
                'total_slots'
            )
            ->orderBy('name')
            ->get()
            ->map(function (SpaceType $type) {
                $price = $type->hourly_rate ?? $type->default_price;

                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'slug' => Str::slug($type->name),
                    'description' => $type->description,
                    'price_per_hour' => $price,
                    'discount_hours' => $type->default_discount_hours,
                    'discount_percentage' => $type->default_discount_percentage,
                    'available_slots' => $type->available_slots,
                    'total_slots' => $type->total_slots,
                ];
            });

        return Inertia::render('CustomerView/Index', [
            'spaceTypes' => $spaceTypes,
        ]);
    }
}
