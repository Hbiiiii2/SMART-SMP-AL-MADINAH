<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LokasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => 'required|numeric|min:1',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama lokasi wajib diisi.',
            'radius.required' => 'Radius wajib diisi.',
            'radius.numeric' => 'Radius harus berupa angka.',
            'radius.min' => 'Radius minimal 1 meter.',
        ];
    }
}
