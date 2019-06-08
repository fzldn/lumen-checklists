<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\ItemResource;
use App\Checklist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Item;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, $checklistId)
    {
        if ($checklist = Checklist::find($checklistId)) {
            $availableFields = [
                'id',
                'created_at',
                'description',
                'due',
                'urgency',
                'is_completed',
                'completed_at',
                'updated_at',
                'assignee_id',
                'task_id'
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
            ]);

            $offset = $request->input('page.offset', 0);
            $limit = $request->input('page.limit', 10);
            $query = $checklist->items()
                ->offset($offset)
                ->limit($limit);
            if ($orderBy = $request->input('sort')) {
                $query->orderBy(preg_replace('/^\-/', '', $orderBy), preg_match('/^\-/', $orderBy) ? 'desc' : 'asc');
            }
            $items = $query->get();
            $total = $query->count();

            $params = collect($request->only(['filter', 'sort', 'fields']));
            return ItemResource::collection($items)
                ->additional([
                    'meta' => [
                        'count' => $items->count(),
                        'total' => $total
                    ],
                    'links' => [
                        'first' => url("/checklists/{$checklistId}/items?" . http_build_query($params->merge(['page' => ['limit' => $limit, 'offset' => 0]])->all())),
                        'prev' => $offset - $limit >= 0 ? url("/checklists/{$checklistId}/items?" . http_build_query($params->merge(['page' => ['limit' => $limit, 'offset' => $offset - $limit]])->all())) : null,
                        'next' => $offset + $limit < $total ? url("/checklists/{$checklistId}/items?" . http_build_query($params->merge(['page' => ['limit' => $limit, 'offset' => $offset + $limit]])->all())) : null,
                        'last' => ceil($total / $limit) - 1 > 0 ? url("/checklists/{$checklistId}/items?" . http_build_query($params->merge(['page' => ['limit' => $limit, 'offset' => ceil($total / $limit) - 1]])->all())) : null,
                    ]
                ]);
        } else {
            return response()->json([
                'status' => 404,
                'error' => "Not Found"
            ], 404);
        }
    }

    public function store(Request $request, $checklistId)
    {
        if ($checklist = Checklist::find($checklistId)) {
            $this->validate($request, [
                'data.attributes.description' => 'required|string',
                'data.attributes.due' => 'date_format:"Y-m-d\TH:i:sP"|after_or_equal:now' . ($checklist->due ? '|before_or_equal:' . Carbon::parse($checklist->due)->format('c') : ''),
                'data.attributes.urgency' => 'integer',
                'data.attributes.assignee_id' => 'integer',
                'data.attributes.task_id' => 'integer',
            ]);

            try {
                $user = Auth::user();
                $item = $checklist->items()->create([
                    'description' => $request->input('data.attributes.description'),
                    'due' => $request->input('data.attributes.due') ? Carbon::parse($request->input('data.attributes.due'))->toDateTimeString() : null,
                    'urgency' => $request->input('data.attributes.urgency'),
                    'assignee_id' => $request->input('data.attributes.assignee_id'),
                    'task_id' => $request->input('data.attributes.task_id'),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
                return new ItemResource($item);
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

    public function show(Request $request, $checklistId, $itemId)
    {
        if ($checklist = Checklist::find($checklistId)) {
            if ($item = $checklist->items()->find($itemId)) {
                return new ItemResource($item);
            } else {
                return response()->json([
                    'status' => 404,
                    'error' => "Not Found"
                ], 404);
            }
        } else {
            return response()->json([
                'status' => 404,
                'error' => "Not Found"
            ], 404);
        }
    }

    public function update(Request $request, $checklistId, $itemId)
    {
        if ($checklist = Checklist::find($checklistId)) {
            if ($item = $checklist->items()->find($itemId)) {
                $this->validate($request, [
                    'data.attributes.description' => 'required|string',
                    'data.attributes.due' => 'date_format:"Y-m-d\TH:i:sP"|after_or_equal:now' . ($checklist->due ? '|before_or_equal:' . Carbon::parse($checklist->due)->format('c') : ''),
                    'data.attributes.urgency' => 'integer',
                    'data.attributes.assignee_id' => 'integer',
                    'data.attributes.task_id' => 'integer',
                ]);

                $user = Auth::user();
                $attributes = collect($request->input('data.attributes'))->only(['description', 'due', 'urgency', 'assignee_id', 'task_id']);
                if (isset($attributes['due'])) {
                    $attributes['due'] = $attributes['due'] ? Carbon::parse($attributes['due'])->toDateTimeString() : null;
                }
                $attributes['updated_by'] = $user->id;
                $item->fill($attributes->all());
                $item->save();

                return new ItemResource($item);
            } else {
                return response()->json([
                    'status' => 404,
                    'error' => "Not Found"
                ], 404);
            }
        } else {
            return response()->json([
                'status' => 404,
                'error' => "Not Found"
            ], 404);
        }
    }

    public function destroy(Request $request, $checklistId, $itemId)
    {
        if ($checklist = Checklist::find($checklistId)) {
            if ($item = $checklist->items()->find($itemId)) {
                $item->delete();
                return response('', 204);
            } else {
                return response()->json([
                    'status' => 404,
                    'error' => "Not Found"
                ], 404);
            }
        } else {
            return response()->json([
                'status' => 404,
                'error' => "Not Found"
            ], 404);
        }
    }

    public function updateBulk(Request $request, $checklistId)
    {
        if ($checklist = Checklist::find($checklistId)) {
            $this->validate($request, [
                'data.*.id' => 'required|integer',
                'data.*.action' => 'required|in:update',
                'data.*.attributes' => 'required',
                'data.*.attributes.description' => 'required|string',
                'data.*.attributes.due' => 'date_format:"Y-m-d\TH:i:sP"|after_or_equal:now' . ($checklist->due ? '|before_or_equal:' . Carbon::parse($checklist->due)->format('c') : ''),
                'data.*.attributes.urgency' => 'integer',
                'data.*.attributes.assignee_id' => 'integer',
                'data.*.attributes.task_id' => 'integer',
            ]);

            $response = [];
            $user = Auth::user();
            foreach ($request->input('data', []) as $key => $value) {
                $temp = [
                    'id' => $value['id'],
                    'action' => $value['action'],
                    'status' => null
                ];
                if ($item = Item::find($value['id'])) {
                    if ($item->checklist_id == $checklist->id) {
                        $attributes = collect($value['attributes'])->only(['description', 'due', 'urgency', 'assignee_id', 'task_id']);
                        if (isset($attributes['due'])) {
                            $attributes['due'] = $attributes['due'] ? Carbon::parse($attributes['due'])->toDateTimeString() : null;
                        }
                        $attributes['updated_by'] = $user->id;
                        $item->fill($attributes->all());
                        $item->save();
                        $temp['status'] = 200;
                    } else {
                        $temp['status'] = 403;
                    }
                } else {
                    $temp['status'] = 404;
                }
                $response[] = $temp;
            }

            return response()->json(['data' => $response]);
        } else {
            return response()->json([
                'status' => 404,
                'error' => "Not Found"
            ], 404);
        }
    }

    /**
     * need more info to clarify param date, because the summaries counting by date
     */
    public function summary(Request $request)
    {
        // $this->validate($request, [
        //     'date' => 'required|date_format:"Y-m-d\TH:i:sP"',
        //     'object_domain' => 'required|string',
        //     'tz' => ''
        // ]);

        return response()->json([
            'data' => [
                'today' => 0,
                'past_due' => 0,
                'this_week' => 0,
                'past_week' => 0,
                'this_month' => 0,
                'past_month' => 0,
                'total' => 0,
            ]
        ]);
    }

    public function complete(Request $request)
    {
        $this->validate($request, [
            'data.*.item_id' => 'required|exists:items,id',
        ]);

        $response = [];
        $user = Auth::user();
        try {
            DB::transaction(function () use ($request, $user, &$response) {
                foreach ($request->input('data') as $key => $value) {
                    $item = Item::find($value['item_id']);
                    $item->fill([
                        'is_completed' => true,
                        'completed_at' => date('Y-m-d H:i:s'),
                        'updated_by' => $user->id
                    ]);
                    $item->save();
                    $response[] = collect($item->only(['id', 'checklist_id', 'is_completed']))->merge([
                        'item_id' => $item->id
                    ]);
                }
            });
            return response()->json([
                'data' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function incomplete(Request $request)
    {
        $this->validate($request, [
            'data.*.item_id' => 'required|exists:items,id',
        ]);

        $response = [];
        $user = Auth::user();
        try {
            DB::transaction(function () use ($request, $user, &$response) {
                foreach ($request->input('data') as $key => $value) {
                    $item = Item::find($value['item_id']);
                    $item->fill([
                        'is_completed' => false,
                        'completed_at' => null,
                        'updated_by' => $user->id
                    ]);
                    $item->save();
                    $response[] = collect($item->only(['id', 'checklist_id', 'is_completed']))->merge([
                        'item_id' => $item->id
                    ]);
                }
            });
            return response()->json([
                'data' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
