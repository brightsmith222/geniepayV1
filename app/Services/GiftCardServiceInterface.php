<?php

namespace App\Services;

interface GiftCardServiceInterface
{
    public function isEnabled(): bool;
    public function getCountries(): array;
    public function getGiftCards(string $countryCode): array;
    public function getCardDenominations(string $operatorId): array;
    public function purchaseCard(array $requestData): array;
    public function checkTransactionStatus(string $transactionId): array;
    public function supportsStatusCheck(): bool;
}


