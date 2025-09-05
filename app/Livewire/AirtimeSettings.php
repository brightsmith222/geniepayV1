<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AirtimeTopupPercentage;
use App\Models\Service;

class AirtimeSettings extends Component
{
    public $percentages = []; // Store network percentages
    public $statuses = []; // Store network status (for percentage charge)
    public $activeStatuses = []; // Store network active status (for service availability)
    public $networkNames = ['MTN', 'Glo', 'Airtel', '9mobile', 'international']; // Networks
    public $activeTab = 'MTN'; // Track the active tab

    public function mount()
    {
        // Load existing percentages, status, and active status from the database
        foreach ($this->networkNames as $network) {
            $record = AirtimeTopupPercentage::where('network_name', $network)->first();
            $this->percentages[$network] = $record ? $record->network_percentage : 0;
            $this->statuses[$network] = $record ? (int) $record->status : 0; // For percentage charge
            
            // Load service availability from Services table
            $this->activeStatuses[$network] = Service::getServiceStatus('airtime', $network) ? 1 : 0;
        }

        // Set the initial active tab
        //$this->activeTab = $this->networkNames[0]; // Default to the first tab
    }

    public function setActiveTab($network)
    {
        $this->activeTab = $network; // Update the active tab
    }

    public function updatePercentage($network)
    {
        // Validate input
        $this->validate([
            "percentages.$network" => 'required|numeric|min:0|max:100',
        ]);

        // Update or create record
        AirtimeTopupPercentage::updateOrCreate(
            ['network_name' => $network],
            [
                'network_percentage' => $this->percentages[$network],
                'status' => $this->statuses[$network], // For percentage charge
            ]
        );
        
        // Update service availability in Services table
        Service::setServiceStatus('airtime', $network, $this->activeStatuses[$network] === 1);

        flash()->success("$network percentage updated successfully!");
    }

    public function toggleStatus($network)
{
    // Toggle the status between 1 (active) and 0 (disabled) - for percentage charge
    $this->statuses[$network] = $this->statuses[$network] === 1 ? 0 : 1;

    // Update the database
    AirtimeTopupPercentage::updateOrCreate(
        ['network_name' => $network],
        ['status' => $this->statuses[$network]]
    );

    // Flash a success message
    $statusText = $this->statuses[$network] === 1 ? 'ON' : 'OFF';
    flash()->success("$network percentage charge status updated to $statusText!");
}

    public function toggleActiveStatus($network)
{
    // Toggle the active status between 1 (active) and 0 (disabled) - for service availability
    $this->activeStatuses[$network] = $this->activeStatuses[$network] === 1 ? 0 : 1;

    // Update the Services table
    Service::setServiceStatus('airtime', $network, $this->activeStatuses[$network] === 1);

    // Flash a success message
    $statusText = $this->activeStatuses[$network] === 1 ? 'ON' : 'OFF';
    flash()->success("$network service availability updated to $statusText!");
}

    public function render()
    {
        return view('livewire.airtime-settings');
    }
}
