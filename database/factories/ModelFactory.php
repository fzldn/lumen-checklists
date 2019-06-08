<?php
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->email,
    ];
});

$factory->define(App\Template::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->words(3, true)
    ];
});

$factory->define(App\TemplateChecklist::class, function (Faker\Generator $faker) {
    return [
        'description' => $faker->words(5, true),
        'due_interval' => $faker->numberBetween(1, 5),
        'due_unit' => $faker->randomElement(['day', 'week', 'month']),
        'urgency' => $faker->numberBetween(0, 3)
    ];
});

$factory->define(App\TemplateItem::class, function (Faker\Generator $faker) {
    return [
        'description' => $faker->words(5, true),
        'due_interval' => $faker->numberBetween(1, 5),
        'due_unit' => $faker->randomElement(['minute', 'hour']),
        'urgency' => $faker->numberBetween(0, 3)
    ];
});

$factory->define(App\Checklist::class, function (Faker\Generator $faker) {
    return [
        'object_domain' => $faker->word,
        'object_id' => $faker->numberBetween(1, 100),
        'description' => $faker->words(5, true),
        'due' => Carbon::now()->add($faker->numberBetween(1, 5), $faker->randomElement(['day', 'week', 'month'])),
        'urgency' => $faker->numberBetween(0, 3)
    ];
});

$factory->define(App\Item::class, function (Faker\Generator $faker) {
    return [
        'description' => $faker->words(5, true),
        'due' => Carbon::now()->add($faker->numberBetween(1, 5), $faker->randomElement(['minute', 'hour'])),
        'urgency' => $faker->numberBetween(0, 3)
    ];
});
