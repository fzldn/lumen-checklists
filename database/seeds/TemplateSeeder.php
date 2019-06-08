<?php

use Illuminate\Database\Seeder;
use App\Template;
use App\TemplateChecklist;
use App\TemplateItem;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Template::class, 3)
            ->create()
            ->each(function ($template) {
                $template->checklist()->save(factory(TemplateChecklist::class)->make());
                $template->items()->saveMany(factory(TemplateItem::class, 3)->make());
            });
    }
}
