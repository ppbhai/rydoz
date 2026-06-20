<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use File;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function settingshow()
    {
        $setting = Setting::firstOrCreate([], [
            'scanner' => 0,
            'photo' => 0,
            'buffer_time' => 0,
        ]);

        return view('settings')->with('setting', $setting);
    }

    public function settingedit(Request $request, Setting $setting)
    {
        $setting->scanner = $request->scanner;
        $setting->photo = $request->photo;
        $setting->buffer_time = $request->buffer_time;

        $setting->save();

        return redirect()->route('settingshow')->with('success', 'Setting updated successfully');
    }
}
