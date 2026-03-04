<?php

namespace App\Services;

interface TransportProviderAdapterInterface
{
    public function sendHoldCreate(array $payload): array;

    public function sendHoldConfirm(array $payload): array;

    public function sendHoldRelease(array $payload): array;
}
