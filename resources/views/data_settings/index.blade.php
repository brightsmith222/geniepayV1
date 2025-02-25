@extends('layout')

@section('dashboard-content')

<h1>Data Top-Up Percentage Settings</h1>

    <!-- Tabs -->
    <div class="custom-tabs">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link" id="mtn-tab" data-toggle="tab" href="#mtn" role="tab" aria-controls="mtn" aria-selected="true">MTN</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="glo-tab" data-toggle="tab" href="#glo" role="tab" aria-controls="glo" aria-selected="false">Glo</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="airtel-tab" data-toggle="tab" href="#airtel" role="tab" aria-controls="airtel" aria-selected="false">Airtel</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="mobile-tab" data-toggle="tab" href="#mobile" role="tab" aria-controls="mobile" aria-selected="false">9mobile</a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="myTabContent">
            <!-- MTN Tab -->
            <div class="tab-pane fade show active" id="mtn" role="tabpanel" aria-labelledby="mtn-tab">
                <div class="tab-inner-content">
                    <h5>MTN Percentage</h5>
                    <div class="current-percentage">
                        <span>Current Percentage:</span>
                        <strong>10%</strong>
                    </div>
                    <form>
                        <div class="form-group">
                            <label for="mtnPercentage">Enter MTN Percentage:</label>
                            <input type="number" class="form-control" id="mtnPercentage" placeholder="e.g., 10">
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>

            <!-- Glo Tab -->
            <div class="tab-pane fade" id="glo" role="tabpanel" aria-labelledby="glo-tab">
                <div class="tab-inner-content">
                    <h5>Glo Percentage</h5>
                    <div class="current-percentage">
                        <span>Current Percentage:</span>
                        <strong>12%</strong>
                    </div>
                    <form>
                        <div class="form-group">
                            <label for="gloPercentage">Enter Glo Percentage:</label>
                            <input type="number" class="form-control" id="gloPercentage" placeholder="e.g., 10">
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>

            <!-- Airtel Tab -->
            <div class="tab-pane fade" id="airtel" role="tabpanel" aria-labelledby="airtel-tab">
                <div class="tab-inner-content">
                    <h5>Airtel Percentage</h5>
                    <div class="current-percentage">
                        <span>Current Percentage:</span>
                        <strong>8%</strong>
                    </div>
                    <form>
                        <div class="form-group">
                            <label for="airtelPercentage">Enter Airtel Percentage:</label>
                            <input type="number" class="form-control" id="airtelPercentage" placeholder="e.g., 10">
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>

            <!-- 9mobile Tab -->
            <div class="tab-pane fade" id="mobile" role="tabpanel" aria-labelledby="mobile-tab">
                <div class="tab-inner-content">
                    <h5>9mobile Percentage</h5>
                    <div class="current-percentage">
                        <span>Current Percentage:</span>
                        <strong>15%</strong>
                    </div>
                    <form>
                        <div class="form-group">
                            <label for="9mobilePercentage">Enter 9mobile Percentage:</label>
                            <input type="number" class="form-control" id="9mobilePercentage" placeholder="e.g., 10">
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@stop