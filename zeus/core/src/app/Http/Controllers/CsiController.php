<?php

namespace App\Http\Controllers;

use App\Services\CsiService;
use App\Services\LcmsService;
use Illuminate\Http\Request;

class CsiController extends Controller
{
    private CsiService $service;
    private LcmsService $lcmsService;

    public function __construct(CsiService $service, LcmsService $lcmsService)
    {
        $this->service = $service;
        $this->lcmsService = $lcmsService;
    }

    /**
     * CSI Dashboard main page
     */
    public function index(Request $request)
    {
        $isAvailable = $this->service->isAvailable();
        $meta = $this->service->getMeta();
        $cssStaffList = $isAvailable ? $this->service->getCssStaffList() : [];

        return view('csi.index', [
            'isAvailable' => $isAvailable,
            'meta' => $meta,
            'cssStaffList' => $cssStaffList,
        ]);
    }

    /**
     * API: Get summary KPIs
     */
    public function apiSummary(Request $request)
    {
        $filters = $this->extractFilters($request);
        return response()->json($this->service->getSummary($filters));
    }

    /**
     * API: Get student list with pagination
     * Phase 202: Enriches each student with LCMS metrics (hw_completion_rate, hw_avg_score, test_avg_score)
     */
    public function apiStudents(Request $request)
    {
        $filters = $this->extractFilters($request);
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 50);
        $sortBy = $request->get('sort_by', 'health_score');
        $sortDir = $request->get('sort_dir', 'asc');

        $result = $this->service->getStudents($filters, $page, $perPage, $sortBy, $sortDir);

        // Phase 202: Enrich with LCMS stats
        if (!empty($result['data'])) {
            $userIds = array_map(fn($s) => (int) $s->student_id, $result['data']);
            try {
                $lcmsStats = $this->lcmsService->getStudentLcmsStatsBatch($userIds);
            } catch (\Exception $e) {
                $lcmsStats = [];
            }

            foreach ($result['data'] as &$student) {
                $uid = (int) $student->student_id;
                $lcms = $lcmsStats[$uid] ?? null;
                $student->hw_completion_rate = $lcms['hw_completion_rate'] ?? null;
                $student->hw_avg_score = $lcms['hw_avg_score'] ?? null;
                $student->test_avg_score = $lcms['test_avg_score'] ?? null;
            }
            unset($student);
        }

        return response()->json($result);
    }

    /**
     * API: Get health distribution for chart
     */
    public function apiHealthDistribution(Request $request)
    {
        $filters = $this->extractFilters($request);
        return response()->json($this->service->getHealthDistribution($filters));
    }

    /**
     * API: Get CSS staff performance
     */
    public function apiCssPerformance(Request $request)
    {
        $filters = $this->extractFilters($request);
        return response()->json($this->service->getCssPerformance($filters));
    }

    /**
     * API: Get score distribution
     */
    public function apiScoreDistribution(Request $request)
    {
        $filters = $this->extractFilters($request);
        return response()->json($this->service->getScoreDistribution($filters));
    }

    /**
     * API: Get teacher warning distribution
     */
    public function apiTeacherWarning(Request $request)
    {
        $filters = $this->extractFilters($request);
        return response()->json($this->service->getTeacherWarningDistribution($filters));
    }

    /**
     * API: Get EWS students with filters and pagination
     */
    public function apiEws(Request $request)
    {
        $filters = [
            'search' => $request->get('search', ''),
            'css_staff' => $request->get('css_staff', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
            'min_missed' => (int) $request->get('min_missed', 0),
        ];
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 50);
        $sortBy = $request->get('sort_by', 'total_missed');
        $sortDir = $request->get('sort_dir', 'desc');

        return response()->json($this->service->getEwsStudents($filters, $page, $perPage, $sortBy, $sortDir));
    }

    /**
     * API: Get EWS detail for a specific student (lesson history + consecutive streak)
     */
    public function apiEwsDetail(Request $request, int $studentId)
    {
        $filters = [
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
        ];

        return response()->json($this->service->getEwsStudentDetail($studentId, $filters));
    }

    /**
     * API: Get weekly/monthly trend data for comparison charts
     */
    public function apiTrends(Request $request)
    {
        $filters = $this->extractFilters($request);
        $groupBy = $request->get('group_by', 'week'); // 'week' or 'month'
        return response()->json($this->service->getTrends($filters, $groupBy));
    }

    /**
     * API: Get weekly/monthly OnTrack trend data (active/ontrack counts per period)
     */
    public function apiOntrackTrends(Request $request)
    {
        $filters = $this->extractFilters($request);
        $groupBy = $request->get('group_by', 'week'); // 'week' or 'month'
        return response()->json($this->service->getOntrackTrends($filters, $groupBy));
    }

    /**
     * API: Get weekly/monthly health-category trend data (green/yellow/red counts per period)
     */
    public function apiHealthTrends(Request $request)
    {
        $filters = $this->extractFilters($request);
        $groupBy = $request->get('group_by', 'week'); // 'week' or 'month'
        return response()->json($this->service->getHealthTrends($filters, $groupBy));
    }

    /**
     * API: Get student detail (CSI summary + lesson history)
     * Phase 202: Includes LCMS metrics (hw_completion_rate, hw_avg_score, test_avg_score)
     */
    public function apiStudentDetail(Request $request, int $studentId)
    {
        $filters = [
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
        ];

        $result = $this->service->getStudentDetail($studentId, $filters);

        // Phase 202: Add LCMS stats
        try {
            $result['lcms'] = $this->lcmsService->getStudentLcmsSummary($studentId);
        } catch (\Exception $e) {
            $result['lcms'] = ['hw_completion_rate' => null, 'hw_avg_score' => null, 'test_avg_score' => null];
        }

        return response()->json($result);
    }

    /**
     * API: Get inactive SpeakWell students list with remaining lesson counts
     * Phase 221: Paginated list + zero_lessons_count summary
     * Phase 222: Added sort_by / sort_dir support
     */
    public function apiSpwInactive(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 50);
        $search = $request->get('search', '');
        $sortBy = $request->get('sort_by', 'remaining_total');
        $sortDir = $request->get('sort_dir', 'asc');

        return response()->json($this->service->getInactiveStudentsList($page, $perPage, $search, $sortBy, $sortDir));
    }

    /**
     * API: Search students
     */
    public function apiSearch(Request $request)
    {
        $keyword = $request->get('q', '');
        if (strlen($keyword) < 1) {
            return response()->json([]);
        }
        return response()->json($this->service->searchStudent($keyword));
    }

    /**
     * Extract filter parameters from request
     */
    private function extractFilters(Request $request): array
    {
        return [
            'health_category' => $request->get('health_category', ''),
            'css_staff' => $request->get('css_staff', ''),
            'teacher_warning' => $request->get('teacher_warning', ''),
            'search' => $request->get('search', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
            'lesson_1_from' => $request->get('lesson_1_from', ''),
            'lesson_1_to' => $request->get('lesson_1_to', ''),
            'first_3_from' => $request->get('first_3_from', ''),
            'first_3_to' => $request->get('first_3_to', ''),
            'ontrack_status' => $request->get('ontrack_status', ''),
            'program' => $request->get('program', ''),
        ];
    }
}
