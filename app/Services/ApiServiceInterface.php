<?php

namespace App\Services;

interface ApiServiceInterface
{
    public function isEnabled(): bool;
    public function getHeaders(): array;
    public function processRequest(array $requestData): array;
    public function handleResponse(array $response, array $context): array;
    public function checkTransactionStatus(string $transactionId): array;
    public function supportsStatusCheck(): bool;
    public function validateNumberForNetwork(string $phoneNumber, int $network): bool;
    public function getNetworkPrefixes(): array;
}