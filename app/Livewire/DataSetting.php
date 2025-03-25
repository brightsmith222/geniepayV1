<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AirtimeTopupPercentage;
use App\Models\User;

class DataSetting extends Component
{
    public $percentages = []; // Store network percentages
    public $statuses = []; // Store network statuses
    public $networkNames = ['MTN', 'Glo', 'Airtel', '9mobile']; // Networks
    public $activeTab = 'MTN'; // Track the active tab

    public function mount()
    {
        // Load existing percentages and statuses from the database
        foreach ($this->networkNames as $network) {
            $record = AirtimeTopupPercentage::where('network_name', $network)->first();
            $this->percentages[$network] = $record ? $record->network_percentage : 0;
            $this->statuses[$network] = $record ? $record->status : 'disabled'; // Default to 'disabled'
        }

        // Set the initial active tab
        $this->activeTab = $this->networkNames[0]; // Default to the first tab
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
                'status' => $this->statuses[$network], // Ensure status is updated
            ]
        );

        session()->flash('success', "$network percentage updated successfully!");
    }

    public function toggleStatus($network)
    {
        // Toggle the status between 'active' and 'disabled'
        $this->statuses[$network] = $this->statuses[$network] === 'active' ? 'disabled' : 'active';

        // Update the database
        AirtimeTopupPercentage::updateOrCreate(
            ['network_name' => $network],
            ['status' => $this->statuses[$network]]
        );

        // Flash a success message
        session()->flash('success', "$network status updated to {$this->statuses[$network]}!");
    }
}