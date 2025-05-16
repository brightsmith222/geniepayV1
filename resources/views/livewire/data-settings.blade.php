<div>
    <h1>Data Top-Up Percentage Settings</h1>

    <!-- Display Flash Messages -->
    @if (session()->has('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('failed'))
        <div class="alert alert-danger">
            {{ session('failed') }}
        </div>
    @endif

    <!-- Tabs -->
    <div class="custom-tabs">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            @foreach ($networkNames as $network)
                <li class="nav-item">
                    <a class="data-nav-link nav-link {{ $activeTab === $network ? 'active' : '' }}" 
                       id="net-{{ strtolower($network) }}-tab"
                       wire:click="setActiveTab('{{ $network }}')" 
                       role="tab" 
                       aria-controls="net-{{ strtolower($network) }}" 
                       aria-selected="{{ $activeTab === $network ? 'true' : 'false' }}">
                        {{ $network }}
                    </a>
                </li>
            @endforeach
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="myTabContent">
            @foreach ($networkNames as $network)
                <div class="data-tab-pane tab-pane fade {{ $activeTab === $network ? 'show active' : '' }}" 
                     id="net-{{ strtolower(str_replace(' ', '-', $network)) }}" 
                     role="tabpanel" 
                     aria-labelledby="net-{{ strtolower(str_replace(' ', '-', $network)) }}-tab">
                    <div class="tab-inner-content">
                        <div class="d-flex" style="justify-content: space-between;">
                            <div>
                                <h5>{{ $network }} Percentage</h5>
                            </div>
                            <div class="toggle {{ $statuses[$network] === 1 ? 'active' : '' }}" 
                                 wire:click="toggleStatus('{{ $network }}')">
                                <div class="switch">
                                    <span>{{ $statuses[$network] === 1 ? 'ON' : 'OFF' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="current-percentage">
                            <span>Current Percentage:</span>
                            <strong>{{ $percentages[$network] ?? '0' }}%</strong>
                        </div>
                        <form wire:submit.prevent="updatePercentage('{{ $network }}')">
                            <div class="form-group">
                                <label for="net-{{ strtolower(str_replace(' ', '-', $network)) }}-percentage">
                                    Enter {{ $network }} Percentage:
                                </label>
                                <input type="number" class="form-control"
                                       id="net-{{ strtolower(str_replace(' ', '-', $network)) }}-percentage"
                                       wire:model="percentages.{{ $network }}"
                                       placeholder="e.g., 10">
                                @error("percentages.$network") 
                                    <span class="text-danger">{{ $message }}</span> 
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>