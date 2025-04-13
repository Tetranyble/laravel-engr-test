<?php

namespace App\Traits;

use App\Enum\EncounterDateType;
use Carbon\Carbon;

trait CostOptimization
{
    /**
     * Calculate the date cost multiplier based on the day of the month.
     */
    public function calculateDateCostMultiplier(int $dayOfMonth): float
    {
        $insurer = $this->insurer;

        return 0.2 + (($dayOfMonth - 1) / 29) *
            ($insurer->month_max_percent_limit - $insurer->month_min_percent_limit); // 20% to 50%
    }

    /**
     * Calculate the specialty multiplier based on the insurer's specialty multipliers.
     */
    public function calculateSpecialtyMultiplier(): float
    {
        $specialtyMultipliers = $this->insurer ?? [];

        // Default to 1 if specialty multiplier is not found
        return $specialtyMultipliers[$this->specialty] ?? 1;
    }

    /**
     * Calculate the priority multiplier based on the priority level of the claim.
     */
    public function calculatePriorityMultiplier(): float
    {
        $priorityMultipliers = $this->insurer->priority_multipliers ?? [];

        // Default to 1 if priority multiplier is not found
        return $priorityMultipliers[(string) $this->priority_level] ?? 1;
    }

    /**
     * Calculate the claim cost based on the formula.
     * Allows flexibility for using either SUBMISSION_DATE or ENCOUNTER_DATE.
     *
     * @param  EncounterDateType|string  $dateType  The date type to use (SUBMISSION_DATE or ENCOUNTER_DATE)
     */
    public function calculateClaimCost(EncounterDateType|string $dateType): float
    {
        // Choose the date based on the provided EncounterDateType
        $date = $dateType === EncounterDateType::ENCOUNTER_DATE
            ? $this->encounter_date
            : $this->submission_date;

        // Parse the date and get the day of the month
        $dayOfMonth = Carbon::parse($date)->day;

        return $this->insurer->base_processing_cost * $this->calculateDateCostMultiplier($dayOfMonth) *
            $this->calculateSpecialtyMultiplier() *
            $this->calculatePriorityMultiplier() *
            ($this->insurer->base_processing_cost + $this->valueCostMultiplier());
    }

    public function valueCostMultiplier(int $scaleFactor = 1000): float|int
    {

        return $this->total_amount / $scaleFactor;
    }

    protected static function bootCostOptimization()
    {
        static::creating(function (self $model) {
            $model->setAttribute(
                'encounter_weight', $model->calculateClaimCost(EncounterDateType::ENCOUNTER_DATE)
            );
            $model->setAttribute(
                'submission_weight', $model->calculateClaimCost(EncounterDateType::SUBMISSION_DATE),
            );
        });
    }
}
