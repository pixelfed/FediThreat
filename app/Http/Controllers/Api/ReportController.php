<?php

namespace App\Http\Controllers\Api;

use App\Jobs\RecalculateThreatScore;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(config('fedithreat.reports.enabled'), 404);
        abort_unless($request->instance->can_report, 403);

        $report = Report::create([
            'instance_id' => $request->instance->id,
            'target_type' => $request->type,
            'target_value' => $request->value,
            'reason' => $request->reason,
            'evidence' => $request->evidence,
            'severity' => $request->severity,
        ]);

        dispatch(new RecalculateThreatScore($report));

        return response()->json($report, 201);
    }
}
