<?php

namespace App\Services;

use App\Models\Beneficiary;
use Illuminate\Support\Facades\Log;

class BeneficiaryService
{
    public function save(array $data, $user): Beneficiary
    {
        if (empty($data['identifier'])) {
            throw new \InvalidArgumentException('Beneficiary identifier is required');
        }
    
        $beneficiary = Beneficiary::updateOrCreate(
            [
                'user_id'    => $user->id,
                'identifier' => $data['identifier'],
                'type'       => $data['type'],
            ],
            [
                'label'      => $data['label'] ?? null,
                'provider'   => $data['provider'] ?? null,
                'extra'      => $data['extra'] ?? null,
            ]
        );
        
        return $beneficiary;
    }

    public function getAllForUser($user)
{
    return Beneficiary::where('user_id', $user->id)->get();
}

    public function getAll($user, ?string $type = null)
    {
        return Beneficiary::query()
            ->where('user_id', $user->id)
            ->when($type, fn($q) => $q->where('type', $type))
            ->orderByDesc('updated_at')
            ->get();
    }

    public function getByTypeAndProvider($user, string $type, $provider)
{
    return Beneficiary::query()
        ->where('user_id', $user->id)
        ->where('type', $type)
        ->where('provider', $provider)
        ->orderByDesc('updated_at')
        ->get();
}



    public function delete($user, int $beneficiaryId)
    {
        return Beneficiary::where('user_id', $user->id)->where('id', $beneficiaryId)->delete();
    }
}

