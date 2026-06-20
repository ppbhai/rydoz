<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\DiscountReasonController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RideFlowController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserLoginController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BookingController;

Route::get('/', function () {
    if (Session()->has('admindata')) {
        return redirect()->route('dashboard');
    }

    if (Auth::check() || Session()->has('userdata')) {
        return redirect()->route('index');
    }

    return redirect('/userlogin');
})->middleware('desktop.redirect');;

Route::get('/optimize', function () {
    Artisan::call('optimize:clear');
});

Route::get('/passwordrecover/{token}', [LoginController::class, 'passwordrecovershow'])->name('passwordrecover');
Route::post('/passwordrecoverprocess', [LoginController::class, 'passwordrecoverprocess'])->name('passwordrecoverprocess');

Route::get('/login', [LoginController::class, 'show']);
Route::post('/loginprocess', [LoginController::class, 'checklogin']);
Route::get('/adminforgetpwd', function () {
    return view('forgotpassword');
});
Route::post('/forgetpassword', [LoginController::class, 'forgetpassword'])->name('forgetpassword');

Route::get('logout', function () {

    if (Session()->has('admindata')) {

        Session()->forget('admindata');
        return redirect('/login');
    }

})->name('logout');

Route::get('/adminprofileupdate/{admin}', [LoginController::class, 'showprofile'])->name('adminprofileupdate');
Route::post('/adminprofileedit/{admin}', [LoginController::class, 'profileedit'])->name('adminprofileedit');
Route::get('/dashboard', [LoginController::class, 'dashboard'])->name('dashboard');



Route::get('/adminchagepwd/{admin}', [LoginController::class, 'adminpwdshow'])->name('adminchagepwd');
Route::post('/changepwd/{admin}', [LoginController::class, 'changepwd'])->name('changepwd');

/////////////////////////////////////// Testimonials ///////////////////////////
Route::get('/testimonialshow', [TestimonialController::class, 'testimonialshow'])->name('testimonialshow');
Route::post('/testimonialsave', [TestimonialController::class, 'testimonialsave'])->name('testimonialsave');
Route::get('/testimonialupdate/{testimonial}', [TestimonialController::class, 'testimonialupdate'])->name('testimonialupdate');
Route::post('/testimonialedit/{testimonial}', [TestimonialController::class, 'testimonialedit'])->name('testimonialedit');
Route::get('/testimonialdelete/{testimonial}', [TestimonialController::class, 'testimonialdelete'])->name('testimonialdelete');

/////////////////////////////////////// user ///////////////////////////
Route::get('/usershow', [UserController::class, 'usershow'])->name('usershow');
Route::post('/usersave', [UserController::class, 'usersave'])->name('usersave');
Route::get('/userupdate/{user}', [UserController::class, 'userupdate'])->name('userupdate');
Route::post('/useredit/{user}', [UserController::class, 'useredit'])->name('useredit');
Route::get('/userdelete/{user}', [UserController::class, 'userdelete'])->name('userdelete');

/////////////////////////////////////// branch ///////////////////////////
Route::get('/branchshow', [BranchController::class, 'branchshow'])->name('branchshow');
Route::post('/branchsave', [BranchController::class, 'branchsave'])->name('branchsave');
Route::get('/branchupdate/{branch}', [BranchController::class, 'branchupdate'])->name('branchupdate');
Route::post('/branchedit/{branch}', [BranchController::class, 'branchedit'])->name('branchedit');
Route::post('/branchdelete-bookings/{branch}', [BranchController::class, 'deleteBranchBookings'])->name('branchdelete.bookings');
Route::get('/branchdelete/{branch}', [BranchController::class, 'branchdelete'])->name('branchdelete');

/////////////////////////////////////// vehicle ///////////////////////////
Route::get('/vehicleshow', [VehicleController::class, 'vehicleshow'])->name('vehicleshow');
Route::post('/vehiclesave', [VehicleController::class, 'vehiclesave'])->name('vehiclesave');
Route::get('/vehicleupdate/{vehicle}', [VehicleController::class, 'vehicleupdate'])->name('vehicleupdate');
Route::post('/vehicleedit/{vehicle}', [VehicleController::class, 'vehicleedit'])->name('vehicleedit');
Route::get('/vehicledelete/{vehicle}', [VehicleController::class, 'vehicledelete'])->name('vehicledelete');

/////////////////////////////////////// discount reasons ///////////////////////////
Route::get('/discount-reasons', [DiscountReasonController::class, 'index'])->name('discount-reasons.index');
Route::post('/discount-reasons', [DiscountReasonController::class, 'store'])->name('discount-reasons.store');
Route::get('/discount-reasons/{discountReason}/edit', [DiscountReasonController::class, 'edit'])->name('discount-reasons.edit');
Route::post('/discount-reasons/{discountReason}', [DiscountReasonController::class, 'update'])->name('discount-reasons.update');
Route::get('/discount-reasons/{discountReason}/delete', [DiscountReasonController::class, 'destroy'])->name('discount-reasons.destroy');

/////////////////////////////////////// Testimonials ///////////////////////////
Route::get('/blogcategoryshow', [BlogController::class, 'blogcategoryshow'])->name('blogcategoryshow');
Route::post('/blogcategorysave', [BlogController::class, 'blogcategorysave'])->name('blogcategorysave');
Route::get('/blogcategoryupdate/{blogcategory}', [BlogController::class, 'blogcategoryupdate'])->name('blogcategoryupdate');
Route::post('/blogcategoryedit/{blogcategory}', [BlogController::class, 'blogcategoryedit'])->name('blogcategoryedit');
Route::get('/blogcategorydelete/{blogcategory}', [BlogController::class, 'blogcategorydelete'])->name('blogcategorydelete');
Route::get('/blogshow', [BlogController::class, 'blogshow'])->name('blogshow');
Route::post('/blogsave', [BlogController::class, 'blogsave'])->name('blogsave');
Route::get('/blogupdate/{blog}', [BlogController::class, 'blogupdate'])->name('blogupdate');
Route::post('/blogedit/{blog}', [BlogController::class, 'blogedit'])->name('blogedit');
Route::get('/blogdelete/{blog}', [BlogController::class, 'blogdelete'])->name('blogdelete');

/////////////////////////////////////// seo ///////////////////////////
Route::get('/seoshow', [SeoController::class, 'seoshow'])->name('seoshow');
Route::get('/seoupdate/{seo}', [SeoController::class, 'seoupdate'])->name('seoupdate');
Route::post('/seoedit/{seo}', [SeoController::class, 'seoedit'])->name('seoedit');

/////////////////////////////////////// setting ///////////////////////////
Route::get('/settingshow', [SettingController::class, 'settingshow'])->name('settingshow');
Route::post('/settingedit/{setting}', [SettingController::class, 'settingedit'])->name('settingedit');

////////////////////////////////////BOOKING
Route::get('/booking-list', [BookingController::class, 'bookingList'])->name('booking.list');
Route::get('/booking-list/export', [BookingController::class, 'exportCompletedBookings'])->name('booking.list.export');
Route::get('/pending-bookings', [BookingController::class, 'pendingBookingList'])->name('booking.pending');
Route::get('/pending-bookings/export', [BookingController::class, 'exportPendingBookings'])->name('booking.pending.export');
Route::get('/discounted-bookings', [BookingController::class, 'discountedBookingList'])->name('booking.discounted');
Route::get('/discounted-bookings/export', [BookingController::class, 'exportDiscountedBookings'])->name('booking.discounted.export');
Route::get('/payment-report', [BookingController::class, 'paymentReport'])->name('payment.report');
Route::get('/payment-report/export', [BookingController::class, 'exportPaymentReport'])->name('payment.report.export');

///////////////////////////////   user 
Route::get('/userlogin', [UserLoginController::class, 'show'])->name('userlogin')->middleware('desktop.redirect');
Route::post('/userloginprocess', [UserLoginController::class, 'checklogin'])->name('userloginprocess');
Route::get('userlogout', function () {
    Auth::logout();
    Session()->forget('userdata');
    Session()->invalidate();
    Session()->regenerateToken();

    return redirect('/userlogin');

})->name('userlogout');


////////////////////////////// web
Route::get('/index', [RideFlowController::class, 'index'])->name('index')->middleware('desktop.redirect');
Route::view('/scooter-batteries', 'theme.scooter-batteries')
    ->name('scooter.batteries')
    ->middleware('desktop.redirect');
Route::get('/book', [RideFlowController::class, 'book'])->name('book')->middleware('desktop.redirect');
Route::get('/booking-customer', [RideFlowController::class, 'customerLookup'])->name('booking-customer')->middleware('desktop.redirect');
Route::post('/send-otp', [RideFlowController::class, 'sendOtp'])->name('send-otp');
Route::post('/verify-otp', [RideFlowController::class, 'verifyOtp'])->name('verify-otp');
Route::get('/confirm-ride/{booking}', [RideFlowController::class, 'confirmRide'])->name('confirm-ride')->middleware('desktop.redirect');
Route::post('/save-booking/{booking}', [RideFlowController::class, 'saveBooking'])->name('save-booking');
Route::post('/assign-booking/{booking}', [RideFlowController::class, 'assignBooking'])->name('booking.assign');
Route::post('/assign-ride/{ride}', [RideFlowController::class, 'assignSingleRide'])->name('ride.assign.single');
Route::get('/ongoing', [RideFlowController::class, 'ongoing'])->name('ongoing')->middleware('desktop.redirect');
Route::post('/finish-booking/{booking}', [RideFlowController::class, 'finishBooking'])->name('booking.finish');
Route::post('/finish-ride-number/{booking}', [RideFlowController::class, 'finishRideByNumber'])->name('ride.finish.number');
Route::post('/finish-ride/{ride}', [RideFlowController::class, 'finishSingleRide'])->name('ride.finish.single');
Route::get('/collect-payment', [RideFlowController::class, 'collectPayment'])->name('payments.collect')->middleware('desktop.redirect');
Route::get('/collect-payment/{booking}', [RideFlowController::class, 'paymentDetail'])->name('payments.show')->middleware('desktop.redirect');
Route::post('/collect-payment/{booking}', [RideFlowController::class, 'storePayment'])->name('payments.store');
Route::get('/collected-payment', [RideFlowController::class, 'collectedPayment'])->name('payments.collected')->middleware('desktop.redirect');
Route::get('/today-payment', [RideFlowController::class, 'todayPayment'])->name('payments.today')->middleware('desktop.redirect');
Route::get('/completed-bookings', [RideFlowController::class, 'completedBookings'])->name('bookings.completed')->middleware('desktop.redirect');
