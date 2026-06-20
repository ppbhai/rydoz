<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Branch;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    public function bookingList(Request $request)
    {
        $bookings = $this->completedBookingQuery($request)->get();
        $branches = Branch::orderBy('name')->get();

        return view('complete-booking', compact('bookings', 'branches'));
    }

    public function pendingBookingList(Request $request)
    {
        $bookings = $this->pendingBookingQuery($request)->get();
        $branches = Branch::orderBy('name')->get();

        return view('pending-booking', compact('bookings', 'branches'));
    }

    public function discountedBookingList(Request $request)
    {
        $bookings = $this->discountedBookingQuery($request)->get();
        $branches = Branch::orderBy('name')->get();

        return view('discounted-booking', compact('bookings', 'branches'));
    }

    public function exportCompletedBookings(Request $request): StreamedResponse
    {
        $bookings = $this->completedBookingQuery($request)->get();

        return $this->streamBookingsCsv($bookings, 'completed-bookings-' . now()->format('Ymd-His') . '.csv', true);
    }

    public function exportPendingBookings(Request $request): StreamedResponse
    {
        $bookings = $this->pendingBookingQuery($request)->get();

        return $this->streamBookingsCsv($bookings, 'pending-bookings-' . now()->format('Ymd-His') . '.csv', false, true);
    }

    public function exportDiscountedBookings(Request $request): StreamedResponse
    {
        $bookings = $this->discountedBookingQuery($request)->get();

        return $this->streamBookingsCsv($bookings, 'discounted-bookings-' . now()->format('Ymd-His') . '.csv');
    }

    public function paymentReport(Request $request)
    {
        $branches = Branch::orderBy('name')->get();

        return view('payment-report', array_merge(
            $this->paymentReportData($request),
            compact('branches')
        ));
    }

    public function exportPaymentReport(Request $request): StreamedResponse
    {
        return $this->streamPaymentReportCsv(
            $this->paymentReportData($request),
            'payment-report-' . now()->format('Ymd-His') . '.csv'
        );
    }

    protected function paymentReportQuery(Request $request)
    {
        return Booking::with(['rides', 'branchRelation'])
            ->where('status', 'completed')
            ->when($request->filled('branch_id') && $request->branch_id !== 'all', function ($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            })
            ->when($request->filled('from_date'), function ($query) use ($request) {
                $query->whereDate('paid_at', '>=', $this->limitedReportDate($request->from_date));
            })
            ->when($request->filled('to_date'), function ($query) use ($request) {
                $query->whereDate('paid_at', '<=', $this->limitedReportDate($request->to_date));
            })
            ->latest('paid_at')
            ->latest('id');
    }

    protected function allBookingReportQuery(Request $request)
    {
        return Booking::query()
            ->when($request->filled('branch_id') && $request->branch_id !== 'all', function ($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            })
            ->when($request->filled('from_date') || $request->filled('to_date'), function ($query) use ($request) {
                $query->where(function ($dateQuery) use ($request) {
                    $dateQuery
                        ->where(function ($createdQuery) use ($request) {
                            $this->applyDateRange($createdQuery, $request, 'created_at');
                        })
                        ->orWhere(function ($paidQuery) use ($request) {
                            $this->applyDateRange($paidQuery, $request, 'paid_at');
                        });
                });
            });
    }

    protected function completedBookingQuery(Request $request)
    {
        return Booking::with(['rides', 'branchRelation'])
            ->where('status', 'completed')
            ->when($request->filled('branch_id') && $request->branch_id !== 'all', function ($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            })
            ->when($request->filled('from_date'), function ($query) use ($request) {
                $query->whereDate('paid_at', '>=', $this->limitedReportDate($request->from_date));
            })
            ->when($request->filled('to_date'), function ($query) use ($request) {
                $query->whereDate('paid_at', '<=', $this->limitedReportDate($request->to_date));
            })
            ->latest('paid_at')
            ->latest('id');
    }

    protected function applyDateRange($query, Request $request, string $column): void
    {
        if ($request->filled('from_date')) {
            $query->whereDate($column, '>=', $this->limitedReportDate($request->from_date));
        }

        if ($request->filled('to_date')) {
            $query->whereDate($column, '<=', $this->limitedReportDate($request->to_date));
        }
    }

    protected function limitedReportDate(?string $date): string
    {
        return min((string) $date, today()->toDateString());
    }

    protected function pendingBookingQuery(Request $request)
    {
        return Booking::with(['rides', 'branchRelation'])
            ->whereIn('status', ['otp_pending', 'pending', 'ongoing', 'payment_pending'])
            ->when($request->filled('branch_id') && $request->branch_id !== 'all', function ($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            })
            ->when($request->filled('from_date'), function ($query) use ($request) {
                $query->whereDate('created_at', '>=', $this->limitedReportDate($request->from_date));
            })
            ->when($request->filled('to_date'), function ($query) use ($request) {
                $query->whereDate('created_at', '<=', $this->limitedReportDate($request->to_date));
            })
            ->latest('id');
    }

    protected function discountedBookingQuery(Request $request)
    {
        return Booking::with(['rides', 'branchRelation'])
            ->where('status', 'completed')
            ->where('discount_amount', '>', 0)
            ->when($request->filled('branch_id') && $request->branch_id !== 'all', function ($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            })
            ->when($request->filled('from_date'), function ($query) use ($request) {
                $query->whereDate('paid_at', '>=', $this->limitedReportDate($request->from_date));
            })
            ->when($request->filled('to_date'), function ($query) use ($request) {
                $query->whereDate('paid_at', '<=', $this->limitedReportDate($request->to_date));
            })
            ->latest('paid_at')
            ->latest('id');
    }

    protected function paymentReportData(Request $request): array
    {
        $payments = $this->paymentReportQuery($request)->get();
        $rides = $payments->flatMap(fn (Booking $booking) => $booking->rides);

        return [
            'payments' => $payments,
            'totalAmount' => (float) $payments->sum('final_amount'),
            'onlineAmount' => (float) $payments->where('payment_method', 'online')->sum('final_amount'),
            'cashAmount' => (float) $payments->where('payment_method', 'cash')->sum('final_amount'),
            'discountAmount' => (float) $payments->sum('discount_amount'),
            'totalRideCount' => $rides->count(),
            'vehicleBookingCounts' => $rides
                ->groupBy('vehicle_name')
                ->map(fn ($vehicleRides) => $vehicleRides->count())
                ->sortKeys(),
            'totalBookingCount' => $this->allBookingReportQuery($request)->count(),
            'pendingBookingCount' => $this->pendingBookingQuery($request)->count(),
            'completedBookingCount' => $this->completedBookingQuery($request)->count(),
            'discountedBookingCount' => $this->discountedBookingQuery($request)->count(),
        ];
    }

    protected function streamBookingsCsv($bookings, string $filename, bool $perRide = false, bool $includeOtp = false): StreamedResponse
    {
        return response()->streamDownload(function () use ($bookings, $perRide, $includeOtp) {
            $handle = fopen('php://output', 'w');

            $header = $perRide
                ? [
                    'No.',
                    'Branch',
                    'Booking ID',
                    'Date',
                    'Name',
                    'Mobile No.',
                ]
                : [
                    'Booking ID',
                    'Branch',
                    'Name',
                    'Mobile',
                ];

            if ($includeOtp) {
                $header[] = 'Sent OTP';
            }

            $header = $perRide
                ? array_merge($header, [
                    'Vehicle',
                    'Vehicle ID',
                    'Assign Ride Time',
                    'Actual Time',
                    'Battery Percentage When Assign',
                    'Battery Percentage When Complete',
                    'Used Battery',
                    'Total KM',
                    'Average Speed',
                    'Total Amount',
                    'Discount',
                    'Final Payment',
                    'Payment Method',
                ])
                : array_merge($header, [
                    'Status',
                    'Vehicles',
                    'Vehicle ID',
                    'Actual Time',
                    'Total KM',
                    'Average Speed',
                    'Document Type',
                    'Total Amount',
                    'Discount Amount',
                    'Discount Reason',
                    'Final Amount',
                    'Payment Method',
                    'Created At',
                    'Paid At',
                ]);

            fputcsv($handle, $header);

            $rowNumber = 1;

            foreach ($bookings as $booking) {
                if ($perRide) {
                    foreach ($booking->rides as $ride) {
                        $usedBattery = $ride->assign_battery_percent !== null && $ride->complete_battery_percent !== null
                            ? max(0, (int) $ride->assign_battery_percent - (int) $ride->complete_battery_percent)
                            : null;

                        $row = [
                            $rowNumber++,
                            $booking->branchRelation?->name ?: $booking->branch_name ?: $booking->branch ?: '-',
                            $booking->id,
                            ($booking->paid_at ?: $booking->created_at)?->format('Y-m-d H:i:s'),
                            $booking->name,
                            $booking->mobile,
                        ];

                        if ($includeOtp) {
                            $row[] = $booking->otp ?: '-';
                        }

                        $row = array_merge($row, [
                            $ride->vehicle_name ?: '-',
                            $ride->ride_number ?: '-',
                            $ride->start_time?->format('Y-m-d H:i:s') ?: '-',
                            $ride->actual_minutes ? $ride->actual_minutes . ' min' : '-',
                            $ride->assign_battery_percent !== null ? $ride->assign_battery_percent . '%' : '-',
                            $ride->complete_battery_percent !== null ? $ride->complete_battery_percent . '%' : '-',
                            $usedBattery !== null ? $usedBattery . '%' : '-',
                            $ride->trip_distance_km !== null ? number_format((float) $ride->trip_distance_km, 3, '.', '') : '-',
                            $ride->average_speed_kph !== null ? number_format((float) $ride->average_speed_kph, 2, '.', '') : '-',
                            number_format((float) $ride->charge, 2, '.', ''),
                            number_format((float) $ride->discount_amount, 2, '.', ''),
                            number_format((float) ($ride->final_charge ?: $ride->charge), 2, '.', ''),
                            $booking->payment_method ?: '-',
                        ]);

                        fputcsv($handle, $row);
                    }

                    continue;
                }

                $vehicleSummary = $booking->rides
                    ->groupBy('vehicle_name')
                    ->map(fn ($rides, $vehicleName) => $vehicleName . ' x ' . $rides->count())
                    ->values()
                    ->implode(', ');

                $rideNumbers = $booking->rides
                    ->pluck('ride_number')
                    ->filter()
                    ->implode(', ');

                $actualTimes = $booking->rides
                    ->map(fn ($ride) => $ride->actual_minutes ? $ride->actual_minutes . ' min' : '-')
                    ->implode(', ');

                $tripDistances = $booking->rides
                    ->map(fn ($ride) => $ride->trip_distance_km !== null ? number_format((float) $ride->trip_distance_km, 3, '.', '') . ' km' : '-')
                    ->implode(', ');

                $averageSpeeds = $booking->rides
                    ->map(fn ($ride) => $ride->average_speed_kph !== null ? number_format((float) $ride->average_speed_kph, 2, '.', '') . ' km/h' : '-')
                    ->implode(', ');

                $row = [
                    $booking->id,
                    $booking->branchRelation?->name ?: $booking->branch_name ?: $booking->branch ?: '-',
                    $booking->name,
                    $booking->mobile,
                ];

                if ($includeOtp) {
                    $row[] = $booking->otp ?: '-';
                }

                $row = array_merge($row, [
                    $booking->status,
                    $vehicleSummary,
                    $rideNumbers,
                    $actualTimes,
                    $tripDistances,
                    $averageSpeeds,
                    $booking->document_type ?: 'Not selected',
                    number_format((float) $booking->total_amount, 2, '.', ''),
                    number_format((float) $booking->discount_amount, 2, '.', ''),
                    $this->discountReasonSummary($booking),
                    number_format((float) $booking->final_amount, 2, '.', ''),
                    $booking->payment_method ?: '-',
                    $booking->created_at?->format('Y-m-d H:i:s'),
                    $booking->paid_at?->format('Y-m-d H:i:s'),
                ]);

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function streamPaymentReportCsv(array $reportData, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($reportData) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Payment Report Summary']);
            fputcsv($handle, ['Total Ride', $reportData['totalRideCount']]);

            foreach ($reportData['vehicleBookingCounts'] as $vehicleName => $vehicleCount) {
                fputcsv($handle, [$vehicleName, $vehicleCount]);
            }

            fputcsv($handle, ['Total Booking', $reportData['totalBookingCount']]);
            fputcsv($handle, ['Pending Booking', $reportData['pendingBookingCount']]);
            fputcsv($handle, ['Completed Booking', $reportData['completedBookingCount']]);
            fputcsv($handle, ['Discounted Booking', $reportData['discountedBookingCount']]);
            fputcsv($handle, ['Total Payment', number_format($reportData['totalAmount'], 2, '.', '')]);
            fputcsv($handle, ['Cash Payment', number_format($reportData['cashAmount'], 2, '.', '')]);
            fputcsv($handle, ['Online Payment', number_format($reportData['onlineAmount'], 2, '.', '')]);
            fputcsv($handle, ['Discount Amount', number_format($reportData['discountAmount'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                '#',
                'Booking ID',
                'Branch',
                'Name',
                'Mobile',
                'Vehicle',
                'Vehicle ID',
                'Total',
                'Discount',
                'Final',
                'Method',
                'Reason',
                'Paid At',
            ]);

            $rowNumber = 1;

            foreach ($reportData['payments'] as $payment) {
                foreach ($payment->rides as $ride) {
                    fputcsv($handle, [
                        $rowNumber++,
                        $payment->id,
                        $payment->branchRelation?->name ?: $payment->branch_name ?: $payment->branch ?: '-',
                        $payment->name,
                        $payment->mobile,
                        $ride->vehicle_name ?: '-',
                        $ride->ride_number ?: '-',
                        number_format((float) $ride->charge, 2, '.', ''),
                        number_format((float) $ride->discount_amount, 2, '.', ''),
                        number_format((float) ($ride->final_charge ?: $ride->charge), 2, '.', ''),
                        $payment->payment_method ?: '-',
                        $ride->discount_reason ?: ($payment->discount_reason ?: '-'),
                        $payment->paid_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function discountReasonSummary(Booking $booking): string
    {
        $rideReasons = $booking->rides
            ->filter(fn ($ride) => (float) $ride->discount_amount > 0 && filled($ride->discount_reason))
            ->pluck('discount_reason')
            ->unique()
            ->values()
            ->implode(', ');

        return $rideReasons ?: ($booking->discount_reason ?: '-');
    }
}
