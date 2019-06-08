<?php
use Laravel\Lumen\Testing\DatabaseMigrations;
use App\User;
use App\Checklist;
use App\Item;
use Carbon\Carbon;

class ChecklistTest extends TestCase
{
    use DatabaseMigrations;

    public function testIndexUnauthenticated()
    {
        $this->json('GET', '/checklists');
        $this->assertResponseStatus(401);
    }

    public function testIndex()
    {
        $user = factory(User::class)->make();
        $this->actingAs($user)->json('GET', '/checklists');
        $this->assertResponseStatus(200);
    }

    public function testIndexStructure()
    {
        $user = factory(User::class)->make();

        $checklistTotal = 20;
        factory(Checklist::class, $checklistTotal)->create()->each(function ($checklist) {
            $checklist->items()->saveMany(factory(Item::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', '/checklists');
        $this->seeJsonStructure([
            'data' => [
                [
                    'type',
                    'id',
                    'attributes' => [
                        'object_domain',
                        'object_id',
                        'description',
                    ],
                    'links' => [
                        'self'
                    ]
                ]
            ],
            'meta' => ['count', 'total'],
            'links' => ['first', 'prev', 'next', 'last']
        ]);

        $this->actingAs($user)->json('GET', '/checklists', [
            'include' => 'items'
        ]);
        $this->seeJsonStructure([
            'data' => [
                [
                    'type',
                    'id',
                    'attributes' => [
                        'object_domain',
                        'object_id',
                        'description',
                        'items' => [
                            [
                                'id',
                                'description'
                            ]
                        ]
                    ],
                    'links' => [
                        'self'
                    ]
                ]
            ],
            'meta' => ['count', 'total'],
            'links' => ['first', 'prev', 'next', 'last']
        ]);
    }

    public function testIndexPaging()
    {
        $user = factory(User::class)->make();

        $checklistTotal = 20;
        factory(Checklist::class, $checklistTotal)->create()->each(function ($checklist) {
            $checklist->items()->saveMany(factory(Item::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', '/checklists', [
            'page' => [
                'limit' => 5
            ]
        ]);
        $response = json_decode($this->response->getContent(), true);

        // templates count match with response
        $this->assertEquals($checklistTotal, $response['meta']['total']);
        $this->assertEquals(5, $response['meta']['count']);
        $this->assertEquals(5, count($response['data']));
    }

    public function testIndexSortingAsc()
    {
        $user = factory(User::class)->make();

        $checklistTotal = 20;
        factory(Checklist::class, $checklistTotal)->create()->each(function ($checklist) {
            $checklist->items()->saveMany(factory(Item::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', '/checklists', ['sort' => 'id']);
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

        $checklistTotal = 20;
        factory(Checklist::class, $checklistTotal)->create()->each(function ($checklist) {
            $checklist->items()->saveMany(factory(Item::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', '/checklists', ['sort' => '-id']);
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
        $this->json('POST', '/checklists');
        $this->assertResponseStatus(401);
    }

    public function testStore()
    {
        $faker = Faker\Factory::create();
        $user = factory(User::class)->make();
        $params = [
            'data' => [
                'attributes' => [
                    'object_domain' => $faker->word,
                    'object_id' => (string)$faker->numberBetween(1, 100),
                    'description' => $faker->words(5, true),
                    'due' => Carbon::now()->add($faker->numberBetween(1, 5), $faker->randomElement(['day', 'week', 'month']))->format('c'),
                    'urgency' => $faker->numberBetween(0, 3),
                    'items' => [
                        $faker->words(4, true),
                        $faker->words(4, true),
                        $faker->words(4, true),
                    ],
                    'task_id' => $faker->numberBetween(1, 100)
                ]
            ]
        ];
        $this->actingAs($user)->json('POST', '/checklists', $params);
        $this->assertResponseStatus(201);
        $this->seeInDatabase('checklists', ['object_domain' => $params['data']['attributes']['object_domain']]);
        $this->seeInDatabase('items', ['description' => $params['data']['attributes']['items'][0]]);
    }

    public function testShowUnauthenticated()
    {
        $this->json('GET', "/checklists/1");
        $this->assertResponseStatus(401);
    }

    public function testShow()
    {
        $user = factory(User::class)->make();
        factory(Checklist::class)->create()->each(function ($checklist) {
            $checklist->items()->saveMany(factory(Item::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', "/checklists/1");
        $this->assertResponseStatus(200);
    }

    public function testShowStructure()
    {
        $user = factory(User::class)->make();
        factory(Checklist::class)->create()->each(function ($checklist) {
            $checklist->items()->saveMany(factory(Item::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', "/checklists/1");
        $this->seeJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'description'
                ],
                'links' => ['self']
            ],
        ]);

        $this->actingAs($user)->json('GET', "/checklists/1", ['include' => 'items']);
        $this->seeJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'description',
                    'items' => [
                        [
                            'id',
                            'description'
                        ]
                    ],
                ],
                'links' => ['self']
            ],
        ]);
    }

    public function testShowNotFound()
    {
        $user = factory(User::class)->make();

        $this->actingAs($user)->json('GET', "/checklists/1");
        $this->assertResponseStatus(404);
    }

    public function testUpdateUnauthenticated()
    {
        $this->json('PATCH', "/checklists/1");
        $this->assertResponseStatus(401);
    }

    public function testUpdate()
    {
        $faker = Faker\Factory::create();
        $user = factory(User::class)->make();
        $checklist = factory(Checklist::class)->create();
        $checklist->each(function ($checklist) {
            $checklist->items()->saveMany(factory(Item::class, 3)->make());
        });
        $items = $checklist->items;

        $params = [
            'data' => [
                'attributes' => [
                    'object_domain' => $faker->word,
                ]
            ]
        ];
        $this->actingAs($user)->json('PATCH', "/checklists/{$checklist->id}", $params);
        $this->assertResponseStatus(200);
        $checklistNew = Checklist::find(1);
        $this->assertNotEquals($checklist->object_domain, $checklistNew->object_domain);
    }

    public function testUpdateNotFound()
    {
        $faker = Faker\Factory::create();
        $user = factory(User::class)->make();

        $params = [
            'data' => [
                'attributes' => [
                    'object_domain' => $faker->word,
                ]
            ]
        ];
        $this->actingAs($user)->json('PATCH', "/checklists/1", $params);
        $this->assertResponseStatus(404);
    }

    public function testDeleteUnauthenticated()
    {
        $this->json('DELETE', '/checklists/1');
        $this->assertResponseStatus(401);
    }

    public function testDelete()
    {
        $user = factory(User::class)->make();
        factory(Checklist::class)->create()->each(function ($checklist) {
            $checklist->items()->saveMany(factory(Item::class, 3)->make());
        });

        $this->actingAs($user)->json('DELETE', '/checklists/1');
        $this->assertResponseStatus(204);
        $this->notSeeInDatabase('checklists', [
            'id' => 1
        ]);
    }

    public function testDeleteNotFound()
    {
        $user = factory(User::class)->make();

        $this->actingAs($user)->json('DELETE', '/checklists/1');
        $this->assertResponseStatus(404);
    }
}
