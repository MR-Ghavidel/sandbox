<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceImportRequest;
use App\Repositories\AttendanceImportRepository;
use App\Support\AttendanceImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttendanceImportController extends Controller
{
    public function __construct(
        private AttendanceImportRepository $imports,
        private AttendanceImporter $importer,
    ) {}

    /**
     * Receive days from the Bizagi Chrome extension and return the review page's URL.
     */
    public function store(StoreAttendanceImportRequest $request): JsonResponse
    {
        $importId = $this->imports->create($request->validated('source'), $request->normalizedDays());

        return response()->json(['preview_url' => route('attendance-imports.show', $importId)], 201);
    }

    /**
     * Show what the import would change before applying it.
     */
    public function show(int $import): View
    {
        $attendanceImport = $this->imports->findOrFail($import);

        return view('attendance-imports.show', [
            'import' => $attendanceImport,
            'rows' => $this->importer->preview($attendanceImport),
        ]);
    }

    /**
     * Apply the import and go to the period of its last day.
     */
    public function apply(int $import): RedirectResponse
    {
        $attendanceImport = $this->imports->findOrFail($import);

        if ($attendanceImport->isApplied()) {
            return back()->with('status', 'این ورودی قبلاً اعمال شده است.');
        }

        $lastPeriod = $this->importer->preview($attendanceImport)->last()['period'];
        $this->importer->apply($attendanceImport);

        return to_route('payroll.show', ['year' => $lastPeriod->year, 'month' => $lastPeriod->month])
            ->with('status', 'داده‌های بیزاجی ثبت شد.');
    }
}
