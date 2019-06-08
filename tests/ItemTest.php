<?php
use Laravel\Lumen\Testing\DatabaseMigrations;
use App\User;
use App\Checklist;
use App\Item;
use Carbon\Carbon;

class ItemTest extends TestCase
{
    use DatabaseMigrations;

    public function testIndexUnauthenticated()
    {
        $this->json('GET', '/checklists/1/items');
        $this->assertResponseStatus(401);
    }

    public function testIndex()
    {
        $user = factory(User::class)->make();
        factory(Checklist::class)->create()->each(function ($checklist) {
            $checklist->items()->saveMany(factory(Item::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', '/checklists/1/items');
        $this->assertResponseStatus(200);
    }

    public function testIndexStructure()
    {
        $user = factory(User::class)->make();

        factory(Checklist::class)->create()->each(function ($checklist) {
            $checklist->items()->saveMany(factory(Item::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', '/checklists/1/items');
        $this->seeJsonStructure([
            'data' => [
                [
                    'type',
                    'id',
                    'attributes' => [
                        'description',
                        'is_completed',
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

        $itemTotal = 20;
        factory(Checklist::class)->create()->each(function ($checklist) use ($itemTotal) {
            $checklist->items()->saveMany(factory(Item::class, $itemTotal)->make());
        });

        $this->actingAs($user)->json('GET', '/checklists/1/items', [
            'page' => [
                'limit' => 5
            ]
        ]);
        $response = json_decode($this->response->getContent(), true);

        // templates count match with response
        $this->assertEquals($itemTotal, $response['meta']['total']);
        $this->assertEquals(5, $response['meta']['count']);
        $this->assertEquals(5, count($response['data']));
    }

    public function testIndexSortingAsc()
    {
        $user = factory(User::class)->make();

        $itemTotal = 20;
        factory(Checklist::class)->create()->each(function ($checklist) use ($itemTotal) {
            $checklist->items()->saveMany(factory(Item::class, $itemTotal)->make());
        });

        $this->actingAs($user)->json('GET', '/checklists/1/items', ['sort' => 'id']);
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

        $itemTotal = 20;
        factory(Checklist::class)->create()->each(function ($checklist) use ($itemTotal) {
            $checklist->items()->saveMany(factory(Item::class, $itemTotal)->make());
        });

        $this->actingAs($user)->json('GET', '/checklists/1/items', ['sort' => '-id']);
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
        $this->json('POST', '/checklists/1/items');
        $this->assertResponseStatus(401);
    }

    public function testStore()
    {
        $faker = Faker\Factory::create();
        $user = factory(User::class)->make();
        factory(Checklist::class)->create();

        $params = [
            'data' => [
                'attributes' => [
                    'description' => $faker->words(5, true),
                    'due' => Carbon::now()->add($faker->numberBetween(1, 5), $faker->randomElement(['minute', 'hour']))->format('c'),
                    'urgency' => $faker->numberBetween(0, 3)
                ]
            ]
        ];
        $this->actingAs($user)->json('POST', '/checklists/1/items', $params);
        $this->assertResponseStatus(201);
        $this->seeInDatabase('items', ['description' => $params['data']['attributes']['description']]);
    }

    public function testShowUnauthenticated()
    {
        $this->json('GET', "/checklists/1/items/1");
        $this->assertResponseStatus(401);
    }

    public function testShow()
    {
        $user = factory(User::class)->make();
        factory(Checklist::class)->create()->each(function ($checklist) {
            $checklist->items()->saveMany(factory(Item::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', "/checklists/1/items/1");
        $this->assertResponseStatus(200);
    }

    public function testShowStructure()
    {
        $user = factory(User::class)->make();
        factory(Checklist::class)->create()->each(function ($checklist) {
            $checklist->items()->saveMany(factory(Item::class, 3)->make());
        });

        $this->actingAs($user)->json('GET', "/checklists/1/items/1");
        $this->seeJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'description',
                    'is_completed'
                ],
                'links' => ['self']
            ],
        ]);
    }

    public function testShowNotFound()
    {
        $user = factory(User::class)->make();

        $this->actingAs($user)->json('GET', "/checklists/1/items/1");
        $this->assertResponseStatus(404);

        factory(Checklist::class)->create();
        $this->actingAs($user)->json('GET', "/checklists/1/items/1");
        $this->assertResponseStatus(404);
    }

    public function testUpdateUnauthenticated()
    {
        $this->json('PATCH', "/checklists/1/items/1");
        $this->assertResponseStatus(401);
    }

    public function testUpdate()
    {
        $faker = Faker\Factory::create();
        $user = factory(User::class)->make();
        $checklist = factory(Checklist::class)->create();
        $item = $checklist->items()->save(factory(Item::class)->make());

        $params = [
            'data' => [
                'attributes' => [
                    'description' => $faker->words(5, true),
                ]
            ]
        ];
        $this->actingAs($user)->json('PATCH', "/checklists/1/items/1", $params);
        $this->assertResponseStatus(200);
        $itemNew = Item::find(1);
        $this->assertNotEquals($item->description, $itemNew->description);
    }

    public function testUpdateNotFound()
    {
        $faker = Faker\Factory::create();
        $user = factory(User::class)->make();
        factory(Checklist::class)->create();

        $params = [
            'data' => [
                'attributes' => [
                    'description' => $faker->words(5, true),
                ]
            ]
        ];
        $this->actingAs($user)->json('PATCH', "/checklists/1/items/1", $params);
        $this->assertResponseStatus(404);
    }

    public function testDeleteUnauthenticated()
    {
        $this->json('DELETE', '/checklists/1/items/1');
        $this->assertResponseStatus(401);
    }

    public function testDelete()
    {
        $user = factory(User::class)->make();
        factory(Checklist::class)->create()->each(function ($checklist) {
            $checklist->items()->saveMany(factory(Item::class, 3)->make());
        });

        $this->actingAs($user)->json('DELETE', '/checklists/1/items/1');
        $this->assertResponseStatus(204);
        $this->notSeeInDatabase('items', [
            'id' => 1
        ]);
    }

    public function testDeleteNotFound()
    {
        $user = factory(User::class)->make();
        factory(Checklist::class)->create();

        $this->actingAs($user)->json('DELETE', '/checklists/1/items/1');
        $this->assertResponseStatus(404);
    }

    public function testCompleteUnauthenticated()
    {
        $params = [
            'data' => [
                [
                    'item_id' => 1
                ]
            ]
        ];
        $this->json('POST', '/checklists/complete', $params);
        $this->assertResponseStatus(401);
    }

    public function testComplete()
    {
        $user = factory(User::class)->make();
        factory(Checklist::class)->create()->each(function ($checklist) {
            $checklist->items()->saveMany(factory(Item::class, 3)->make());
        });

        $params = [
            'data' => [
                ['item_id' => 1],
                ['item_id' => 2],
            ]
        ];
        $this->actingAs($user)->json('POST', '/checklists/complete', $params);
        $this->assertResponseStatus(200);
        $this->seeJsonStructure([
            'data' => [
                ['id', 'item_id', 'is_completed', 'checklist_id']
            ]
        ]);
        $this->seeInDatabase('items', [
            'id' => 1,
            'is_completed' => true
        ]);
        $this->seeInDatabase('items', [
            'id' => 2,
            'is_completed' => true
        ]);
        $this->seeInDatabase('items', [
            'id' => 3,
            'is_completed' => false
        ]);
    }

    public function testIncompleteUnauthenticated()
    {
        $params = [
            'data' => [
                [
                    'item_id' => 1
                ]
            ]
        ];
        $this->json('POST', '/checklists/incomplete', $params);
        $this->assertResponseStatus(401);
    }

    public function testIncomplete()
    {
        $user = factory(User::class)->make();
        factory(Checklist::class)->create()->each(function ($checklist) {
            $checklist->items()->saveMany(factory(Item::class, 3)->make());
            $checklist->items()->update(['is_completed' => true, 'completed_at' => date('Y-m-d H:i:s')]);
        });

        $params = [
            'data' => [
                ['item_id' => 1],
                ['item_id' => 2],
            ]
        ];
        $this->actingAs($user)->json('POST', '/checklists/incomplete', $params);
        $this->assertResponseStatus(200);
        $this->seeJsonStructure([
            'data' => [
                ['id', 'item_id', 'is_completed', 'checklist_id']
            ]
        ]);
        $this->seeInDatabase('items', [
            'id' => 1,
            'is_completed' => false
        ]);
        $this->seeInDatabase('items', [
            'id' => 2,
            'is_completed' => false
        ]);
        $this->seeInDatabase('items', [
            'id' => 3,
            'is_completed' => true
        ]);
    }
}
