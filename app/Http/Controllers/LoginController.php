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
        $totalVehicles = class_exists(BranchVehicle::class) ? BranchVehicle::count() : Vehicle::count();
        $branches = Branch::orderBy('name')->get();
        $selectedBranchId = (int) request('branch_id', $branches->first()?->id ?? 0);
        $liveDashboardStats = $this->liveDashboardStats($selectedBranchId);

        return view('index', compact(
            'totalCustomers',
            'taskPending',
            'totalDeals',
            'totalRevenue',
            'totalVehicles',
            'totalBranches',
            'branches',
            'selectedBranchId',
            'liveDashboardStats'
        ));
    }

    public function liveStats(Request $request)
    {
        $branchId = (int) $request->query('branch_id');

        return response()->json([
            'status' => true,
            'stats' => $this->liveDashboardStats($branchId),
        ]);
    }

    protected function liveDashboardStats(int $branchId): array
    {
        $branch = Branch::find($branchId) ?: Branch::orderBy('name')->first();

        if (!$branch) {
            return [
                'branch_id' => null,
                'total_scooters' => 0,
                'ongoing_rides' => 0,
                'available_scooters' => 0,
                'online_scooters' => 0,
                'low_battery_scooters' => 0,
                'reported_at' => null,
            ];
        }

        $totalScooters = BranchVehicle::where('branch_id', $branch->id)->count();
        $ongoingRides = BookingRide::where('status', 'ongoing')
            ->whereHas('booking', fn ($query) => $query->where('branch_id', $branch->id))
            ->count();
        $liveStat = BranchLiveStat::where('branch_id', $branch->id)->first();
        $hasFreshLiveStat = $liveStat?->reported_at && $liveStat->reported_at->greaterThanOrEqualTo(now()->subMinutes(2));

        return [
            'branch_id' => $branch->id,
            'total_scooters' => $totalScooters,
            'ongoing_rides' => $ongoingRides,
            'available_scooters' => max(0, $totalScooters - $ongoingRides),
            'online_scooters' => $hasFreshLiveStat ? (int) $liveStat->online_scooters : 0,
            'low_battery_scooters' => $hasFreshLiveStat ? (int) $liveStat->low_battery_scooters : 0,
            'reported_at' => $hasFreshLiveStat ? $liveStat->reported_at->format('d M Y h:i A') : null,
        ];
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
