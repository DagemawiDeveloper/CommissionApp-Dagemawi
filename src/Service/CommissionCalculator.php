<?php

namespace CommissionApp\Service;

use CommissionApp\Model\Operation;

/**
 * Class CommissionCalculator
 * 
 * Handles the calculation of commission fees for deposit and withdrawal operations
 * based on user type (private or business) and transaction currency.
 */
class CommissionCalculator
{
    /**
     * @var CurrencyConverter
     */
    private $currencyConverter;

    /**
     * @var array
     */
    private $privateWithdrawals;

    /**
     * CommissionCalculator constructor.
     * 
     * @param CurrencyConverter $currencyConverter An instance of CurrencyConverter for currency conversions.
     */
    public function __construct(CurrencyConverter $currencyConverter)
    {
        $this->currencyConverter = $currencyConverter;
        $this->privateWithdrawals = [];
    }

    /**
     * Calculates the commission fee based on the operation type and user type.
     * 
     * @param Operation $operation The operation to be processed.
     * @return float The calculated commission fee.
     */
    public function calculate(Operation $operation)
    {
        // Determine the type of operation and user, then delegate to the appropriate calculation method.
        if ($operation->getUserType() === 'private') {
            if ($operation->getOperationType() === 'withdraw') {
                return $this->calculatePrivateWithdraw($operation);
            } elseif ($operation->getOperationType() === 'deposit') {
                return $this->calculateDeposit($operation);
            }
        } elseif ($operation->getUserType() === 'business') {
            if ($operation->getOperationType() === 'withdraw') {
                return $this->calculateBusinessWithdraw($operation);
            } elseif ($operation->getOperationType() === 'deposit') {
                return $this->calculateDeposit($operation);
            }
        }

        // Return 0 if operation type or user type is unknown.
        return 0;
    }

    /**
     * Calculates the commission fee for private withdrawals.
     * 
     * @param Operation $operation The withdrawal operation details.
     * @return float The calculated commission fee.
     */
    private function calculatePrivateWithdraw(Operation $operation)
    {
        $userId = $operation->getUserId();
        $amount = $operation->getAmount();
        $currency = $operation->getCurrency();
        $date = $operation->getDate();

        // Convert amount to EUR if it is in a different currency.
        if ($currency !== 'EUR') {
            $amount = $this->currencyConverter->convert($amount, $currency, 'EUR');
        }

        // Initialize user data if it does not exist.
        if (!isset($this->privateWithdrawals[$userId])) {
            $this->privateWithdrawals[$userId] = [
                'totalAmount' => 0,
                'operationCount' => 0,
                'weekStart' => (new \DateTime($date))->modify('monday this week')->format('Y-m-d')
            ];
        }

        $userData = $this->privateWithdrawals[$userId];

        // Check if the operation is within a new week.
        $currentWeekStart = (new \DateTime($date))->modify('monday this week')->format('Y-m-d');
        if ($userData['weekStart'] !== $currentWeekStart) {
            // Reset user data for the new week.
            $userData['totalAmount'] = 0;
            $userData['operationCount'] = 0;
            $userData['weekStart'] = $currentWeekStart;
        }

        // Determine the commissionable amount based on free withdrawal limits.
        $commissionableAmount = 0;
        if ($userData['operationCount'] < 3 && ($userData['totalAmount'] + $amount) <= 1000) {
            // The withdrawal is within the free limit.
            $userData['totalAmount'] += $amount;
        } else {
            // Calculate the commissionable amount for the part exceeding the free limit.
            $freeLimit = 1000;
            if ($userData['totalAmount'] < $freeLimit) {
                $remainingFreeLimit = $freeLimit - $userData['totalAmount'];
                if ($amount > $remainingFreeLimit) {
                    $commissionableAmount = $amount - $remainingFreeLimit;
                }
                $userData['totalAmount'] = $freeLimit;
            } else {
                $commissionableAmount = $amount;
            }
            $userData['totalAmount'] += $amount;
            $userData['operationCount']++;
        }

        $this->privateWithdrawals[$userId] = $userData;

        // Convert commissionable amount back to the original currency if needed.
        if ($currency !== 'EUR') {
            $commissionableAmount = $this->currencyConverter->convert($commissionableAmount, 'EUR', $currency);
        }

        // Return the rounded commission fee (0.3% of the commissionable amount).
        return round($commissionableAmount * 0.003, 2);
    }

    /**
     * Calculates the commission fee for business withdrawals.
     * 
     * @param Operation $operation The withdrawal operation details.
     * @return float The calculated commission fee.
     */
    private function calculateBusinessWithdraw(Operation $operation)
    {
        // Calculate and return the commission fee (0.5% of the withdrawal amount).
        return round($operation->getAmount() * 0.005, 2);
    }

    /**
     * Calculates the commission fee for deposits.
     * 
     * @param Operation $operation The deposit operation details.
     * @return float The calculated commission fee.
     */
    private function calculateDeposit(Operation $operation)
    {
        // Calculate and return the commission fee (0.03% of the deposit amount).
        return round($operation->getAmount() * 0.0003, 2);
    }
}