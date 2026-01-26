<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OperatingHoursUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:7'],

            'items.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'items.*.open_time' => ['required', 'date_format:H:i'],
            'items.*.close_time' => ['required', 'date_format:H:i'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);

            foreach ($items as $i => $row) {
                $open = $row['open_time'] ?? null;
                $close = $row['close_time'] ?? null;

                if ($open && $close && $open >= $close) {
                    $validator->errors()->add("items.$i.close_time", 'O horário de fechamento deve ser maior que o de abertura.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Envie a lista de horários.',
            'items.*.day_of_week.between' => 'Dia da semana inválido.',
            'items.*.open_time.date_format' => 'open_time deve estar no formato HH:mm.',
            'items.*.close_time.date_format' => 'close_time deve estar no formato HH:mm.',
        ];
    }
}
