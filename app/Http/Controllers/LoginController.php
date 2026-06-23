<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingRide;
use App\Models\Branch;
use App\Models\BranchLiveStat;
use App\Models\BranchVehicle;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use DB;
use File;
use Mail;
use App\Mail\AdminForgetMail;

class LoginController extends Controller
{

    public function show()
    {
        return view('login');
    }
    public function checklogin(Request $request)
    {
        $adata = Admin::where('email', $request->email)->first();

        if ($adata && Hash::check($request->password, $adata->pwd)) {
            // Store as object
            Session()->put('admindata', $adata);
            return redirect()->route('dashboard');
        } elseif ($adata) {
            return redirect('/login')->with('error', 'Password Incorrect');
        } else {
            return redirect('/login')->with('error', 'User Not Found');
        }
    }



    public function dashboard()
    {
        $totalCustomers = User::count();
        $taskPending = Booking::whereIn('status', ['pending', 'ongoing', 'payment_pending'])->count();
        $totalDeals = Booking::where('status', 'completed')->count();
        $totalRevenue = (float) Booking::where('status', 'completed')->sum('final_amount');
        $totalBranches = Branch::count();
        $totalVehicles = class_exists(BranchVehicle::class) ? (int) BranchVehicle::sum('quantity') : Vehicle::count();
        $branches = Branch::orderBy('name')->get();
        $selectedBranchId = (int) request('branch_id', $branches->first()?->id ?? 0);
        $selectedVehicleId = (int) request('vehicle_id', 0);
        $liveDashboardStats = $this->liveDashboardStats($selectedBranchId, $selectedVehicleId);

        return view('index', compact(
            'totalCustomers',
            'taskPending',
            'totalDeals',
            'totalRevenue',
            'totalVehicles',
            'totalBranches',
            'branches',
            'selectedBranchId',
            'selectedVehicleId',
            'liveDashboardStats'
        ));
    }

    public function liveStats(Request $request)
    {
        $branchId = (int) $request->query('branch_id');
        $vehicleId = (int) $request->query('vehicle_id');

        return response()->json([
            'status' => true,
            'stats' => $this->liveDashboardStats($branchId, $vehicleId),
        ]);
    }

    protected function liveDashboardStats(int $branchId, int $vehicleId = 0): array
    {
        $branch = Branch::find($branchId) ?: Branch::orderBy('name')->first();

        if (!$branch) {
            return [
                'branch_id' => null,
                'total_scooters' => 0,
                'ongoing_rides' => 0,
                'available_scooters' => 0,
                'offline_scooters' => 0,
                'online_scooters' => 0,
                'low_battery_scooters' => 0,
                'reported_at' => null,
                'scooters' => [],
                'vehicles' => [],
                'vehicle_id' => null,
            ];
        }

        $vehicles = BranchVehicle::query()
            ->where('branch_id', $branch->id)
            ->orderBy('name')
            ->get(['id', 'name', 'quantity']);
        $selectedVehicle = $vehicles->firstWhere('id', $vehicleId);
        $selectedVehicleId = $selectedVehicle?->id;
        $totalScooters = $selectedVehicle
            ? max(1, (int) $selectedVehicle->quantity)
            : (int) $vehicles->sum(fn (BranchVehicle $vehicle) => max(1, (int) $vehicle->quantity));
        $ongoingRides = BookingRide::where('status', 'ongoing')
            ->whereHas('booking', fn ($query) => $query->where('branch_id', $branch->id))
            ->when($selectedVehicleId, fn ($query) => $query->where('branch_vehicle_id', $selectedVehicleId))
            ->count();
        $liveStat = BranchLiveStat::where('branch_id', $branch->id)->first();
        $hasFreshLiveStat = $liveStat?->reported_at && $liveStat->reported_at->greaterThanOrEqualTo(now()->subMinutes(2));
        $liveScooters = $hasFreshLiveStat ? collect($liveStat->scooters ?: []) : collect();
        $scooters = $this->liveDashboardScooters($branch->id, $liveScooters, $selectedVehicleId);
        $onlineScooters = $selectedVehicleId
            ? collect($scooters)->where('is_online', true)->count()
            : ($hasFreshLiveStat ? (int) $liveStat->online_scooters : 0);
        $lowBatteryScooters = $selectedVehicleId
            ? collect($scooters)->filter(fn (array $scooter) => $scooter['battery'] !== null && $scooter['battery'] <= 10)->count()
            : ($hasFreshLiveStat ? (int) $liveStat->low_battery_scooters : 0);

        return [
            'branch_id' => $branch->id,
            'vehicle_id' => $selectedVehicleId,
            'vehicles' => $vehicles
                ->map(fn (BranchVehicle $vehicle) => [
                    'id' => $vehicle->id,
                    'name' => $vehicle->name,
                    'quantity' => max(1, (int) $vehicle->quantity),
                ])
                ->values()
                ->all(),
            'total_scooters' => $totalScooters,
            'ongoing_rides' => $ongoingRides,
            'available_scooters' => max(0, $totalScooters - $ongoingRides),
            'offline_scooters' => max(0, $totalScooters - ($ongoingRides + $onlineScooters)),
            'online_scooters' => $onlineScooters,
            'low_battery_scooters' => $lowBatteryScooters,
            'reported_at' => $hasFreshLiveStat ? $liveStat->reported_at->format('d M Y h:i A') : null,
            'scooters' => $scooters,
        ];
    }

    protected function liveDashboardScooters(int $branchId, $liveScooters, ?int $vehicleId = null): array
    {
        $liveBatteryByScooter = collect($liveScooters)
            ->mapWithKeys(function (array $scooter) {
                $scooterId = trim((string) ($scooter['scooterId'] ?? ''));

                if ($scooterId === '') {
                    return [];
                }

                return [
                    $scooterId => array_key_exists('battery', $scooter) && $scooter['battery'] !== null
                        ? (int) $scooter['battery']
                        : null,
                ];
            });

        $liveChargingByScooter = collect($liveScooters)
            ->mapWithKeys(function (array $scooter) {
                $scooterId = trim((string) ($scooter['scooterId'] ?? ''));

                if ($scooterId === '') {
                    return [];
                }

                return [$scooterId => (bool) ($scooter['charging'] ?? false)];
            });

        $rideQuery = BookingRide::query()
            ->whereNotNull('ride_number')
            ->where('ride_number', '!=', '')
            ->when($vehicleId, fn ($query) => $query->where('branch_vehicle_id', $vehicleId))
            ->whereHas('booking', fn ($query) => $query->where('branch_id', $branchId));

        $rideNumbers = (clone $rideQuery)
            ->select('ride_number')
            ->distinct()
            ->pluck('ride_number');

        $ongoingRideNumbers = (clone $rideQuery)
            ->where('status', 'ongoing')
            ->pluck('ride_number')
            ->unique()
            ->flip();

        $assignCountsLastDay = (clone $rideQuery)
            ->where('start_time', '>=', now()->subDay())
            ->selectRaw('ride_number, count(*) as assign_count')
            ->groupBy('ride_number')
            ->pluck('assign_count', 'ride_number');

        $lifetimeKm = (clone $rideQuery)
            ->selectRaw('ride_number, coalesce(sum(trip_distance_km), 0) as total_km')
            ->groupBy('ride_number')
            ->pluck('total_km', 'ride_number');

        $scooterIds = $vehicleId ? $rideNumbers : $rideNumbers->merge($liveBatteryByScooter->keys());

        return $scooterIds
            ->filter()
            ->unique()
            ->map(function (string $scooterId) use ($liveBatteryByScooter, $liveChargingByScooter, $ongoingRideNumbers, $assignCountsLastDay, $lifetimeKm) {
                $battery = $liveBatteryByScooter->has($scooterId) ? $liveBatteryByScooter->get($scooterId) : null;
                $charging = (bool) ($liveChargingByScooter->get($scooterId) ?? false);

                return [
                    'scooter_name' => $scooterId,
                    'battery' => $battery,
                    'charging' => $charging,
                    'is_online' => $liveBatteryByScooter->has($scooterId),
                    'status' => $charging ? 'charging' : ($ongoingRideNumbers->has($scooterId) ? 'ongoing' : 'available'),
                    'assigned_24h_count' => (int) ($assignCountsLastDay[$scooterId] ?? 0),
                    'lifetime_km' => round((float) ($lifetimeKm[$scooterId] ?? 0), 3),
                ];
            })
            ->sort(function (array $left, array $right) {
                if ($left['battery'] === null && $right['battery'] !== null) {
                    return 1;
                }

                if ($left['battery'] !== null && $right['battery'] === null) {
                    return -1;
                }

                if ($left['battery'] !== $right['battery']) {
                    return ($left['battery'] ?? 101) <=> ($right['battery'] ?? 101);
                }

                return strcmp($left['scooter_name'], $right['scooter_name']);
            })
            ->values()
            ->all();
    }
    public function showprofile(Admin $admin)
    {

        return view('adminprofileedit')->with('admin', $admin);
    }

    public function profileedit(Request $request, Admin $admin)
    {
        $request->validate([
            'unm' => 'required',
            'email' => 'required',
        ]);

        $unm = $request->unm;
        $email = $request->email;

        if ($request->file('adminimage') == null) {
            $admin->unm = $unm;
            $admin->email = $email;
            $admin->save();

        } else {
            File::delete(public_path('storage/' . $admin->image));
            $admin->unm = $unm;
            $admin->email = $email;
            $admin->image = $request->file('adminimage')->store('adminprofileimages', 'public');
            $admin->save();
        }

        return redirect('logout')->with('success', 'Profile Updated !');

    }

    public function adminpwdshow(Admin $admin)
    {
        return view('adminchagepwd')->with('admin', $admin);
    }

    public function changepwd(Request $request, Admin $admin)
    {
        $oldpwd = $request->oldpwd;
        $newpwd = $request->newpwd;
        $cpwd = $request->cpwd;


        if (Hash::check($oldpwd, $admin->pwd)) {
            if ($newpwd == $cpwd) {
                $admin->pwd = Hash::make($newpwd);
                $admin->save();
                return redirect()->back()->with('success', 'Password Changed !');
            } else {
                return redirect()->back()->with('error', 'Password Not Matched !');
            }
        } else {
            return redirect()->back()->with('error', 'Old Password Not Matched !');
        }

    }

    public function passwordrecovershow($token)
    {
        $admin = Admin::where('token', $token)->first();

        if ($admin) {
            $dbtoken = $admin->token;

            if ($dbtoken == $token && $admin) {
                return view('passwordrecover', compact('token'));
            } else {
                echo "Forget Password Link Expired!";
            }
        } else {
            echo "Forget Password Link Expired!";
        }

    }

    public function passwordrecoverprocess(Request $req)
    {
        $newpwd = $req->newpassword;
        $newcpwd = $req->confirmpassword;
        $token = $req->token;

        if ($newpwd == $newcpwd) {

            Admin::where('token', $token)->update(['pwd' => Hash::make($newpwd)]);
            $sms_code = random_int(111111, 999999);

            Admin::where('token', $token)->update(array(

                'token' => $sms_code,

            ));
            return redirect('/login')->with('success', 'Password Recovered. Login With New Password! ');
        } else {

            return back()->with('error', 'Confirm Password Not Matched ! ');
        }

    }

    public function forgetpassword(Request $req)
    {
        $req->validate([
            'email' => 'required',
        ]);
        $email = $req->email;

        $admindata = Admin::where('email', $email)->first();

        if ($admindata) {
            $token = $admindata->token;
            $details = [

                'admintoken' => $token,

            ];

            Mail::to($email)->send(new AdminForgetMail($details));

            return back()->with('success', 'Please check your mail inbox!');

        } else {
            return back()->with('error', 'Admin Email Not Found!');
        }
    }


}
