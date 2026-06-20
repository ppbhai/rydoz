<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Blogcategory;
use File;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function blogcategoryshow()
    {
        $blogcat = Blogcategory::all();
        return view('blogcategory')->with('blogcategory', $blogcat);
    }



    public function blogcategorysave(Request $request)
    {
        $request->validate([
            'name' => 'required',

        ]);


        $blogcat = new Blogcategory();
        $blogcat->name = $request->name;
        $blogcat->save();

        return redirect()->route('blogcategoryshow')->with('success', 'Category Added Successfully');

    }



    public function blogcategoryupdate(Blogcategory $blogcategory)
    {
        $blogcategory = Blogcategory::find($blogcategory->id);
        return view('blogcategoryupdate')->with('blogcategory', $blogcategory);
    }

    public function blogcategoryedit(Request $request, Blogcategory $blogcategory)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $blogcategory->name = $request->name;
        $blogcategory->save();

        return redirect()->route('blogcategoryshow')->with('success', 'Category Updated Successfully');
    }

    public function blogcategorydelete(Blogcategory $blogcategory)
    {
        $blogcategory = Blogcategory::find($blogcategory->id);
        $blogcategory->delete();
        return redirect()->route('blogcategoryshow')->with('success', 'Category Deleted Successfully');

    }
    //

    // Blog Module
    public function blogshow()
    {
        $blog = Blog::all();
        $blogcategory = Blogcategory::all();
        return view('blog')->with('blogcategory', $blogcategory)->with('blog', $blog);
    }


    public function blogsave(Request $request)
    {
        $request->validate([
            'blogimg' => 'required',
            'title' => 'required',
            'description' => 'required',
        ]);

        $blog = new Blog();
        $blog->blogcatid = $request->blogcategory;
        $blog->title = $request->title;
        $blog->alt = $request->alt;
        $blog->slug = $request->slug;
        $blog->description = $request->description;
        $blog->image = $request->file('blogimg')->store('blog', 'public');
        $blog->metatitle = ($request->metatitle != null) ? $request->metatitle : "";
        $blog->metades = ($request->metadescription != null) ? $request->metadescription : "";
        $blog->metakeyword = ($request->metakeywords != null) ? $request->metakeywords : "";
        $blog->canonical = ($request->canonical != null) ? $request->canonical : "";
        $blog->save();



        return redirect()->route('blogshow')->with('success', 'Blog added successfully');

    }


    public function blogupdate(Blog $blog)
    {

        $blogcat = Blogcategory::all();
        $blog = Blog::find($blog->id);
        return view('blogupdate')->with('blog', $blog)->with('blogcategory', $blogcat);
    }

    public function blogedit(Request $request, Blog $blog)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
        ]);

        $blog->blogcatid = $request->blogcategory;
        $blog->title = $request->title;
        $blog->description = $request->description;
        $blog->alt = $request->alt;
        $blog->slug = $request->slug;

        if ($request->hasFile('blogimg')) {
            if ($blog->image && File::exists(public_path('storage/' . $blog->image))) {
                File::delete(public_path('storage/' . $blog->image));
            }

            $blog->image = $request->file('blogimg')->store('blog', 'public');
        }

        $blog->metatitle = $request->metatitle ?? '';
        $blog->metades = $request->metadescription ?? '';
        $blog->metakeyword = $request->metakeywords ?? '';
        $blog->canonical = $request->canonical ?? '';

        $blog->save();

        return redirect()->route('blogshow')->with('success', 'Blog updated successfully.');
    }

    public function blogdelete(Blog $blog)
    {
        $blog = Blog::find($blog->id);
        File::delete('storage/' . $blog->image);
        $blog->delete();
        return redirect()->route('blogshow')->with('success', 'Blog Deleted successfully');

    }
}
