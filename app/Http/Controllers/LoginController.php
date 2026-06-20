<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Branch;
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

        return view('index', compact(
            'totalCustomers',
            'taskPending',
            'totalDeals',
            'totalRevenue',
            'totalVehicles',
            'totalBranches'
        ));
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
