@extends('layout')

@section('dashboard-content')
    <div class="container py-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4>ARTX Service Explorer</h4>
            </div>

            <div class="card-body">
                <!-- Operator Selection Form -->
                <form method="POST" action="{{ route('operator.products') }}">
                    @csrf
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <label class="form-label">Select Service Provider</label>
                            <select name="operator_id" class="form-select" required>
                                <option value="">Choose Operator</option>
                                @foreach ($operators as $id => $name)
                                    <option value="{{ $id }}" {{ $selectedOperator == $id ? 'selected' : '' }}>
                                        {{ $name }} (Operator Id: {{ $id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i> Explore Services
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Products Display -->
                @if (count($products) > 0)
                    <div class="mb-4">
                        <h5>Available Services for {{ $operators[$selectedOperator] ?? 'Selected Provider' }}</h5>

                        <!-- Category Filter -->
                        @if (count($categories) > 1)
                            <div class="mb-3">
                                <select class="form-select" id="categoryFilter">
                                    <option value="">All Categories</option>
                                    @foreach ($categories as $id => $name)
                                        <option value="cat-{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Products Table -->
                        <div class="table-responsive">
                            <table class="table table-hover" id="productsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Service</th>
                                        <th>Type</th>
                                        <th>Category</th>
                                        <th class="text-end">Price</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(count(session('products', [])) > 0)
                                    @foreach(session('products') as $id => $product)
                                        <tr class="cat-{{ $product['productCategory']['id'] }}">
                                            <td>{{ $id }}</td>
                                            <td>{{ $product['name'] }}</td>
                                            <td>{{ $productTypes[$product['productType']['id']] ?? $product['productType']['name'] }}</td>
                                            <td>{{ $categories[$product['productCategory']['id']] ?? $product['productCategory']['name'] }}</td>
                                            <td class="text-end">
                                                @if($product['priceType'] == 'fixed')
                                                    ₦{{ number_format($product['price']['operator'], 2) }}
                                                @else
                                                    ₦{{ number_format($product['price']['min']['operator'], 2) }} - 
                                                    ₦{{ number_format($product['price']['max']['operator'], 2) }}
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('product.details', [
                                                    'operator_id' => session('selectedOperator'), 
                                                    'product_id' => $id
                                                ]) }}" class="btn btn-sm btn-outline-primary">
                                                    Details
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                @elseif($selectedOperator)
                    <div class="alert alert-info">
                        <p>This operator doesn't have specific products. You can proceed directly with the operator.</p>
                        <form method="POST" action="{{ route('transaction.initiate') }}">
                            @csrf
                            <input type="hidden" name="operator_id" value="{{ $selectedOperator }}">
                            <button type="submit" class="btn btn-primary">
                                Continue with {{ $operators[$selectedOperator] }}
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
@section('scripts')
    <script src="{{ asset('assets/js/explorer.js') }}"></script>
@endsection
