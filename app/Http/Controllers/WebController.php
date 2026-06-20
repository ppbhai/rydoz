<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use DB;
use File;
use Mail;
use App\Mail\AdminForgetMail;

class WebController extends Controller
{

    public function index()
    {
        if (!session()->has('userdata')) {
            return redirect()->route('userlogin');
        }
        return view('theme.index');
    }

    public function book()
    {
        $vehicles = Vehicle::all();
        return view('theme.book')->with('vehicles', $vehicles);
    }

    public function sendOtp(Request $request)
    {
        $otp = rand(1000, 9999);

        $booking = Booking::updateOrCreate(
            ['mobile' => $request->mobile],
            [
                'name' => $request->name,
                'otp' => $otp,
                'is_verified' => 0
            ]
        );

        return response()->json([
            'status' => true,
            'otp' => $otp
        ]);
    }

    public function confirmRide()
    {
        return view('theme.confirm-ride');
    }

    public function verifyOtp(Request $request)
    {
        $booking = Booking::where('mobile', $request->mobile)->first();

        if ($booking && $booking->otp == $request->otp) {
            $booking->update(['is_verified' => 1]);

            return response()->json(['status' => true]);
        }

        return response()->json(['status' => false]);
    }

    public function saveBooking(Request $request)
    {
        $booking = Booking::where('mobile', $request->mobile)->first();

        if (!$booking || !$booking->is_verified) {
            return response()->json([
                'status' => false,
                'msg' => 'OTP not verified'
            ]);
        }

        // ✅ Ensure folder exists
        $uploadPath = public_path('uploads');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // ✅ IMAGE UPLOAD
        $frontImageName = null;
        $backImageName = null;

        if ($request->hasFile('front_image')) {
            $frontImage = $request->file('front_image');
            $frontImageName = time() . '_front.' . $frontImage->getClientOriginalExtension();
            $frontImage->move($uploadPath, $frontImageName);
        }

        if ($request->hasFile('back_image')) {
            $backImage = $request->file('back_image');
            $backImageName = time() . '_back.' . $backImage->getClientOriginalExtension();
            $backImage->move($uploadPath, $backImageName);
        }

        // ✅ SAVE DATA
        $booking->update([
            'vehicles' => $request->vehicles,
            'document_type' => $request->document_type,
            'front_image' => $frontImageName,
            'back_image' => $backImageName,
            'status' => 'pending'
        ]);

        return response()->json(['status' => true]);
    }
}
