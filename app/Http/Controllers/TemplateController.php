<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Template;
use App\Http\Resources\TemplateResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Checklist;
use Illuminate\Support\Carbon;
use App\Http\Resources\ChecklistResource;
use App\Http\Resources\ItemResource;
use App\Item;

class TemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $availableFields = [
            'id',
            'name',
            'created_at',
            'updated_at',
        ];
        $this->validate($request, [
            'page.limit' => 'filled|integer',
            'page.offset' => 'filled|integer',
            'filter' => 'filled|in:' . collect($availableFields)->join(','),
            'filter.*' => 'filled|in:like,!like,is,!is,in,!in',
            'filter.*.*' => 'filled|string',
            'sort' => 'filled|in:' . collect($availableFields)->map(function ($value) {
                return "{$value},-{$value}";
            })->join(','),
            'fields' => ''
        ]);

        $offset = $request->input('page.offset', 0);
        $limit = $request->input('page.limit', 10);
        $query = Template::with(['checklist', 'items'])
            ->offset($offset)
            ->limit($limit);
        if ($orderBy = $request->input('sort')) {
            $query->orderBy(preg_replace('/^\-/', '', $orderBy), preg_match('/^\-/', $orderBy) ? 'desc' : 'asc');
        }
        $templates = $query->get();
        $total = $query->count();

        $params = collect($request->only(['filter', 'sort', 'fields']));
        return TemplateResource::collection($templates)
            ->additional([
                'meta' => [
                    'count' => $templates->count(),
                    'total' => $total
                ],
                'links' => [
                    'first' => url("/checklists/templates?" . http_build_query($params->merge(['page' => ['limit' => $limit, 'offset' => 0]])->all())),
                    'prev' => $offset - $limit >= 0 ? url("/checklists/templates?" . http_build_query($params->merge(['page' => ['limit' => $limit, 'offset' => $offset - $limit]])->all())) : null,
                    'next' => $offset + $limit < $total ? url("/checklists/templates?" . http_build_query($params->merge(['page' => ['limit' => $limit, 'offset' => $offset + $limit]])->all())) : null,
                    'last' => ceil($total / $limit) - 1 > 0 ? url("/checklists/templates?" . http_build_query($params->merge(['page' => ['limit' => $limit, 'offset' => ceil($total / $limit) - 1]])->all())) : null,
                ]
            ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'data.attributes.name' => 'required|string',
            'data.attributes.checklist.description' => 'required|string',
            'data.attributes.checklist.due_interval' => 'required_with:data.attributes.checklist.due_unit|integer',
            'data.attributes.checklist.due_unit' => 'required_with:data.attributes.checklist.due_interval|in:minute,hour,day,week,month',
            'data.attributes.checklist.urgency' => 'integer',
            'data.attributes.items' => 'required|array',
            'data.attributes.items.*.description' => 'required|string',
            'data.attributes.items.*.due_interval' => 'integer',
            'data.attributes.items.*.due_unit' => 'in:minute,hour,day,week,month',
            'data.attributes.items.*.urgency' => 'integer',
            'data.attributes.items.*.assignee_id' => 'integer',
            'data.attributes.items.*.task_id' => 'integer',
        ]);

        try {
            $template = new Template();
            DB::transaction(function () use ($request, &$template) {
                $user = Auth::user();
                $template->fill([
                    'name' => $request->input('data.attributes.name'),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
                $template->save();

                $template->checklist()->create(collect($request->input('data.attributes.checklist'))
                    ->merge([
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ])
                    ->all());
                foreach ($request->input('data.attributes.items', []) as $key => $value) {
                    $template->items()->create(collect($value)
                        ->merge([
                            'created_by' => $user->id,
                            'updated_by' => $user->id,
                        ])
                        ->all());
                }
            });
            $template->checklist;
            $template->items;
            return new TemplateResource($template);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $templateId)
    {
        if ($template = Template::with(['checklist', 'items'])->find($templateId)) {
            return new TemplateResource($template);
        } else {
            return response()->json([
                'status' => 404,
                'error' => "Not Found"
            ], 404);
        }
    }

    public function update(Request $request, $templateId)
    {
        $this->validate($request, [
            'data.attributes.name' => 'required|string',
            'data.attributes.checklist.description' => 'required|string',
            'data.attributes.checklist.due_interval' => 'required_with:data.attributes.checklist.due_unit|integer',
            'data.attributes.checklist.due_unit' => 'required_with:data.attributes.checklist.due_interval|in:minute,hour,day,week,month',
            'data.attributes.checklist.urgency' => 'integer',
            'data.attributes.items' => 'required|array',
            'data.attributes.items.*.description' => 'required|string',
            'data.attributes.items.*.due_interval' => 'integer',
            'data.attributes.items.*.due_unit' => 'in:minute,hour,day,week,month',
            'data.attributes.items.*.urgency' => 'integer',
            'data.attributes.items.*.assignee_id' => 'integer',
            'data.attributes.items.*.task_id' => 'integer',
        ]);

        if ($template = Template::with(['checklist', 'items'])->find($templateId)) {
            try {
                DB::transaction(function () use ($request, &$template) {
                    $user = Auth::user();
                    $template->fill([
                        'name' => $request->input('data.attributes.name'),
                        'updated_by' => $user->id
                    ]);
                    $template->save();

                    $template->checklist()->delete();
                    $template->checklist()->create(collect($request->input('data.attributes.checklist'))
                        ->merge([
                            'created_by' => $user->id,
                            'updated_by' => $user->id,
                        ])
                        ->all());

                    $template->items()->delete();
                    foreach ($request->input('data.attributes.items', []) as $key => $value) {
                        $template->items()->create(collect($value)
                            ->merge([
                                'created_by' => $user->id,
                                'updated_by' => $user->id,
                            ])
                            ->all());
                    }
                });

                return new TemplateResource($template);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 500,
                    'error' => $e->getMessage()
                ], 500);
            }
        } else {
            return response()->json([
                'status' => 404,
                'error' => "Not Found"
            ], 404);
        }
    }

    public function destroy(Request $request, $templateId)
    {
        if ($template = Template::find($templateId)) {
            $template->delete();
            return response('', 204);
        } else {
            return response()->json([
                'status' => 404,
                'error' => "Not Found"
            ], 404);
        }
    }

    public function assigns(Request $request, $templateId)
    {
        $this->validate($request, [
            'data' => 'required|array',
            'data.*.attributes.object_domain' => 'required|string',
            'data.*.attributes.object_id' => 'required|integer',
        ]);

        if ($template = Template::with(['checklist', 'items'])->find($templateId)) {
            try {
                $checklistIds = [];
                $itemIds = [];
                DB::transaction(function () use ($request, &$template, &$checklistIds, &$itemIds) {
                    $user = Auth::user();
                    foreach ($request->input('data', []) as $key => $value) {
                        $checklist = Checklist::create([
                            'object_domain' => $value['attributes']['object_domain'],
                            'object_id' => $value['attributes']['object_id'],
                            'description' => $template->checklist->description,
                            'urgency' => $template->checklist->urgency,
                            'due' => $template->checklist->due_interval ? Carbon::now()->add($template->checklist->due_interval, $template->checklist->due_unit)->toDateTimeString() : null,
                            'created_by' => $user->id,
                            'updated_by' => $user->id,
                        ]);
                        $checklistIds[] = $checklist->id;

                        foreach ($template->items as $tempItem) {
                            $item = $checklist->items()->create([
                                'description' => $tempItem->description,
                                'urgency' => $tempItem->urgency,
                                'due' => $tempItem->due_interval ? Carbon::now()->add($tempItem->due_interval, $tempItem->due_unit)->toDateTimeString() : null,
                                'assignee_id' => $tempItem->assignee_id,
                                'task_id' => $tempItem->task_id,
                                'created_by' => $user->id,
                                'updated_by' => $user->id,
                            ]);
                            $itemIds[] = $item->id;
                        }
                    }
                });

                $checklists = Checklist::with('items')->find($checklistIds);
                return ChecklistResource::collection($checklists)
                    ->additional([
                        'meta' => [
                            'count' => $checklists->count(),
                            'total' => $checklists->count(),
                        ],
                        'includes' => ItemResource::collection(Item::find($itemIds))
                    ])
                    ->response()
                    ->setStatusCode(201);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 500,
                    'error' => $e->getMessage()
                ], 500);
            }
        } else {
            return response()->json([
                'status' => 404,
                'error' => "Not Found"
            ], 404);
        }
    }
}
