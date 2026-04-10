<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\FormFieldType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    use HasUlids;

    protected $fillable = [
        'form_id',
        'type',
        'label',
        'name',
        'is_required',
        'validation_rules',
        'settings',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => FormFieldType::class,
            'is_required' => 'boolean',
            'validation_rules' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(FormFieldOption::class)->orderBy('order');
    }
}
