<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SliderControllers extends Component
{
    public $slider_id;
    public $image;

    public function render()
    {
        return view('livewire.slider-controllers', [
            'sliders' => Slider::all()
        ]);
    }

    public function uploadSlider(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('sliders', 'public');

            Slider::create([
                'image' => $imagePath,
            ]);

            return response()->json(['success' => 'Slider uploaded successfully.']);
        }

        return response()->json(['error' => 'Image upload failed.'], 400);
    }
}