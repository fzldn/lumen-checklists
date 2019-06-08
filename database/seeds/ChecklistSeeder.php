<?php

use Illuminate\Database\Seeder;
use App\Checklist;
use App\Item;

class ChecklistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Checklist::class, 10)
            ->create()
            ->each(function ($checklist) {
                $checklist->items()->saveMany(factory(Item::class, 3)->make());
            });
    }
}
