<?php
use Laravel\Lumen\Testing\DatabaseMigrations;
use App\User;
use App\Template;
use App\TemplateChecklist;
use App\TemplateItem;
use App\Checklist;

class TemplateTest extends TestCase
{
    use DatabaseMigrations;

    public function testIndexUnauthenticated()
    {
        $this->json('GET', '/checklists/templates');
        $this->assertResponseStatus(401);
    }

    public function testIndex()
    {
        $user = factory(User::class)->make();
        $this->actingAs($user)->json('GET', '/checklists/templates');
        $this->assertResponseStatus(200);
    }

    public function testIndexStructure()
    {
        $user = factory(User::class)->make();

        $templateTotal = 20;
        factory(Template::class, $templateTotal)->create()->each(function ($template) {
            $template->checklist()->save(factory(TemplateChecklist::class)->make());
            $template->items()->saveMany(factory(TemplateItem::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', '/checklists/templates');
        $this->seeJsonStructure([
            'data' => [
                [
                    'type',
                    'id',
                    'name',
                    'checklist' => ['id', 'description'],
                    'items' => [
                        ['id', 'description']
                    ],
                ]
            ],
            'meta' => ['count', 'total'],
            'links' => ['first', 'prev', 'next', 'last']
        ]);
    }

    public function testIndexPaging()
    {
        $user = factory(User::class)->make();

        $templateTotal = 20;
        factory(Template::class, $templateTotal)->create()->each(function ($template) {
            $template->checklist()->save(factory(TemplateChecklist::class)->make());
            $template->items()->saveMany(factory(TemplateItem::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', '/checklists/templates', [
            'page' => [
                'limit' => 5
            ]
        ]);
        $response = json_decode($this->response->getContent(), true);

        // templates count match with response
        $this->assertEquals($templateTotal, $response['meta']['total']);
        $this->assertEquals(5, $response['meta']['count']);
        $this->assertEquals(5, count($response['data']));
    }

    public function testIndexSortingAsc()
    {
        $user = factory(User::class)->make();

        $templateTotal = 20;
        factory(Template::class, $templateTotal)->create()->each(function ($template) {
            $template->checklist()->save(factory(TemplateChecklist::class)->make());
            $template->items()->saveMany(factory(TemplateItem::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', '/checklists/templates', ['sort' => 'id']);
        $response = json_decode($this->response->getContent(), true);

        $lastId = null;
        $sorted = true;
        foreach ($response['data'] as $value) {
            if ($lastId !== null) {
                if ($lastId > $value['id']) {
                    $sorted = false;
                }
            }
            $lastId = $value['id'];
        }
        $this->assertTrue($sorted);
    }

    public function testIndexSortingDesc()
    {
        $user = factory(User::class)->make();

        $templateTotal = 20;
        factory(Template::class, $templateTotal)->create()->each(function ($template) {
            $template->checklist()->save(factory(TemplateChecklist::class)->make());
            $template->items()->saveMany(factory(TemplateItem::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', '/checklists/templates', ['sort' => '-id']);
        $response = json_decode($this->response->getContent(), true);

        $lastId = null;
        $sorted = true;
        foreach ($response['data'] as $value) {
            if ($lastId !== null) {
                if ($lastId < $value['id']) {
                    $sorted = false;
                }
            }
            $lastId = $value['id'];
        }
        $this->assertTrue($sorted);
    }

    public function testStoreUnauthenticated()
    {
        $this->json('POST', '/checklists/templates');
        $this->assertResponseStatus(401);
    }

    public function testStore()
    {
        $faker = Faker\Factory::create();
        $user = factory(User::class)->make();
        $params = [
            'data' => [
                'attributes' => [
                    "name" => $faker->words(3, true),
                    "checklist" => [
                        'description' => $faker->words(5, true),
                        'due_interval' => $faker->numberBetween(1, 3),
                        'due_unit' => $faker->randomElement(['day', 'week', 'month'])
                    ],
                    'items' => [
                        [
                            'description' => $faker->words(4, true),
                            'due_interval' => $faker->numberBetween(1, 5),
                            'due_unit' => $faker->randomElement(['minute', 'hour'])
                        ]
                    ]
                ]
            ]
        ];
        $this->actingAs($user)->json('POST', '/checklists/templates', $params);
        $this->assertResponseStatus(201);
        $this->seeInDatabase('templates', ['name' => $params['data']['attributes']['name']]);
        $this->seeInDatabase('template_checklists', ['description' => $params['data']['attributes']['checklist']['description']]);
        $this->seeInDatabase('template_items', ['description' => $params['data']['attributes']['items'][0]['description']]);
    }

    public function testShowUnauthenticated()
    {
        $this->json('GET', "/checklists/templates/1");
        $this->assertResponseStatus(401);
    }

    public function testShow()
    {
        $user = factory(User::class)->make();
        factory(Template::class)->create()->each(function ($template) {
            $template->checklist()->save(factory(TemplateChecklist::class)->make());
            $template->items()->saveMany(factory(TemplateItem::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', "/checklists/templates/1");
        $this->assertResponseStatus(200);
    }

    public function testShowStructure()
    {
        $user = factory(User::class)->make();
        factory(Template::class)->create()->each(function ($template) {
            $template->checklist()->save(factory(TemplateChecklist::class)->make());
            $template->items()->saveMany(factory(TemplateItem::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', "/checklists/templates/1");
        $this->seeJsonStructure([
            'data' => [
                'type',
                'id',
                'name',
                'checklist' => [
                    'id',
                    'description'
                ],
                'items' => [
                    [
                        'id',
                        'description'
                    ]
                ],
                'links' => ['self']
            ],
        ]);
    }

    public function testShowNotFound()
    {
        $user = factory(User::class)->make();

        $this->actingAs($user)->json('GET', "/checklists/templates/1");
        $this->assertResponseStatus(404);
    }

    public function testUpdateUnauthenticated()
    {
        $this->json('PATCH', "/checklists/templates/1");
        $this->assertResponseStatus(401);
    }

    public function testUpdate()
    {
        $faker = Faker\Factory::create();
        $user = factory(User::class)->make();
        factory(Template::class)
            ->create([
                'name' => $faker->words(3, true)
            ])
            ->each(function ($template) {
                $template->checklist()->save(factory(TemplateChecklist::class)->make());
                $template->items()->saveMany(factory(TemplateItem::class, 3)->make());
            });
        $template = Template::find(1);
        $tempChecklist = $template->checklist;
        $tempItems = $template->items;

        $params = [
            'data' => [
                'attributes' => [
                    "name" => $faker->words(3, true),
                    "checklist" => [
                        'description' => $faker->words(5, true),
                        'due_interval' => $faker->numberBetween(1, 3),
                        'due_unit' => $faker->randomElement(['day', 'week', 'month'])
                    ],
                    'items' => [
                        [
                            'description' => $faker->words(4, true),
                            'due_interval' => $faker->numberBetween(1, 5),
                            'due_unit' => $faker->randomElement(['minute', 'hour'])
                        ]
                    ]
                ]
            ]
        ];
        $this->actingAs($user)->json('PATCH', "/checklists/templates/{$template->id}", $params);
        $this->assertResponseStatus(200);
        $templateNew = Template::find(1);
        $this->assertNotEquals($template->name, $templateNew->name);
        $this->assertNotEquals($tempChecklist->description, $templateNew->checklist->description);
        $this->assertNotEquals($tempItems->count(), $templateNew->items->count());
    }

    public function testUpdateNotFound()
    {
        $faker = Faker\Factory::create();
        $user = factory(User::class)->make();

        $params = [
            'data' => [
                'attributes' => [
                    "name" => $faker->words(3, true),
                    "checklist" => [
                        'description' => $faker->words(5, true),
                        'due_interval' => $faker->numberBetween(1, 3),
                        'due_unit' => $faker->randomElement(['day', 'week', 'month'])
                    ],
                    'items' => [
                        [
                            'description' => $faker->words(4, true),
                            'due_interval' => $faker->numberBetween(1, 5),
                            'due_unit' => $faker->randomElement(['minute', 'hour'])
                        ]
                    ]
                ]
            ]
        ];
        $this->actingAs($user)->json('PATCH', "/checklists/templates/1", $params);
        $this->assertResponseStatus(404);
    }

    public function testDeleteUnauthenticated()
    {
        $this->json('DELETE', '/checklists/templates/1');
        $this->assertResponseStatus(401);
    }

    public function testDelete()
    {
        $user = factory(User::class)->make();
        factory(Template::class)->create()->each(function ($template) {
            $template->checklist()->save(factory(TemplateChecklist::class)->make());
            $template->items()->saveMany(factory(TemplateItem::class, 3)->make());
        });

        $this->actingAs($user)->json('DELETE', '/checklists/templates/1');
        $this->assertResponseStatus(204);
        $this->notSeeInDatabase('templates', [
            'id' => 1
        ]);
    }

    public function testDeleteNotFound()
    {
        $user = factory(User::class)->make();

        $this->actingAs($user)->json('DELETE', '/checklists/templates/1');
        $this->assertResponseStatus(404);
    }

    public function testAssignsUnauthenticated()
    {
        $this->json('POST', '/checklists/templates/1/assigns');
        $this->assertResponseStatus(401);
    }

    public function testAssigns()
    {
        $faker = Faker\Factory::create();
        $user = factory(User::class)->make();
        factory(Template::class)->create()->each(function ($template) {
            $template->checklist()->save(factory(TemplateChecklist::class)->make());
            $template->items()->save(factory(TemplateItem::class)->make());
        });

        $params = [
            'data' => [
                [
                    'attributes' => [
                        'object_id' => $faker->numberBetween(1, 5),
                        'object_domain' => $faker->word
                    ]
                ],
                [
                    'attributes' => [
                        'object_id' => $faker->numberBetween(6, 10),
                        'object_domain' => $faker->word
                    ]
                ],
            ]
        ];
        $this->actingAs($user)->json('POST', '/checklists/templates/1/assigns', $params);
        $this->assertResponseStatus(201);
        $this->seeInDatabase('checklists', ['object_domain' => $params['data'][0]['attributes']['object_domain']]);
        $checklist = Checklist::where('object_domain', $params['data'][0]['attributes']['object_domain'])->first();
        $this->seeInDatabase('items', ['checklist_id' => $checklist->id]);
    }
}
