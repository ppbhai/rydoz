<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingRide;
use App\Models\Branch;
use App\Models\BranchLiveStat;
use App\Models\BranchVehicle;
use App\Models\DiscountReason;
use App\Models\User;
use App\Services\MsgClubSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RideFlowController extends Controller
{
    public function index()
    {
        $user = $this->currentUser();
        $branch = $this->currentBranch();

        if (!$user || !$branch) {
            return redirect()->route('userlogin');
        }

        $todayBookings = $this->branchBookings()->whereDate('created_at', today())->count();
        $ongoingRides = BookingRide::where('status', 'ongoing')
            ->whereHas('booking', fn ($query) => $query->where('branch_id', $branch->id))
            ->count();
        $paymentReadyCount = $this->branchBookings()
            ->whereHas('rides', fn ($query) => $query->where('status', 'finished'))
            ->whereDoesntHave('rides', fn ($query) => $query->where('status', '!=', 'finished'))
            ->count();
        $todayCollection = $this->branchBookings()
            ->where('status', 'completed')
            ->whereDate('paid_at', today())
            ->sum('final_amount');

        $search = trim((string) request('search', ''));
        $openBookingId = (int) request('booking', 0);

        $pendingAssignments = $this->branchBookings()
            ->with(['rides' => fn ($query) => $query->whereIn('status', ['pending', 'ongoing'])->orderBy('id')])
            ->whereHas('rides', fn ($query) => $query->where('status', 'pending'))
            ->whereIn('status', ['pending', 'ongoing'])
            ->orderByDesc('paid_at')
            ->get();

        return view('theme.ride-index', compact(
            'todayBookings',
            'ongoingRides',
            'paymentReadyCount',
            'todayCollection',
            'pendingAssignments',
            'branch',
            'search',
            'openBookingId'
        ));
    }

    public function book()
    {
        $this->ensureUserLoggedIn();

        return view('theme.book-flow');
    }

    public function scooterBatteries()
    {
        $this->ensureUserLoggedIn();

        return view('theme.scooter-batteries');
    }

    public function scooterUsage()
    {
        $this->ensureUserLoggedIn();

        $branch = $this->currentBranch();
        abort_unless($branch, 403);

        $assignedScooters = BookingRide::query()
            ->selectRaw('ride_number, count(*) as assign_count, max(start_time) as last_assigned_at')
            ->whereNotNull('ride_number')
            ->where('ride_number', '!=', '')
            ->where('start_time', '>=', now()->subDay())
            ->whereHas('booking', fn ($query) => $query->where('branch_id', $branch->id))
            ->groupBy('ride_number')
            ->orderByDesc('assign_count')
            ->orderBy('ride_number')
            ->get();

        return view('theme.scooter-usage', compact('assignedScooters'));
    }

    public function freeTrial()
    {
        $this->ensureUserLoggedIn();
        $branch = $this->currentBranch();

        abort_unless($branch && $branch->free_trial_enabled, 403);

        return view('theme.free-trial');
    }

    public function updateScooterLiveStats(Request $request): JsonResponse
    {
        $this->ensureUserLoggedIn();

        $branch = $this->currentBranch();
        abort_unless($branch, 403);

        Log::info('Scooter live stats request started', [
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'payload' => $request->all(),
        ]);

        $validated = $request->validate([
            'scooters' => ['nullable', 'array'],
            'scooters.*.scooterId' => ['required_with:scooters', 'string', 'max:100'],
            'scooters.*.battery' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $scooters = collect($validated['scooters'] ?? [])
            ->map(function (array $scooter) {
                return [
                    'scooterId' => trim((string) ($scooter['scooterId'] ?? '')),
                    'battery' => array_key_exists('battery', $scooter) && $scooter['battery'] !== null
                        ? min(100, max(0, (int) round((float) $scooter['battery'])))
                        : null,
                ];
            })
            ->filter(fn (array $scooter) => $scooter['scooterId'] !== '')
            ->unique('scooterId')
            ->values();

        $lowBatteryScooters = $scooters
            ->filter(fn (array $scooter) => $scooter['battery'] !== null && $scooter['battery'] <= 10)
            ->count();

        Log::info('Scooter live stats report received', [
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'online_scooters' => $scooters->count(),
            'low_battery_scooters' => $lowBatteryScooters,
            'scooters' => $scooters->all(),
        ]);

        BranchLiveStat::updateOrCreate(
            ['branch_id' => $branch->id],
            [
                'online_scooters' => $scooters->count(),
                'low_battery_scooters' => $lowBatteryScooters,
                'scooters' => $scooters->all(),
                'reported_at' => now(),
            ]
        );

        return response()->json([
            'status' => true,
            'online_scooters' => $scooters->count(),
            'low_battery_scooters' => $lowBatteryScooters,
        ]);
    }

    public function customerLookup(Request $request): JsonResponse
    {
        $request->validate([
            'mobile' => ['required', 'digits:10'],
        ]);

        $customer = Booking::query()
            ->where('mobile', $request->mobile)
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->latest('id')
            ->first();

        return response()->json([
            'status' => true,
            'name' => $customer?->name,
        ]);
    }

    public function sendOtp(Request $request, MsgClubSmsService $smsService): JsonResponse
    {
        $this->ensureUserLoggedIn();

        $request->validate([
            'mobile' => ['required', 'digits:10'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $branch = $this->currentBranch();
        abort_unless($branch, 403);

        $otp = (string) random_int(10000, 99999);

        if (!$smsService->sendOtp($request->mobile, $otp, $request->name)) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to send OTP. Please try again.',
            ], 502);
        }

        $booking = Booking::create([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'branch' => $branch->name,
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'otp' => $otp,
            'is_verified' => false,
            'status' => 'otp_pending',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'OTP sent successfully.',
            'booking_id' => $booking->id,
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => ['required', 'integer'],
            'mobile' => ['required', 'digits:10'],
            'otp' => ['required', 'digits:5'],
        ]);

        $booking = $this->branchBookings()
            ->where('id', $request->booking_id)
            ->where('mobile', $request->mobile)
            ->first();

        if (!$booking || $booking->otp !== $request->otp) {
            return response()->json([
                'status' => false,
                'message' => 'Wrong OTP.',
            ], 422);
        }

        $booking->update([
            'is_verified' => true,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'redirect_url' => route('confirm-ride', $booking),
        ]);
    }

    public function confirmRide(Booking $booking)
    {
        $this->ensureUserLoggedIn();
        $this->ensureBookingBelongsToUserBranch($booking);

        abort_unless($booking->is_verified, 404);

        $branch = $this->currentBranch();
        abort_unless($branch, 403);

        $vehicles = $this->branchVehicles($branch);

        return view('theme.confirm-ride-flow', compact('booking', 'branch', 'vehicles'));
    }

    public function saveBooking(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureUserLoggedIn();
        $this->ensureBookingBelongsToUserBranch($booking);
        abort_unless($booking->is_verified, 404);

        $branch = $this->currentBranch();
        abort_unless($branch, 403);

        $validated = $request->validate([
            'vehicles_json' => ['required', 'string'],
            'document_type' => [$branch->document_select_enabled ? 'required' : 'nullable', 'string', 'max:255'],
            'proof_image' => [$branch->photo_enabled ? 'required' : 'nullable', 'image', 'max:4096'],
        ], [
            'proof_image.required' => 'Photo is required for this branch.',
        ]);

        $rideRows = collect(json_decode($validated['vehicles_json'], true))
            ->filter(fn ($row) => !empty($row['branch_vehicle_id']) && (int) ($row['qty'] ?? 0) > 0)
            ->values();

        if ($rideRows->isEmpty()) {
            return back()->withInput()->with('error', 'Select at least one vehicle.');
        }

        $branchVehicles = BranchVehicle::query()
            ->where('branch_id', $branch->id)
            ->whereIn('id', $rideRows->pluck('branch_vehicle_id')->all())
            ->get()
            ->keyBy('id');

        $serializedRides = [];
        $booking->rides()->delete();

        foreach ($rideRows as $row) {
            $branchVehicle = $branchVehicles->get((int) $row['branch_vehicle_id']);
            $requestedQty = (int) $row['qty'];

            if (!$branchVehicle) {
                continue;
            }

            for ($i = 0; $i < $requestedQty; $i++) {
                $booking->rides()->create([
                    'branch_vehicle_id' => $branchVehicle->id,
                    'vehicle_name' => $branchVehicle->name,
                    'vehicle_price' => $branchVehicle->price,
                    'vehicle_time' => $branchVehicle->time,
                    'requested_minutes' => $branchVehicle->time,
                    'qty' => 1,
                    'status' => 'pending',
                ]);
            }

            $serializedRides[] = [
                'branch_vehicle_id' => $branchVehicle->id,
                'vehicle_name' => $branchVehicle->name,
                'qty' => $requestedQty,
                'time' => $branchVehicle->time,
            ];
        }

        if (empty($serializedRides)) {
            return back()->withInput()->with('error', 'Selected vehicles are invalid.');
        }

        $uploadDirectory = public_path('uploads/bookings');

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        $proofImageName = $request->hasFile('proof_image')
            ? $this->storeUploadedFile($request->file('proof_image'), $uploadDirectory)
            : null;

        $booking->update([
            'vehicles' => $serializedRides,
            'document_type' => ($validated['document_type'] ?? null) ?: null,
            'front_image' => $proofImageName ? 'uploads/bookings/' . $proofImageName : null,
            'back_image' => null,
            'status' => 'pending',
        ]);

        return redirect()->route('index')
            ->with('success', 'Booking saved successfully.')
            ->with('booking_saved', true);
    }

    public function assignBooking(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureUserLoggedIn();
        $this->ensureBookingBelongsToUserBranch($booking);

        $branch = $this->currentBranch();
        abort_unless($branch, 403);

        $pendingRides = $booking->rides()->where('status', 'pending')->orderBy('id')->get();

        if ($pendingRides->isEmpty()) {
            return redirect()->route('index')->with('error', 'This booking is already assigned.');
        }

        $validated = $request->validate([
            'ride_numbers' => ['array'],
            'ride_numbers.*' => ['nullable', 'string', 'max:50'],
        ]);

        if ($branch->vehicle_number_required || $branch->scanner_enabled) {
            foreach ($pendingRides as $ride) {
                $rideNumber = trim((string) ($validated['ride_numbers'][$ride->id] ?? ''));

                if ($rideNumber === '') {
                    return back()->with('error', 'Vehicle number is required for all selected rides.');
                }
            }
        }

        $enteredRideNumbers = [];

        foreach ($pendingRides as $ride) {
            $rideNumber = trim((string) ($validated['ride_numbers'][$ride->id] ?? ''));

            if ($rideNumber === '') {
                continue;
            }

            $rideNumberKey = mb_strtolower($rideNumber);

            if (in_array($rideNumberKey, $enteredRideNumbers, true)) {
                return back()->with('error', 'Vehicle number ' . $rideNumber . ' is repeated in this assignment.');
            }

            if ($this->rideNumberAlreadyAssigned($branch->id, $ride->branch_vehicle_id, $rideNumber, $ride->id)) {
                return back()->with('error', $ride->vehicle_name . ' number ' . $rideNumber . ' is already assigned in this branch.');
            }

            $enteredRideNumbers[] = $rideNumberKey;
        }

        $startTime = now();

        foreach ($pendingRides as $ride) {
            $ride->update([
                'ride_number' => $validated['ride_numbers'][$ride->id] ?? null,
                'start_time' => $startTime,
                'status' => 'ongoing',
            ]);
        }

        $booking->update(['status' => 'ongoing']);

        return redirect()->route('index')->with('success', 'Ride assigned successfully.');
    }

    public function assignSingleRide(Request $request, BookingRide $ride): RedirectResponse
    {
        $this->ensureUserLoggedIn();
        $this->ensureRideBelongsToUserBranch($ride);

        $branch = $this->currentBranch();
        abort_unless($branch, 403);

        if ($ride->status !== 'pending') {
            return redirect()->route('index')->with('error', 'This ride is already assigned.');
        }

        $validated = $request->validate([
            'ride_number' => ['nullable', 'string', 'max:50'],
            'iot_device_id' => ['nullable', 'string', 'max:100'],
            'iot_battery_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'return_search' => ['nullable', 'string', 'max:255'],
            'return_booking' => ['nullable', 'integer'],
        ]);

        $rideNumber = trim((string) ($validated['ride_number'] ?? ''));
        $iotDeviceId = $this->normalizeScooterId((string) ($validated['iot_device_id'] ?? ''));

        if ($rideNumber === '' && $iotDeviceId !== '') {
            $rideNumber = $iotDeviceId;
        }

        if (($branch->vehicle_number_required || $branch->scanner_enabled) && $rideNumber === '') {
            return back()->with('error', 'Vehicle number is required for this ride.');
        }

        if (array_key_exists('iot_battery_percent', $validated) && $validated['iot_battery_percent'] !== null && (float) $validated['iot_battery_percent'] <= 10) {
            return back()->with('error', 'Scooter battery is too low. Assign another scooter.');
        }

        if ($rideNumber !== '' && $this->rideNumberAlreadyAssigned($branch->id, $ride->branch_vehicle_id, $rideNumber, $ride->id)) {
            return back()->with('error', $ride->vehicle_name . ' number ' . $rideNumber . ' is already assigned in this branch.');
        }

        $ride->update([
            'ride_number' => $rideNumber !== '' ? $rideNumber : null,
            'start_time' => now(),
            'assign_battery_percent' => $this->validatedBatteryPercent($validated['iot_battery_percent'] ?? null),
            'status' => 'ongoing',
        ]);

        $booking = $ride->booking->fresh('rides');
        $booking->update([
            'status' => $this->resolveBookingStatus($booking->rides),
        ]);

        $redirectParams = [];
        $returnSearch = trim((string) ($validated['return_search'] ?? ''));
        $returnBooking = (int) ($validated['return_booking'] ?? $booking->id);

        if ($returnSearch !== '') {
            $redirectParams['search'] = $returnSearch;
        }

        if ($booking->rides->contains(fn (BookingRide $item) => $item->status === 'pending')) {
            $redirectParams['booking'] = $returnBooking ?: $booking->id;
        }

        return redirect()->route('index', $redirectParams)->with('success', $ride->vehicle_name . ' assigned successfully.');
    }

    public function ongoing()
    {
        $this->ensureUserLoggedIn();

        $search = trim((string) request('search', ''));
        $openBookingId = (int) request('booking', 0);
        $branch = $this->currentBranch();
        abort_unless($branch, 403);

        $bookings = $this->branchBookings()
            ->with(['rides' => fn ($query) => $query->whereIn('status', ['ongoing', 'finished'])->orderBy('id')])
            ->whereHas('rides', fn ($query) => $query->where('status', 'ongoing'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhereHas('rides', function ($rideQuery) use ($search) {
                            $rideQuery
                                ->where('vehicle_name', 'like', "%{$search}%")
                                ->orWhere('ride_number', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('paid_at')
            ->get();

        return view('theme.ongoing-flow', compact('bookings', 'search', 'branch', 'openBookingId'));
    }

    public function finishSingleRide(Request $request, BookingRide $ride): RedirectResponse
    {
        $this->ensureUserLoggedIn();
        $this->ensureRideBelongsToUserBranch($ride);

        $branch = $this->currentBranch();
        abort_unless($branch, 403);

        if ($ride->status !== 'ongoing') {
            return redirect()->route('ongoing')->with('error', 'This ride is not ongoing.');
        }

        $validated = $request->validate([
            'return_search' => ['nullable', 'string', 'max:255'],
            'return_booking' => ['nullable', 'integer'],
            'iot_distance_km' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'iot_battery_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $booking = $this->finishRideAndRefreshBooking($ride, $branch, $validated);

        $redirectParams = [];
        $returnSearch = trim((string) ($validated['return_search'] ?? ''));
        $returnBooking = (int) ($validated['return_booking'] ?? $booking->id);

        if ($returnSearch !== '') {
            $redirectParams['search'] = $returnSearch;
        }

        if ($booking->rides->contains(fn (BookingRide $item) => $item->status === 'ongoing')) {
            $redirectParams['booking'] = $returnBooking ?: $booking->id;
        }

        return redirect()->route('ongoing', $redirectParams)->with('success', 'Ride completed successfully.');
    }

    public function finishRideByNumber(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureUserLoggedIn();
        $this->ensureBookingBelongsToUserBranch($booking);

        $branch = $this->currentBranch();
        abort_unless($branch, 403);

        if (!$branch->vehicle_number_required && !$branch->scanner_enabled) {
            return redirect()->route('ongoing', ['booking' => $booking->id])
                ->with('error', 'Vehicle number completion is not enabled for this branch.');
        }

        $validated = $request->validate([
            'ride_number' => ['nullable', 'string', 'max:50'],
            'iot_device_id' => ['nullable', 'string', 'max:100'],
            'iot_distance_km' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'iot_battery_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'return_search' => ['nullable', 'string', 'max:255'],
            'return_booking' => ['nullable', 'integer'],
        ]);

        $rideNumber = trim((string) ($validated['ride_number'] ?? ''));
        $iotDeviceId = $this->normalizeScooterId((string) ($validated['iot_device_id'] ?? ''));

        if ($rideNumber === '' && $iotDeviceId !== '') {
            $rideNumber = $iotDeviceId;
        }

        if ($rideNumber === '') {
            return back()->with('error', 'Vehicle number is required for this ride.');
        }

        $ride = $booking->rides()
            ->where('status', 'ongoing')
            ->where('ride_number', $rideNumber)
            ->orderBy('id')
            ->first();

        if (!$ride) {
            return back()->with('error', 'Entered vehicle number does not match any ongoing ride in this booking.');
        }

        $booking = $this->finishRideAndRefreshBooking($ride, $branch, $validated);

        $redirectParams = [];
        $returnSearch = trim((string) ($validated['return_search'] ?? ''));
        $returnBooking = (int) ($validated['return_booking'] ?? $booking->id);

        if ($returnSearch !== '') {
            $redirectParams['search'] = $returnSearch;
        }

        if ($booking->rides->contains(fn (BookingRide $item) => $item->status === 'ongoing')) {
            $redirectParams['booking'] = $returnBooking ?: $booking->id;
        }

        return redirect()->route('ongoing', $redirectParams)->with('success', 'Ride completed successfully.');
    }

    public function finishBooking(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureUserLoggedIn();
        $this->ensureBookingBelongsToUserBranch($booking);

        $branch = $this->currentBranch();
        abort_unless($branch, 403);

        $ongoingRides = $booking->rides()->where('status', 'ongoing')->get();

        if ($ongoingRides->isEmpty()) {
            return redirect()->route('ongoing')->with('error', 'There is no ongoing ride to finish.');
        }

        $endTime = now();

        foreach ($ongoingRides as $ride) {
            $actualMinutes = $this->calculateActualRideMinutes($ride, $endTime);
            $charge = $this->calculateRideCharge($ride, $actualMinutes, (int) $branch->buffer_time);
            $tripDistanceKm = $this->validatedTripDistance($request->input('iot_distance_km'));

            Log::info('Ride IoT trip capture', [
                'ride_id' => $ride->id,
                'ride_number' => $ride->ride_number,
                'raw_iot_distance_km' => $request->input('iot_distance_km'),
                'trip_distance_km' => $tripDistanceKm,
            ]);

            $ride->update([
                'end_time' => $endTime,
                'actual_minutes' => $actualMinutes,
                'trip_distance_km' => $tripDistanceKm,
                'average_speed_kph' => $this->calculateAverageSpeedKph($tripDistanceKm, $actualMinutes),
                'complete_battery_percent' => $this->validatedBatteryPercent($request->input('iot_battery_percent')),
                'charge' => $charge,
                'status' => 'finished',
            ]);
        }

        $booking->load('rides');
        $booking->update([
            'status' => $this->resolveBookingStatus($booking->rides),
            'total_amount' => (float) $booking->rides()->sum('charge'),
            'final_amount' => (float) $booking->rides->sum(fn (BookingRide $item) => $item->final_charge ?? $item->charge),
        ]);

        return redirect()->route('ongoing')->with('success', 'Ride completed successfully.');
    }

    public function collectPayment()
    {
        $this->ensureUserLoggedIn();

        $search = trim((string) request('search', ''));
        $payments = $this->branchBookings()
            ->with(['rides' => fn ($query) => $query->orderBy('id')])
            ->whereHas('rides', fn ($query) => $query->where('status', 'finished'))
            ->whereDoesntHave('rides', fn ($query) => $query->where('status', '!=', 'finished'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhereHas('rides', function ($rideQuery) use ($search) {
                            $rideQuery
                                ->where('vehicle_name', 'like', "%{$search}%")
                                ->orWhere('ride_number', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('paid_at')
            ->get();

        $discountReasons = DiscountReason::query()
            ->where('is_active', true)
            ->orderBy('reason')
            ->get();

        return view('theme.payment-review', compact('payments', 'discountReasons', 'search'));
    }

    public function paymentDetail(Booking $booking)
    {
        $this->ensureUserLoggedIn();
        $this->ensureBookingBelongsToUserBranch($booking);
        $booking->load(['rides' => fn ($query) => $query->orderBy('id')]);

        if (!$this->bookingReadyForPayment($booking->rides)) {
            return redirect()->route('payments.collect')->with('error', 'This booking is not ready for payment.');
        }

        $discountReasons = DiscountReason::query()
            ->where('is_active', true)
            ->orderBy('reason')
            ->get();

        $payableRides = $booking->rides->where('status', 'finished')->values();

        $rideCountSummary = $payableRides
            ->groupBy('vehicle_name')
            ->map(fn (Collection $rides, string $vehicleName) => $rides->count() . ' ' . $vehicleName)
            ->values()
            ->implode(', ');

        $subtotal = (float) $payableRides->sum('charge');
        $totalDiscount = (float) $payableRides->sum('discount_amount');
        $finalTotal = max($subtotal - $totalDiscount, 0);

        return view('theme.payment-detail', compact(
            'booking',
            'payableRides',
            'discountReasons',
            'rideCountSummary',
            'subtotal',
            'totalDiscount',
            'finalTotal'
        ));
    }

    public function storePayment(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureUserLoggedIn();
        $this->ensureBookingBelongsToUserBranch($booking);
        $booking->load('rides');

        if (!$this->bookingReadyForPayment($booking->rides)) {
            return redirect()->route('payments.collect')->with('error', 'This booking is not ready for payment.');
        }

        $validated = $request->validate([
            'discount_ride_id' => ['nullable', 'integer'],
            'discount_reason_id' => ['nullable', 'exists:discount_reasons,id'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,online'],
        ]);

        $payableRides = $booking->rides->where('status', 'finished')->values();
        $subtotal = (float) $payableRides->sum('charge');
        $selectedRideId = (int) ($validated['discount_ride_id'] ?? 0);
        $reasonId = $validated['discount_reason_id'] ?? null;
        $reason = $reasonId ? DiscountReason::find($reasonId)?->reason : null;
        $discountRide = $selectedRideId ? $payableRides->firstWhere('id', $selectedRideId) : null;
        $requestedDiscount = (float) ($validated['discount_amount'] ?? 0);
        $hasDiscountInput = $selectedRideId > 0 || !is_null($reasonId) || $requestedDiscount > 0;

        if ($selectedRideId && !$discountRide) {
            return back()->with('error', 'Selected discount ride is invalid.');
        }

        if ($hasDiscountInput && !$selectedRideId) {
            return back()->withInput()->with('error', 'Please select a discount ride before entering a discount amount.');
        }

        if ($hasDiscountInput && !$reasonId) {
            return back()->withInput()->with('error', 'Please select a discount reason before entering a discount amount.');
        }

        if ($requestedDiscount > $subtotal) {
            return back()->withInput()->with('error', 'Discount amount cannot be greater than the total payment.');
        }

        $totalDiscount = 0.0;

        foreach ($payableRides as $ride) {
            $discountAmount = 0.0;

            if ($discountRide && $discountRide->id === $ride->id) {
                $discountAmount = min(max($requestedDiscount, 0), (float) $ride->charge);
            }

            $finalCharge = max((float) $ride->charge - $discountAmount, 0);
            $ride->update([
                'discount_reason' => $discountAmount > 0 ? $reason : null,
                'discount_amount' => $discountAmount,
                'final_charge' => $finalCharge,
                'status' => 'completed',
            ]);

            $totalDiscount += $discountAmount;
        }

        $booking->refresh()->load('rides');
        $resolvedStatus = $this->resolveBookingStatus($booking->rides);
        $booking->update([
            'discount_reason' => $resolvedStatus === 'completed' ? null : $booking->discount_reason,
            'discount_amount' => (float) $booking->rides->sum('discount_amount'),
            'total_amount' => (float) $booking->rides->sum('charge'),
            'final_amount' => (float) $booking->rides->sum(fn (BookingRide $item) => $item->final_charge ?? $item->charge),
            'payment_method' => $resolvedStatus === 'completed' ? $validated['payment_method'] : null,
            'paid_at' => $resolvedStatus === 'completed' ? now() : null,
            'status' => $resolvedStatus,
        ]);

        return redirect()->route('payments.collect')
            ->with('success', 'Payment collected successfully.')
            ->with('payment_completed', true);
    }

    public function todayPayment()
    {
        $this->ensureUserLoggedIn();

        $todayPayments = $this->branchBookings()
            ->with('rides')
            ->where('status', 'completed')
            ->whereDate('paid_at', today())
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get();

        $rideCounts = $todayPayments
            ->flatMap(fn (Booking $booking) => $booking->rides)
            ->groupBy('vehicle_name')
            ->map(fn (Collection $rides) => $rides->count())
            ->sortKeys();

        $totalAmount = (float) $todayPayments->sum('total_amount');
        $discountTotal = (float) $todayPayments->sum('discount_amount');
        $todayTotal = (float) $todayPayments->sum('final_amount');
        $cashTotal = (float) $todayPayments->where('payment_method', 'cash')->sum('final_amount');
        $onlineTotal = (float) $todayPayments->where('payment_method', 'online')->sum('final_amount');
        $totalRideCount = (int) $rideCounts->sum();

        return view('theme.today-payment', compact(
            'rideCounts',
            'totalAmount',
            'discountTotal',
            'todayTotal',
            'cashTotal',
            'onlineTotal',
            'totalRideCount'
        ));
    }

    public function completedBookings()
    {
        $this->ensureUserLoggedIn();

        $search = trim((string) request('search', ''));
        $bookings = $this->branchBookings()
            ->with(['rides' => fn ($query) => $query->where('status', 'completed')->orderBy('id')])
            ->whereHas('rides', fn ($query) => $query->where('status', 'completed')->where('updated_at', '>=', now()->subDay()))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhereHas('rides', function ($rideQuery) use ($search) {
                            $rideQuery
                                ->where('vehicle_name', 'like', "%{$search}%")
                                ->orWhere('ride_number', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('paid_at')
            ->get();

        return view('theme.completed-bookings', compact('bookings', 'search'));
    }

    public function collectedPayment()
    {
        return redirect()->route('payments.collect');
    }

    protected function ensureUserLoggedIn(): void
    {
        abort_unless($this->currentUser(), 403);
    }

    protected function currentUser(): ?User
    {
        if (Auth::check()) {
            $user = Auth::user()?->load('branchRelation');

            if ($user) {
                session()->put('userdata', $user);
            }

            return $user;
        }

        $sessionUser = session('userdata');

        if (!$sessionUser?->id) {
            return null;
        }

        $user = User::with('branchRelation')->find($sessionUser->id);

        if ($user) {
            session()->put('userdata', $user);
        }

        return $user;
    }

    protected function currentBranch(): ?Branch
    {
        return $this->currentUser()?->branchRelation;
    }

    protected function branchBookings()
    {
        $branch = $this->currentBranch();

        return Booking::query()->when($branch, fn ($query) => $query->where('branch_id', $branch->id));
    }

    protected function ensureBookingBelongsToUserBranch(Booking $booking): void
    {
        $branch = $this->currentBranch();
        abort_unless($branch && $booking->branch_id === $branch->id, 403);
    }

    protected function ensureRideBelongsToUserBranch(BookingRide $ride): void
    {
        $ride->loadMissing('booking');
        abort_unless($ride->booking, 404);
        $this->ensureBookingBelongsToUserBranch($ride->booking);
    }

    protected function availableBranchVehicles(Branch $branch): Collection
    {
        return BranchVehicle::query()
            ->where('branch_id', $branch->id)
            ->orderBy('name')
            ->get()
            ->map(function (BranchVehicle $vehicle) {
                $vehicle->available_quantity = 1;

                return $vehicle;
            });
    }

    protected function branchVehicles(Branch $branch): Collection
    {
        return BranchVehicle::query()
            ->where('branch_id', $branch->id)
            ->orderBy('name')
            ->get();
    }

    protected function storeUploadedFile($file, string $uploadDirectory): string
    {
        $fileName = uniqid('booking_', true) . '.' . $file->getClientOriginalExtension();
        $file->move($uploadDirectory, $fileName);

        return $fileName;
    }

    protected function calculateRideCharge(BookingRide $ride, int $actualMinutes, int $bufferTime): float
    {
        $slotMinutes = max(1, (int) $ride->vehicle_time);
        $pricePerMinute = (float) $ride->vehicle_price / $slotMinutes;
        $extraMinutes = max(0, $actualMinutes - $slotMinutes - $bufferTime);
        $chargeableMinutes = $slotMinutes + (int) ceil($extraMinutes / $slotMinutes) * $slotMinutes;

        return round($pricePerMinute * $chargeableMinutes, 2);
    }

    protected function calculateActualRideMinutes(BookingRide $ride, $endTime): int
    {
        if (!$ride->start_time) {
            return max(1, (int) $ride->vehicle_time);
        }

        $actualSeconds = max(1, (int) ceil($ride->start_time->diffInSeconds($endTime)));

        return max(1, (int) ceil($actualSeconds / 60));
    }

    protected function finishRideAndRefreshBooking(BookingRide $ride, Branch $branch, array $input = []): Booking
    {
        $endTime = now();
        $actualMinutes = $this->calculateActualRideMinutes($ride, $endTime);
        $charge = $this->calculateRideCharge($ride, $actualMinutes, (int) $branch->buffer_time);
        $tripDistanceKm = $this->validatedTripDistance($input['iot_distance_km'] ?? null);

        Log::info('Ride IoT trip capture', [
            'ride_id' => $ride->id,
            'ride_number' => $ride->ride_number,
            'raw_iot_distance_km' => $input['iot_distance_km'] ?? null,
            'trip_distance_km' => $tripDistanceKm,
        ]);

        $ride->update([
            'end_time' => $endTime,
            'actual_minutes' => $actualMinutes,
            'trip_distance_km' => $tripDistanceKm,
            'average_speed_kph' => $this->calculateAverageSpeedKph($tripDistanceKm, $actualMinutes),
            'complete_battery_percent' => $this->validatedBatteryPercent($input['iot_battery_percent'] ?? null),
            'charge' => $charge,
            'status' => 'finished',
        ]);

        $booking = $ride->booking->fresh('rides');
        $booking->update([
            'status' => $this->resolveBookingStatus($booking->rides),
            'total_amount' => (float) $booking->rides->sum('charge'),
            'final_amount' => (float) $booking->rides->sum(fn (BookingRide $item) => $item->final_charge ?? $item->charge),
        ]);

        return $booking;
    }

    protected function validatedTripDistance($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round(max(0, (float) $value), 3);
    }

    protected function calculateAverageSpeedKph(?float $tripDistanceKm, int $actualMinutes): ?float
    {
        if ($tripDistanceKm === null || $actualMinutes <= 0) {
            return null;
        }

        return round($tripDistanceKm / ($actualMinutes / 60), 2);
    }

    protected function validatedBatteryPercent($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return min(100, max(0, (int) round((float) $value)));
    }

    protected function bookingReadyForPayment(Collection $rides): bool
    {
        return $rides->isNotEmpty()
            && $rides->every(fn (BookingRide $ride) => $ride->status === 'finished');
    }

    protected function resolveBookingStatus(Collection $rides): string
    {
        if ($rides->contains(fn (BookingRide $item) => $item->status === 'pending')) {
            return 'pending';
        }

        if ($rides->contains(fn (BookingRide $item) => $item->status === 'ongoing')) {
            return 'ongoing';
        }

        if ($rides->contains(fn (BookingRide $item) => $item->status === 'finished')) {
            return 'payment_pending';
        }

        return 'completed';
    }

    protected function rideNumberAlreadyAssigned(int $branchId, ?int $branchVehicleId, string $rideNumber, ?int $ignoreRideId = null): bool
    {
        return BookingRide::query()
            ->where('ride_number', $rideNumber)
            ->where('status', 'ongoing')
            ->when($branchVehicleId, fn ($query) => $query->where('branch_vehicle_id', $branchVehicleId))
            ->when($ignoreRideId, fn ($query) => $query->where('id', '!=', $ignoreRideId))
            ->whereHas('booking', fn ($query) => $query->where('branch_id', $branchId))
            ->exists();
    }

    protected function normalizeScooterId(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (str_starts_with(strtolower($value), 'scooter:')) {
            return trim(substr($value, 8));
        }

        $query = (string) parse_url($value, PHP_URL_QUERY);

        if ($query !== '') {
            parse_str($query, $params);

            foreach (['scooter', 'iot'] as $key) {
                if (!empty($params[$key]) && is_string($params[$key])) {
                    return trim($params[$key]);
                }
            }
        }

        return $value;
    }
}
