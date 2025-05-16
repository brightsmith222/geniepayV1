<?php
namespace App\Services;

interface EsimServiceInterface
{
    public function isEnabled(): bool;
    public function supportsStatusCheck(): bool;
    public function getCountries(): array;
    public function getPlans(string $countryCode): array;
    public function purchaseEsim(array $requestData): array;
    public function checkTransactionStatus(string $transactionId): array;
}
