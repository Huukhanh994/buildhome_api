<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ComponentType;
use App\Enums\HouseType;
use App\Enums\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ComponentCalculationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'component'  => ['required', 'string', new Enum(ComponentType::class)],
            'area'       => ['required', 'numeric', 'min:0.1', 'max:10000'],
            'house_type' => ['required', 'string', new Enum(HouseType::class)],
            'floors'     => ['required', 'integer', 'min:1', 'max:5'],
            'location'   => ['sometimes', 'string', new Enum(Region::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'component.required'  => 'Vui lòng chọn bộ phận công trình.',
            'area.required'       => 'Diện tích là bắt buộc.',
            'area.min'            => 'Diện tích phải lớn hơn 0.',
            'area.max'            => 'Diện tích không được vượt quá 10.000 m².',
            'house_type.required' => 'Vui lòng chọn loại nhà.',
            'floors.required'     => 'Số tầng là bắt buộc.',
            'floors.min'          => 'Số tầng phải từ 1 trở lên.',
            'floors.max'          => 'Hệ thống hỗ trợ tối đa 5 tầng.',
        ];
    }
}
