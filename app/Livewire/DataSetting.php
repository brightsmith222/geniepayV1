<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AirtimeTopupPercentage;
use App\Models\User;

class DataSetting extends Component
{
    public $percentages = []; // Store network percentages
    public $networkNames = ['MTN', 'Glo', 'Airtel', '9mobile']; // Networks

    public function mount()
    {
        // Load existing percentages from the database
        foreach ($this->networkNames as $network) {
            $record = AirtimeTopupPercentage::where('network_name', $network)->first();
            $this->percentages[$network] = $record ? $record->network_percentage : 0;
        }
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
            ['network_percentage' => $this->percentages[$network]]
        );

        session()->flash('message', "$network percentage updated successfully!");
    }

    public function render()
    {
        return view('livewire.data-setting');
    }
}
