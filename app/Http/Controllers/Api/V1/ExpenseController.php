<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\Group;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request, Group $group): JsonResponse
    {
        $this->member($request, $group);

        return response()->json($group->expenses()->with(['payer:id,name,email', 'shares.user:id,name,email'])->latest('spent_on')->get());
    }

    public function store(StoreExpenseRequest $request, Group $group, ExpenseService $service): JsonResponse
    {
        $this->member($request, $group);

        return response()->json($service->save($group, $request->user(), $request->validated()), 201);
    }

    public function update(UpdateExpenseRequest $request, Group $group, Expense $expense, ExpenseService $service): JsonResponse
    {
        $this->member($request, $group);
        abort_unless($expense->group_id === $group->id, 404);

        return response()->json($service->save($group, $request->user(), $request->validated(), $expense));
    }

    public function destroy(Request $request, Group $group, Expense $expense): JsonResponse
    {
        $this->member($request, $group);
        abort_unless($expense->group_id === $group->id, 404);
        $expense->delete();

        return response()->json([], 204);
    }

    private function member(Request $request, Group $group): void
    {
        abort_unless($group->members()->where('user_id', $request->user()->id)->exists(), 404);
    }
}
