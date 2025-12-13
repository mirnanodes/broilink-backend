<?php

namespace App\Http\Requests;

use App\DataTransferObjects\AggregateRequestDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for validating aggregate query parameters
 */
class AggregateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'farm_id' => ['required', 'integer', 'exists:farms,farm_id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'range' => ['required', 'in:1_day,1_week,1_month,6_months'],
        ];
    }

    /**
     * Convert validated request to DTO
     */
    public function dto(): AggregateRequestDto
    {
        $validated = $this->validated();

        return new AggregateRequestDto(
            farmId: (int) $validated['farm_id'],
            date: $validated['date'],
            range: $validated['range']
        );
    }
}
