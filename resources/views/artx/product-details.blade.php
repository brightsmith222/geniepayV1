@extends('layout')

@section('dashboard-content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Product Details</h4>
                </div>

                <div class="card-body">
                    <div class="mb-4">
                        <a href="{{ route('product.explorer') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Search
                        </a>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Basic Information</h5>
                                </div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-4">Product ID:</dt>
                                        <dd class="col-sm-8">{{ $product['id'] ?? 'N/A' }}</dd>

                                        <dt class="col-sm-4">Name:</dt>
                                        <dd class="col-sm-8">{{ $product['name'] ?? 'N/A' }}</dd>

                                        <dt class="col-sm-4">Operator:</dt>
                                        <dd class="col-sm-8">{{ $operator['name'] ?? 'N/A' }}</dd>

                                        <dt class="col-sm-4">Type:</dt>
                                        <dd class="col-sm-8">{{ $product['productType']['name'] ?? 'N/A' }}</dd>

                                        <dt class="col-sm-4">Category:</dt>
                                        <dd class="col-sm-8">{{ $product['productCategory']['name'] ?? 'N/A' }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Pricing Information</h5>
                                </div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-4">Price Type:</dt>
                                        <dd class="col-sm-8">{{ ucfirst($product['priceType'] ?? 'N/A') }}</dd>

                                        <dt class="col-sm-4">Operator Price:</dt>
                                        <dd class="col-sm-8">₦{{ number_format($product['price']['operator'] ?? 0, 2) }}</dd>

                                        <dt class="col-sm-4">User Price:</dt>
                                        <dd class="col-sm-8">₦{{ number_format($product['price']['user'] ?? 0, 2) }}</dd>

                                        <dt class="col-sm-4">Currency:</dt>
                                        <dd class="col-sm-8">{{ $currency['operator'] ?? 'NGN' }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(isset($product['extraParameters']))
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Extra Parameters</h5>
                            </div>
                            <div class="card-body">
                                <pre class="bg-light p-3 rounded">{{ json_encode($product['extraParameters'], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection