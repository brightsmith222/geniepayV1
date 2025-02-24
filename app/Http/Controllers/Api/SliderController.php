<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Slider;

class SliderController extends Controller
{
    
    public function index()
    {
        try {
            $sliders = Slider::all();
            return response()->json(

                [
                    'success' => true,
                    'data' => $sliders
                ], 200
            );
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'success' => false,
                    'message' => throw $th
                ], 500
            );
        }
    }

}
