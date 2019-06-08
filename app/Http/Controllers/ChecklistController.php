<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Checklist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Http\Resources\ChecklistResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ChecklistController extends Controller
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

    public function store(Request $request)
    {
        $this->validate($request, [
            'data.attributes.object_domain' => 'required|string',
            'data.attributes.object_id' => 'required|string',
            'data.attributes.description' => 'required|string',
            'data.attributes.due' => 'date_format:"Y-m-d\TH:i:sP"|after_or_equal:now',
            'data.attributes.urgency' => 'integer',
            'data.attributes.items' => 'required|array',
        ]);

        try {
            $checklist = new Checklist();
            DB::transaction(function () use ($request, &$checklist) {
                $user = Auth::user();
                $checklist->fill([
                    'object_domain' => $request->input('data.attributes.object_domain'),
                    'object_id' => $request->input('data.attributes.object_id'),
                    'description' => $request->input('data.attributes.description'),
                    'due' => $request->input('data.attributes.due') ? (new Carbon($request->input('data.attributes.due')))->toDateTimeString() : null,
                    'urgency' => $request->input('data.attributes.urgency'),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
                $checklist->save();

                // found in example. need to clarify insert task_id in checklist, it shoud be in item's attribute
                $task_id = $request->input('data.attributes.task_id', null);

                foreach ($request->input('data.attributes.items') as $description) {
                    $checklist->items()->create([
                        'description' => $description,
                        'task_id' => $task_id,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
                }
            });
            return new ChecklistResource($checklist);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $availableFields = [
            'id',
            'created_at',
            'object_domain',
            'object_id',
            'description',
            'due',
            'urgency',
            'is_completed',
            'completed_at',
            'updated_at'
        ];
        $this->validate($request, [
            'include' => 'filled|in:items',
            'page.limit' => 'filled|integer',
            'page.offset' => 'filled|integer',
            'filter' => 'filled|in:object_domain,object_id,description,is_completed,completed_at',
            'filter.*' => 'filled|in:like,!like,is,!is,in,!in',
            'filter.*.*' => 'filled|string',
            'sort' => [
                'filled',
                Rule::in(collect($availableFields)->merge(collect($availableFields)->map(function ($value) {
                    return "-{$value}";
                })->all()))
            ],
        ]);

        $offset = $request->input('page.offset', 0);
        $limit = $request->input('page.limit', 10);
        if ($orderBy = $request->input('sort')) {
            $order = preg_replace('/^\-/', '', $orderBy);
            $sort = preg_match('/^\-/', $orderBy) ? 'desc' : 'asc';
        }
        $query = Checklist::with($request->input('include', []))
            ->offset($offset)
            ->limit($limit);
        if ($orderBy) {
            $query->orderBy($order, $sort);
        }
        $checklists = $query->get();
        $total = $query->count();

        $params = collect($request->only(['include', 'filter', 'sort', 'fields']));
        return ChecklistResource::collection($checklists)
            ->additional([
                'meta' => [
                    'count' => $checklists->count(),
                    'total' => $total
                ],
                'links' => [
                    'first' => url("/checklists?" . http_build_query($params->merge(['page' => ['limit' => $limit, 'offset' => 0]])->all())),
                    'prev' => $offset - $limit >= 0 ? url("/checklists?" . http_build_query($params->merge(['page' => ['limit' => $limit, 'offset' => $offset - $limit]])->all())) : null,
                    'next' => $offset + $limit < $total ? url("/checklists?" . http_build_query($params->merge(['page' => ['limit' => $limit, 'offset' => $offset + $limit]])->all())) : null,
                    'last' => ceil($total / $limit) - 1 > 0 ? url("/checklists?" . http_build_query($params->merge(['page' => ['limit' => $limit, 'offset' => ceil($total / $limit) - 1]])->all())) : null,
                ]
            ]);
    }

    public function show(Request $request, $checklistId)
    {
        $this->validate($request, [
            'include' => 'filled|in:items',
        ]);

        if ($checklist = Checklist::with($request->input('include', []))->find($checklistId)) {
            return new ChecklistResource($checklist);
        } else {
            return response()->json([
                'status' => 404,
                'error' => "Not Found"
            ], 404);
        }
    }

    public function update(Request $request, $checklistId)
    {
        $this->validate($request, [
            'data.attributes.object_domain' => 'required|string',
            'data.attributes.object_id' => 'required|string',
            'data.attributes.description' => 'required|string',
            'data.attributes.due' => 'date_format:"Y-m-d\TH:i:sP"|after_or_equal:now',
            'data.attributes.urgency' => 'integer',
        ]);

        if ($checklist = Checklist::with($request->input('include', []))->find($checklistId)) {
            $user = Auth::user();
            $checklist->fill([
                'object_domain' => $request->input('data.attributes.object_domain'),
                'object_id' => $request->input('data.attributes.object_id'),
                'description' => $request->input('data.attributes.description'),
                'due' => $request->input('data.attributes.due') ? (new Carbon($request->input('data.attributes.due')))->toDateTimeString() : null,
                'urgency' => $request->input('data.attributes.urgency'),
                'updated_by' => $user->id,
            ]);
            $checklist->save();

            return new ChecklistResource($checklist);
        } else {
            return response()->json([
                'status' => 404,
                'error' => "Not Found"
            ], 404);
        }
    }

    public function destroy(Request $request, $checklistId)
    {
        if ($checklist = Checklist::with($request->input('include', []))->find($checklistId)) {
            $checklist->delete();
            return response('', 204);
        } else {
            return response()->json([
                'status' => 404,
                'error' => "Not Found"
            ], 404);
        }
    }
}
