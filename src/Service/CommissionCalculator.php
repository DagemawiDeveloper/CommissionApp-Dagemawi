<?php

declare(strict_types=1);

namespace CommissionApp\Service;

use CommissionApp\Model\Operation;
use LogicException;

/**
 * Applies commission policies to validated operations.
 */
final class CommissionCalculator
{
    private const DEPOSIT_RATE = 0.0003;
    private const BUSINESS_WITHDRAW_RATE = 0.005;
    private const PRIVATE_WITHDRAW_RATE = 0.003;
    private const PRIVATE_FREE_OPERATIONS_PER_WEEK = 3;
    private const PRIVATE_FREE_AMOUNT_EUR_PER_WEEK = 1000.0;

    /**
     * Weekly state keyed by user ID and ISO week-year.
     *
     * @var array<int, array<string, array{totalAmountEur:float,operationCount:int}>>
     */
    private array $privateWithdrawals = [];

    public function __construct(private readonly CurrencyConverter $currencyConverter)
    {
    }

    public function calculate(Operation $operation): float
    {
        if ($operation->getOperationType() === 'deposit') {
            return $this->calculateDeposit($operation);
        }

        return match ($operation->getUserType()) {
            'private' => $this->calculatePrivateWithdraw($operation),
            'business' => $this->calculateBusinessWithdraw($operation),
            default => throw new LogicException('Validated operation contains an unsupported user type.'),
        };
    }

    private function calculatePrivateWithdraw(Operation $operation): float
    {
        $userId = $operation->getUserId();
        $weekKey = $operation->getIsoWeekKey();
        $currency = $operation->getCurrency();
        $amount = $operation->getAmount();
        $amountEur = $currency === 'EUR'
            ? $amount
            : $this->currencyConverter->convert($amount, $currency, 'EUR');

        $state = $this->privateWithdrawals[$userId][$weekKey] ?? [
            'totalAmountEur' => 0.0,
            'operationCount' => 0,
        ];

        $remainingFreeAmountEur = max(
            0.0,
            self::PRIVATE_FREE_AMOUNT_EUR_PER_WEEK - $state['totalAmountEur']
        );
        $freeAmountEur = $state['operationCount'] < self::PRIVATE_FREE_OPERATIONS_PER_WEEK
            ? min($amountEur, $remainingFreeAmountEur)
            : 0.0;
        $commissionableAmountEur = max(0.0, $amountEur - $freeAmountEur);

        $state['operationCount']++;
        $state['totalAmountEur'] += $amountEur;
        $this->privateWithdrawals[$userId][$weekKey] = $state;

        $commissionableAmount = $currency === 'EUR'
            ? $commissionableAmountEur
            : $this->currencyConverter->convert($commissionableAmountEur, 'EUR', $currency);

        return $this->fee($commissionableAmount, self::PRIVATE_WITHDRAW_RATE);
    }

    private function calculateBusinessWithdraw(Operation $operation): float
    {
        return $this->fee($operation->getAmount(), self::BUSINESS_WITHDRAW_RATE);
    }

    private function calculateDeposit(Operation $operation): float
    {
        return $this->fee($operation->getAmount(), self::DEPOSIT_RATE);
    }

    private function fee(float $amount, float $rate): float
    {
        return round($amount * $rate, 2, PHP_ROUND_HALF_UP);
    }
}
