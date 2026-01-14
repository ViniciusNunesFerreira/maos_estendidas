<?php
// app/Traits/HasCreditControl.php

namespace App\Traits;

trait HasCreditControl
{
    public function hasAvailableCredit(float $amount): bool
    {
        return $this->available_credit >= $amount;
    }

    public function decreaseCredit(float $amount): bool
    {
        if (!$this->hasAvailableCredit($amount)) {
            return false;
        }

        $this->decrement('available_credit', $amount);
        $this->increment('used_credit', $amount);

        return true;
    }

    public function increaseCredit(float $amount): void
    {
        $this->increment('available_credit', $amount);
        $this->decrement('used_credit', $amount);
    }
}