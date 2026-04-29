<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeavePlan;
use App\Models\Holiday;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class PlanningController extends Controller
{
    // LEAVE PLANS
    public function indexPlans()
    {
        $plans = LeavePlan::orderBy('start_date')->get();
        return response()->json(['success' => true, 'data' => $plans]);
    }

    public function storePlan(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $plan = LeavePlan::create([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'year' => date('Y', strtotime($request->start_date)),
        ]);

        ActivityLogger::success('PLAN_CREATED', "Période de congé '{$plan->name}' créée", 'planning');
        return response()->json(['success' => true, 'data' => $plan], 201);
    }

    public function updatePlan(Request $request, LeavePlan $plan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $plan->update([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'year' => date('Y', strtotime($request->start_date)),
        ]);

        ActivityLogger::info('PLAN_UPDATED', "Période '{$plan->name}' modifiée", 'planning');
        return response()->json(['success' => true, 'data' => $plan]);
    }

    public function destroyPlan(LeavePlan $plan)
    {
        $name = $plan->name;
        $plan->delete();
        ActivityLogger::warning('PLAN_DELETED', "Période '{$name}' supprimée", 'planning');
        return response()->json(['success' => true, 'message' => 'Période supprimée']);
    }

    // HOLIDAYS
    public function indexHolidays()
    {
        $holidays = Holiday::orderBy('date')->get();
        return response()->json(['success' => true, 'data' => $holidays]);
    }

    public function storeHoliday(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        $holiday = Holiday::create([
            'name' => $request->name,
            'date' => $request->date,
            'year' => date('Y', strtotime($request->date)),
        ]);

        ActivityLogger::success('HOLIDAY_CREATED', "Jour férié '{$holiday->name}' ajouté", 'planning');
        return response()->json(['success' => true, 'data' => $holiday], 201);
    }

    public function updateHoliday(Request $request, Holiday $holiday)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        $holiday->update([
            'name' => $request->name,
            'date' => $request->date,
            'year' => date('Y', strtotime($request->date)),
        ]);

        return response()->json(['success' => true, 'data' => $holiday]);
    }

    public function destroyHoliday(Holiday $holiday)
    {
        $name = $holiday->name;
        $holiday->delete();
        ActivityLogger::warning('HOLIDAY_DELETED', "Jour férié '{$name}' supprimé", 'planning');
        return response()->json(['success' => true, 'message' => 'Jour férié supprimé']);
    }
}