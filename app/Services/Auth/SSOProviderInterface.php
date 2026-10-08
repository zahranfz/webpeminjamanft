<?php
namespace App\Services\Auth;

interface SSOProviderInterface {
    public function getLoginUrl(): string;
    public function authenticate(array $params): array;
}
