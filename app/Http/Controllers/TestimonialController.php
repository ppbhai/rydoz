<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Testimonial;

class TestimonialController extends Controller
{
    public function testimonialshow()
    {
        $testimonial = Testimonial::all();
        return view('testimonial')->with('testimonial', $testimonial);
    }

    public function testimonialsave(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'review' => 'required',
            'rating' => 'required',
            'position' => 'required',
        ]);

        $testimonial = new Testimonial();
        $testimonial->name = $request->name;
        $testimonial->review = $request->review;
        $testimonial->rating = $request->rating;
        $testimonial->position = $request->position;


        $testimonial->save();

        return redirect()->route('testimonialshow')->with('success', 'Testimonial added successfully');
    }

    public function testimonialupdate(Testimonial $testimonial)
    {
        $testimonial = Testimonial::find($testimonial->id);
        return view('testimonialupdate')->with('testimonial', $testimonial);
    }

    public function testimonialedit(Request $request, Testimonial $testimonial)
    {


        $testimonial->name = $request->name;
        $testimonial->review = $request->review;
        $testimonial->rating = $request->rating;
        $testimonial->position = $request->position;


        $testimonial->save();

        return redirect()->route('testimonialshow')->with('success', 'Testimonial updated successfully');
    }

    public function testimonialdelete(Testimonial $testimonial)
    {
        Testimonial::find($testimonial->id)->delete();
        return redirect()->route('testimonialshow')->with('success', 'Testimonial Deleted Successfully');
    }
}
