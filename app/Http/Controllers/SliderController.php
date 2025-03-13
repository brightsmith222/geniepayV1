<?php

namespace App\Http\Controllers;

use App\Models\Slider;
use Illuminate\Http\Request;

class SliderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        $sliders = Slider::paginate(3); 
        return view('slider.index', compact('sliders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('slider.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $slider_title = $request->input('slider_title');
        $slider_message = $request->input('slider_message');
        $image = $request->file('image');

        if (
            $image != null
        ) {

            $model = new Slider();
            $model->slider_title = $slider_title;
            $slider_message = html_entity_decode($slider_message);
            $slider_message = strip_tags($slider_message);
            $model->slider_message = $slider_message;

            $ext = $image->getClientOriginalExtension();
            $fileName = rand(10000, 50000) . '.' . $ext;
            if ($ext == 'jpg' || $ext == 'png') {
                if ($image->move(public_path(), $fileName)) {

                    $model->image = url('/') . '/' . $fileName;
                } else {
                    return redirect()->back()->with('failed', 'failed to upload, please check your internet');
                }
            } else {
                return redirect()->back()->with('failed', 'Please upload png or jpg/jpeg');
            }

            if ($model->save()) {
                return redirect()->back()->with('success', 'Slider  saved successfully!');
            }
            return redirect()->back()->with('failed', 'Slider could not be saved!');
        }
        return redirect()->back()->with('failed', 'Please fill all the compulsory fields!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Slider $slider)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $slider = Slider::find($id);
        return view('slider.edit', compact('slider'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $slider_title = $request->input('slider_title');
        $slider_message = $request->input('slider_message');
        $image = $request->file('image');

        // if (
        //     $image != null
        // ) {

            $model = Slider::find($id);
            $model->slider_title = $slider_title;

            $slider_message = html_entity_decode($slider_message);
            $slider_message = strip_tags($slider_message);
            $model->slider_message = $slider_message;

            if ($image != null) {

                $ext = $image->getClientOriginalExtension();
                $fileName = rand(10000, 50000) . '.' . $ext;
                if ($ext == 'jpg' || $ext == 'png') {
                    if ($image->move(public_path(), $fileName)) {

                        $model->image = url('/') . '/' . $fileName;
                    } else {
                        return redirect()->back()->with('failed', 'failed to upload, please check your internet');
                    }
                } else {
                    return redirect()->back()->with('failed', 'Please upload png or jpg/jpeg');
                }
            } else {

                $model->image = $request->input('image_update');
            }


            if ($model->save()) {
                return redirect()->back()->with('success', 'Slider  updated successfully!');
            }
            return redirect()->back()->with('failed', 'Slider could not be updated!');
        // }
        // return redirect()->back()->with('failed', 'Please fill all the compulsory fields!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Find the slider by ID
        $slider = Slider::find($id);
    
        // Check if the slider exists
        if ($slider) {
            // Delete the slider
            $slider->delete();
    
            // Redirect with success message
            return redirect()->back()->with('deleted', 'Slider deleted successfully!');
        }
    
        // If the slider does not exist, redirect with error message
        return redirect()->back()->with('delete-failed', 'Slider not found!');
    }
}
