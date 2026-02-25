<?php

namespace App\Enums;

enum TransactionType: string
{
    case SALARY = 'salary';
    case BUSINESS_INCOME = 'business_income';
    case RENT_INCOME = 'rent_income';
    case INVESTMENT_INCOME = 'investment_income';
    case LOAN_DISBURSEMENT = 'loan_disbursement';
    case DEBT_PAYMENT = 'debt_payment';
    case RENT_EXPENSE = 'rent_expense';
    case UTILITY = 'utility';
    case GROCERY = 'grocery';
    case FUEL = 'fuel';
    case AIRTIME = 'airtime';
    case TRANSFER_OUT = 'transfer_out';
    case TRANSFER_IN = 'transfer_in';
    case WITHDRAWAL = 'withdrawal';
    case DEPOSIT = 'deposit';
    case FEE = 'fee';
    case INTEREST = 'interest';
    case GAMBLING = 'gambling';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::SALARY => 'Salary Payment',
            self::BUSINESS_INCOME => 'Business Income',
            self::RENT_INCOME => 'Rent Income',
            self::INVESTMENT_INCOME => 'Investment Income',
            self::LOAN_DISBURSEMENT => 'Loan Disbursement',
            self::DEBT_PAYMENT => 'Debt/Loan Payment',
            self::RENT_EXPENSE => 'Rent Payment',
            self::UTILITY => 'Utility Bill',
            self::GROCERY => 'Grocery/Shopping',
            self::FUEL => 'Fuel Purchase',
            self::AIRTIME => 'Airtime/Data',
            self::TRANSFER_OUT => 'Transfer Out',
            self::TRANSFER_IN => 'Transfer In',
            self::WITHDRAWAL => 'Cash Withdrawal',
            self::DEPOSIT => 'Cash Deposit',
            self::FEE => 'Bank/Service Fee',
            self::INTEREST => 'Interest Earned',
            self::GAMBLING => 'Gambling/Betting',
            self::OTHER => 'Other',
        };
    }

    public function isIncome(): bool
    {
        return in_array($this, [
            self::SALARY,
            self::BUSINESS_INCOME,
            self::RENT_INCOME,
            self::INVESTMENT_INCOME,
            self::LOAN_DISBURSEMENT,
            self::TRANSFER_IN,
            self::DEPOSIT,
            self::INTEREST,
        ]);
    }

    public function isExpense(): bool
    {
        return in_array($this, [
            self::DEBT_PAYMENT,
            self::RENT_EXPENSE,
            self::UTILITY,
            self::GROCERY,
            self::FUEL,
            self::AIRTIME,
            self::TRANSFER_OUT,
            self::WITHDRAWAL,
            self::FEE,
            self::GAMBLING,
        ]);
    }

    public function isDebtPayment(): bool
    {
        return $this === self::DEBT_PAYMENT;
    }
}
