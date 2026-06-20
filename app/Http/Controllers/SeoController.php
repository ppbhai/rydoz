<?php

namespace App\Http\Controllers;

use App\Models\Seo;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    public function seoshow()
    {
        $seo = Seo::all();
        return view('seo')->with('seo', $seo);
    }

    public function seoupdate(Seo $seo)
    {
        $seo = Seo::find($seo->id);
        return view('seoupdate')->with('seo', $seo);
    }

    public function seoedit(Request $request, Seo $seo)
    {


        if ($request->title != null) {
            $seo->title = $request->title;
        } else {
            $seo->title = "";
        }
        if ($request->des != null) {
            $seo->des = $request->des;
        } else {
            $seo->des = "";
        }
        if ($request->keywords != null) {
            $seo->keywords = $request->keywords;
        } else {
            $seo->keywords = "";
        }
        if ($request->canonical != null) {
            $seo->canonical = $request->canonical;
        } else {
            $seo->canonical = "";
        }

        $seo->save();

        return redirect()->route('seoshow')->with('success', 'SEO Updated successfully');
    }


}
