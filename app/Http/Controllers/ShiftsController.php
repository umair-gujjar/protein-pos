<?php

namespace App\Http\Controllers;

use App\Exceptions\SuspendedShiftException;
use App\Http\Requests\ClockIn;
use App\Http\Requests\ClockOut;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Shift;
use App\Services\ShiftService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

/**
 * Class ShiftsController
 *
 * @package App\Http\Controllers
 */
class ShiftsController extends AuthenticatedController
{
    private $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        parent::__construct();

        $this->shiftService = $shiftService;
    }

    public function index()
    {
        $this->authorize('access', Shift::class);

        $shifts = Shift::with('openedBy', 'closedBy', 'branch')
            ->orderBy('opened_at', 'desc')
            ->paginate();

        return view('shifts.index', [
            'shifts' => $shifts
        ]);
    }

    public function viewClockIn(Request $request)
    {
        $intended = $request->get('redirect') ?: URL::previous();
        $user     = Auth::user();
        $shift    = Shift::inBranch($user->branch)->open()
            ->where('opened_by_user_id', $user->id)
            ->first();

        if ($shift) {
            return redirect('/')->with('flashes.error', 'Already clocked in');
        }

        if ($intended) {
            Session::put('url.intended', $intended);
        }

        return view('shifts.in', [
            'user'       => $user,
            'canClockIn' => Shift::inBranch(Auth::user()->branch)
                    ->where('opened_by_user_id', '=', $user->id)
                    ->suspended()
                    ->exists() === false
        ]);
    }

    public function clockIn(ClockIn $request)
    {
        try {
            $this->shiftService->openShift(Auth::user(), $request->get('opening_balance'));
        } catch (SuspendedShiftException $ex) {
            return redirect(route('shifts.viewIn'));
        }

        return redirect()->intended()->with('flashes.success', 'Clocked in');
    }

    public function viewClockOut(Request $request)
    {
        $user                = Auth::user();
        $shift               = Shift::inBranch($user->branch)->open()
            ->where('opened_by_user_id', $user->id)
            ->first();
        $todayCompletedSales = Sale::with('payments')
            ->paid()
            ->where('opened_by_user_id', '=', Auth::user()->id)
            ->whereBetween('paid_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])
            ->get();

        if (!$shift) {
            return redirect()->back()->with('flashes.error', 'You\'re not clocked in');
        }

        return view('shifts.out', [
            'user'         => Auth::user(),
            'shift'        => $shift,
            'redirectTo'   => $request->get('redirect-to'),
            'salesSummary' => [
                'cash'   => $todayCompletedSales->filter(function (Sale $sale) { return $sale->payments->first()->payment_method === SalePayment::PAYMENT_METHOD_CASH; })->sum(function (Sale $sale) { return $sale->calculateTotal(); }),
                'credit' => $todayCompletedSales->filter(function (Sale $sale) { return $sale->payments->first()->payment_method === SalePayment::PAYMENT_METHOD_CREDIT_CARD; })->sum(function (Sale $sale) { return $sale->calculateTotal(); })
            ]
        ]);
    }

    public function clockOut(ClockOut $request, $shiftId)
    {
        $shift = Shift::find($shiftId);

        if (!$shift) {
            return redirect()->with('flashes.error', 'Shift not found');
        }

        $redirectTo = $request->get('redirect-to') ?: '/';

        $this->shiftService->closeShift($shift, Auth::user(), 0, $request->get('remark') ?: null);

        return redirect()->to($redirectTo)->with('flashes.success', 'Shift closed');
    }
}