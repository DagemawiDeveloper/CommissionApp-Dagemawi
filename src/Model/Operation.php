<?php 

namespace CommissionApp\Model;

/**
 * Class Operation
 * 
 * Represents a financial operation with details such as date, user information,
 * type of operation, amount, and currency.
 */
class Operation
{
    /**
     * @var string The date of the operation in 'Y-m-d' format.
     */
    private $date;

    /**
     * @var int The unique identifier of the user performing the operation.
     */
    private $userId;

    /**
     * @var string The type of user ('private' or 'business').
     */
    private $userType;

    /**
     * @var string The type of operation ('withdraw' or 'deposit').
     */
    private $operationType;

    /**
     * @var float The amount of money involved in the operation.
     */
    private $amount;

    /**
     * @var string The currency code of the amount (e.g., 'EUR', 'USD').
     */
    private $currency;

    /**
     * Operation constructor.
     * 
     * @param string $date The date of the operation.
     * @param int $userId The user ID of the person performing the operation.
     * @param string $userType The type of user ('private' or 'business').
     * @param string $operationType The type of operation ('withdraw' or 'deposit').
     * @param float $amount The amount involved in the operation.
     * @param string $currency The currency code of the amount.
     */
    public function __construct($date, $userId, $userType, $operationType, $amount, $currency)
    {
        $this->date = $date;
        $this->userId = $userId;
        $this->userType = $userType;
        $this->operationType = $operationType;
        $this->amount = $amount;
        $this->currency = $currency;
    }

    /**
     * Gets the date of the operation.
     * 
     * @return string The date of the operation.
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Gets the user ID of the person performing the operation.
     * 
     * @return int The user ID.
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Gets the type of user ('private' or 'business').
     * 
     * @return string The user type.
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * Gets the type of operation ('withdraw' or 'deposit').
     * 
     * @return string The operation type.
     */
    public function getOperationType()
    {
        return $this->operationType;
    }

    /**
     * Gets the amount of money involved in the operation.
     * 
     * @return float The amount.
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Gets the currency code of the amount involved in the operation.
     * 
     * @return string The currency code.
     */
    public function getCurrency()
    {
        return $this->currency;
    }
}