<?php

namespace App\Models;

use App\Casts\AsMoney;
use App\Enums\SavingsGoalStatus;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'target_amount', 'target_date', 'icon', 'color', 'status'])]
class SavingsGoal extends Model
{
    use BelongsToUser, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'target_amount' => AsMoney::class,
            'target_date' => 'date',
            'status' => SavingsGoalStatus::class,
        ];
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(SavingsGoalContribution::class);
    }
}
