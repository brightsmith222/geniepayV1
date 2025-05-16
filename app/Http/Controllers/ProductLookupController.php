<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProductLookupController extends Controller
{
    protected $operators = [
        
        
    ];

    protected $productTypes = [
        
    ];

    protected $productCategories = [
        
    ];

    protected function fetchOperatorsFromApi()
{
    $salt = Str::random(40);
    $passwordHash = hash('sha512', $salt . sha1(config('api.artx.password')));

    try {
        $response = Http::withoutVerifying()
            ->timeout(30)
            ->post(config('api.artx.base_url'), [
                'auth' => [
                    'username' => config('api.artx.username'),
                    'salt' => $salt,
                    'password' => $passwordHash,
                ],
                'version' => 5,
                'command' => 'getOperators',
                //'country' => 'NG' // or leave out for all countries
            ]);

        if ($response->successful()) {
            $data = $response->json()['result'] ?? [];
            $operators = [];
            foreach ($data as $opId => $details) {
                $operators[$opId] = $details['name'];
            }
            return $operators;
        }

        Log::error('ARTX getOperators failed', ['body' => $response->body()]);
    } catch (\Exception $e) {
        Log::error('ARTX getOperators exception', ['message' => $e->getMessage()]);
    }

    return [];
}


public function showForm(Request $request)
{
    $this->operators = $this->fetchOperatorsFromApi();

    return view('artx.product-explorer', [
        'operators' => $this->operators,
        'selectedOperator' => $request->operator_id,
        'products' => $request->session()->get('products', []),
        'categories' => $request->session()->get('categories', [])
    ]);
}

    public function getOperatorProducts(Request $request)
{
    $request->validate(['operator_id' => 'required|numeric']);

    $products = $this->fetchProductsFromApi($request->operator_id);

    if (empty($products)) {
        return redirect()->route('product.explorer')
            ->withInput()
            ->withErrors(['error' => 'Failed to fetch products. Please try again later.']);
    }

    return redirect()->route('product.explorer')
        ->withInput()
        ->with([
            'products' => $products,
            'categories' => $this->extractCategories($products),
            'operator_id' => $request->operator_id,
            'selectedOperator' => $request->operator_id
        ]);
}

    protected function fetchProductsFromApi($operatorId)
{
    $salt = Str::random(40);
    $passwordHash = hash('sha512', $salt.sha1(config('api.artx.password')));

    try {
        $response = Http::withoutVerifying()
            ->timeout(30)
            ->post(config('api.artx.base_url'), [
                'auth' => [
                    'username' => config('api.artx.username'),
                    'salt' => $salt,
                    'password' => $passwordHash,
                ],
                'version' => 5,
                'command' => 'getOperatorProducts',
                'operator' => $operatorId
            ]);

        // Log the response for debugging
        Log::info('ARTX API Response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        // Check for successful response
        if ($response->successful()) {
            return $response->json()['result']['products'] ?? [];
        }

        // Handle non-successful responses
        Log::error('ARTX API Error', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return [];
    } catch (\Exception $e) {
        // Log the exception
        Log::error('ARTX API Exception', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return [];
    }
}

    protected function extractCategories($products)
    {
        $categories = [];
        foreach ($products as $product) {
            $catId = $product['productCategory']['id'];
            $categories[$catId] = $this->productCategories[$catId] ?? $catId;
        }
        return $categories;
    }

    public function getProductDetails($operatorId, $productId)
{
    // Fetch operators from the API
    $this->operators = $this->fetchOperatorsFromApi();
    Log::info('Operators Array called in', $this->operators);


    // Generate the salt and password hash
    $salt = Str::random(40);
    $passwordHash = hash('sha512', $salt.sha1(config('api.artx.password')));

    // Make the API request to fetch product details
    $response = Http::withoutVerifying()
        ->timeout(30)
        ->post(config('api.artx.base_url'), [
            'auth' => [
                'username' => config('api.artx.username'),
                'salt' => $salt,
                'password' => $passwordHash,
            ],
            'version' => 5,
            'command' => 'getProduct',
            'operator' => $operatorId,
            'productId' => $productId
        ]);

    // Pass the product and operator data to the view
    return view('artx.product-details', [
        'product' => $response->json()['result']['product'] ?? null,
        'operator' => $this->operators[$operatorId] ?? 'Unknown'
    ]);
    Log::info('Operator ID in Product Details', ['operatorId' => $operatorId]);
Log::info('Operator Name', ['operatorName' => $this->operators[$operatorId] ?? 'Unknown']);
}
}