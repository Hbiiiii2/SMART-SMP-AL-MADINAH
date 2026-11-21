<?php

namespace App\Http\Requests;

use App\Models\RfidCard;
use Illuminate\Foundation\Http\FormRequest;

class RfidCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('rfid')?->id;

        return [
            'siswa_id' => 'nullable|exists:siswa,id',
            'guru_id' => 'nullable|exists:guru,id',
            'uid' => 'required|string|max:50|unique:rfid_card,uid,' . $id,
            'is_active' => 'boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (empty($this->siswa_id) && empty($this->guru_id)) {
                $validator->errors()->add('siswa_id', 'Pilih minimal Siswa atau Guru.');
                return;
            }

            if (!empty($this->siswa_id) && !empty($this->guru_id)) {
                $validator->errors()->add('siswa_id', 'Hanya pilih Siswa ATAU Guru, tidak keduanya.');
                return;
            }

            $excludeId = $this->route('rfid')?->id;

            if (!empty($this->siswa_id)) {
                $exists = RfidCard::where('siswa_id', $this->siswa_id);
                if ($excludeId) {
                    $exists->where('id', '!=', $excludeId);
                }
                if ($exists->exists()) {
                    $validator->errors()->add('siswa_id', 'Siswa ini sudah terdaftar menggunakan kartu RFID.');
                }
            }

            if (!empty($this->guru_id)) {
                $exists = RfidCard::where('guru_id', $this->guru_id);
                if ($excludeId) {
                    $exists->where('id', '!=', $excludeId);
                }
                if ($exists->exists()) {
                    $validator->errors()->add('guru_id', 'Guru ini sudah terdaftar menggunakan kartu RFID.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'uid.required' => 'UID kartu RFID wajib diisi.',
            'uid.unique' => 'UID kartu RFID sudah terdaftar.',
        ];
    }
}
