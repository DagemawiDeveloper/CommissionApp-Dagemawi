<?php

namespace CommissionApp\Service;

use CommissionApp\Model\Operation;
use DateTime;

/**
 * Applies commission policies to deposit and withdrawal operations.
 */
class CommissionCalculator
{
    private const DEPOSIT_RATE = 0.0003;
    private const BUSINESS_WITHDRAW_RATE = 0.005;
    private const PRIVATE_WITHDRAW_RATE = 0.003;
    private const PRIVATE_FREE_OPERATIONS_PER_WEEK = 3;
    private const PRIVATE_FREE_AMOUNT_EUR_PER_WEEK = 1000.0;

    private CurrencyConverter $currencyConverter;

    /**
     * Weekly private-withdrawal state keyed by user ID.
     *
     * @var array<int|string, array{weekStart:string,totalAmountEur:float,operationCount:int}>
     */
    private array $privateWithdrawals = [];

    public function __construct(CurrencyConverter $currencyConverter)
    {
        $this->currencyConverter = $currencyConverter;
    }

    public function calculate(Operation $operation): float
    {
        if ($operation->getOperationType() === 'deposit') {
            return $this->calculateDeposit($operation);
        }

        if ($operation->getOperationType() !== 'withdraw') {
            return 0.0;
        }

        if ($operation->getUserType() === 'private') {
            return $this->calculatePrivateWithdraw($operation);
        }

        if ($operation->getUserType() === 'business') {
            return $this->calculateBusinessWithdraw($operation);
        }

        return 0.0;
    }

    private function calculatePrivateWithdraw(Operation $operation): float
    {
        $userId = $operation->getUserId();
        $currency = $operation->getCurrency();
        $amount = (float) $operation->getAmount();
        $amountEur = $currency === 'EUR'
            ? $amount
            : $this->currencyConverter->convert($amount, $currency, 'EUR');

        $weekStart = (new DateTime($operation->getDate()))
            ->modify('monday this week')
            ->format('Y-m-d');

        $state = $this->privateWithdrawals[$userId] ?? $this->newWeekState($weekStart);

        if ($state['weekStart'] !== $weekStart) {
            $state = $this->newWeekState($weekStart);
        }

        $remainingFreeAmountEur = max(
            0.0,
            self::PRIVATE_FREE_AMOUNT_EUR_PER_WEEK - $state['totalAmountEur']
        );

        $operationStillFree = $state['operationCount'] < self::PRIVATE_FREE_OPERATIONS_PER_WEEK;
        $freeAmountEur = $operationStillFree
            ? min($amountEur, $remainingFreeAmountEur)
            : 0.0;

        $commissionableAmountEur = max(0.0, $amountEur - $freeAmountEur);

        // Every private withdrawal consumes one of the weekly operation slots,
        // regardless of whether the amount itself was fully free.
        $state['operationCount']++;
        $state['totalAmountEur'] += $amountEur;
        $this->privateWithdrawals[$userId] = $state;

        $commissionableAmount = $currency === 'EUR'
            ? $commissionableAmountEur
            : $this->currencyConverter->convert($commissionableAmountEur, 'EUR', $currency);

        return round($commissionableAmount * self::PRIVATE_WITHDRAW_RATE, 2);
    }

    private function calculateBusinessWithdraw(Operation $operation): float
    {
        return round((float) $operation->getAmount() * self::BUSINESS_WITHDRAW_RATE, 2);
    }

    private function calculateDeposit(Operation $operation): float
    {
        return round((float) $operation->getAmount() * self::DEPOSIT_RATE, 2);
    }

    /**
     * @return array{weekStart:string,totalAmountEur:float,operationCount:int}
     */
    private function newWeekState(string $weekStart): array
    {
        return [
            'weekStart' => $weekStart,
            'totalAmountEur' => 0.0,
            'operationCount' => 0,
        ];
    }
}
