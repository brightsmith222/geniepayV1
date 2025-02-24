<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\MyFunctions;

class ElectricityController extends Controller
{
    

    public function getElectricityProviders()
    {

        try {


            $url = "https://vtpass.com/api/services?identifier=electricity-bill";

            $headers = [
                'api-key' => '6f8493837a1d4b0e5715fd72849cb087', //$webconfig['VTPASS_API_KEY'],
                'secret-key' => 'SK_925ad054b329478d807b776ce071ed7e01d7c903914', //$webconfig['VTPASS_SK'],
                'public-key' => 'PK_42554e477a0c32098989c8a7240f66381b9ca6e1f3a',
                'Content-Type' => 'application/json',
            ];

            $response = Http::withHeaders($headers)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                // Log::info("An error occurred: " . $data);

                if ($data['response_description'] == '000') {
                    return response()->json(
                        [
                            'status' => true,
                            'data' => $data['content'] ?? []
                        ],
                        200
                    );
                }

            } else {
                // Log::error("Error Occured: " . $data['message']);
                return response()->json([
                    'status' => false,
                    'message' => 'Could not fetch data'
                ], $response->status());
            }
        } catch (RequestException $e) {
            // Handle exceptions that occur during the HTTP request
            Log::error("Request failed: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            // Handle any other exceptions
            Log::error("An error occurred: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }

    }

}
