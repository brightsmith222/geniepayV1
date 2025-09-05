<?php

namespace App;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;


class MyFunctions
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public static function generateRequestId()
    {
        // Set the timezone to Lagos
        $lagosTimezone = 'Africa/Lagos';

        // Get the current time in Lagos timezone
        $currentTime = Carbon::now($lagosTimezone);

        // Format the current time as 'YmdHis' (YearMonthDayHourMinuteSecond)
        $todayNumeric = $currentTime->format('YmdHis');

        // Define the desired length of random characters
        $desiredLength = 3;

        // Generate random characters (letters and digits)
        $randomChars = substr(bin2hex(random_bytes($desiredLength)), 0, $desiredLength);

        // Concatenate the numeric date and random characters to form the request ID
        $requestId = $todayNumeric . $randomChars;

        return $requestId;
    }




    public static function reserveAccount($email, $customerName, $accountName)
    {

        try {
            $accessToken = MyFunctions::monnifyAuth();
            if($accessToken == false){
                return false;
            }
            $url = "https://sandbox.monnify.com/api/v2/bank-transfer/reserved-accounts";

            $payload = [

                "accountReference" => "Geniepay|" . MyFunctions::generateRequestId(),
                "accountName" => $accountName,
                "currencyCode" => "NGN",
                "contractCode" => "3466853259",
                "customerEmail" => $email,
                "customerName" => $customerName,
                "getAllAvailableBanks" => true

            ];

            $headers = [

                "Content-Type" => "application/json",
                "Authorization" => "Bearer " . $accessToken

            ];

            // Send POST request to Monnify API for authentication
            $response = Http::withHeaders($headers)->post($url, $payload);

            // Check if the response status is 200
            Log::info("Monnify Auth response: " . $response);
            if ($response->successful()) {
                // Get the access token from the response
                $data = $response->json();
                $accountReference = $data['responseBody']['accountReference'];
                return $accountReference;
            } else {
                Log::error("Request for monnify Auth failed");
                return false;
            }

        } catch (RequestException $e) {

            // Handle exceptions that occur during the HTTP request
            Log::error("Request for monnify reserve accoint failed" . $e->getMessage());
            return false;
        } catch (\Exception $e) {

            Log::error("Request for monnify reserve account general error: " . $e->getMessage());
            return false;
        }


    }





    public static function monnifyAuth()
    {
        try {
            // Get the website configuration values
            $webconfig = config('website'); // Assuming you have website configuration stored in a config file

            $accessToken = null;

            // Prepare headers
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode('MK_TEST_ZPC8SNRUV5'. ':' . 'X9FV8PP9R0W4MYP259690KK77UM6RME5'),
            ];

            // Send POST request to Monnify API for authentication
            $response = Http::withHeaders($headers)->post('https://sandbox.monnify.com/api/v1/auth/login');

            // Check if the response status is 200
            if ($response->successful()) {
                // Get the access token from the response
                $data = $response->json();
                $accessToken = $data['responseBody']['accessToken'];
            } else {
                Log::error("Request for monnify Auth failed");
                return false;
            }

            return $accessToken;
        } catch (RequestException $e) {

            // Handle exceptions that occur during the HTTP request
            Log::error("Request for monnify Auth failed" . $e->getMessage());
            return false;
        } catch (\Exception $e) {

            Log::error("Request for general error: " . $e->getMessage());
            return false;
        }

    }



}
