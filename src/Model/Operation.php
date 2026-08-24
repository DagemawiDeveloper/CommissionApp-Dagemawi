<?php

declare(strict_types=1);

namespace CommissionApp\Model;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Immutable, validated financial operation.
 */
final class Operation
{
    private DateTimeImmutable $date;
    private int $userId;
    private string $userType;
    private string $operationType;
    private float $amount;
    private string $currency;

    /**
     * @param int|string $userId
     * @param int|float|string $amount
     */
    public function __construct(
        string $date,
        int|string $userId,
        string $userType,
        string $operationType,
        int|float|string $amount,
        string $currency
    ) {
        $this->date = $this->validateDate($date);
        $this->userId = $this->validateUserId($userId);
        $this->userType = $this->validateToken($userType, ['private', 'business'], 'user type');
        $this->operationType = $this->validateToken($operationType, ['withdraw', 'deposit'], 'operation type');
        $this->amount = $this->validateAmount($amount);
        $this->currency = $this->validateCurrency($currency);
    }

    public function getDate(): string
    {
        return $this->date->format('Y-m-d');
    }

    public function getIsoWeekKey(): string
    {
        return $this->date->format('o-W');
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUserType(): string
    {
        return $this->userType;
    }

    public function getOperationType(): string
    {
        return $this->operationType;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    private function validateDate(string $date): DateTimeImmutable
    {
        $date = trim($date);
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = DateTimeImmutable::getLastErrors();
        $hasErrors = is_array($errors)
            && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

        if (! $parsed || $hasErrors || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Operation date must use a valid Y-m-d value.');
        }

        return $parsed;
    }

    private function validateUserId(int|string $userId): int
    {
        $validated = filter_var(
            $userId,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($validated === false) {
            throw new InvalidArgumentException('User ID must be a positive integer.');
        }

        return (int) $validated;
    }

    /**
     * @param list<string> $allowed
     */
    private function validateToken(string $value, array $allowed, string $label): string
    {
        $value = strtolower(trim($value));

        if (! in_array($value, $allowed, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported %s "%s". Allowed values: %s.',
                $label,
                $value,
                implode(', ', $allowed)
            ));
        }

        return $value;
    }

    /**
     * @param int|float|string $amount
     */
    private function validateAmount(int|float|string $amount): float
    {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException('Operation amount must be numeric.');
        }

        $amount = (float) $amount;

        if (! is_finite($amount) || $amount <= 0) {
            throw new InvalidArgumentException('Operation amount must be finite and greater than zero.');
        }

        return $amount;
    }

    private function validateCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException('Currency must be a three-letter ISO-style code.');
        }

        return $currency;
    }
}
