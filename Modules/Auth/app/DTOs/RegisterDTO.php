<?php

namespace Modules\Auth\DTOs;

final class RegisterDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
    ) {}

    public static function fromRequest(array $data): static
    {
        return new self(
            name: $data['name'] ?? '',
            username: $data['username'] ?? '',
            email: $data['email'] ?? '',
            password: $data['password'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
