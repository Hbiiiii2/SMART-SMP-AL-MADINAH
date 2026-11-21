<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JadwalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guru_id' => 'required|exists:guru,id',
            'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'kelas' => 'required|string|max:20',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_mulai' => 'required|regex:/^\d{2}:\d{2}(:\d{2})?$/',
            'jam_selesai' => 'required|regex:/^\d{2}:\d{2}(:\d{2})?$/|after:jam_mulai',
            'lokasi_id' => 'required|exists:lokasi,id',
        ];
    }

    public function messages(): array
    {
        return [
            'guru_id.required' => 'Guru wajib dipilih.',
            'guru_id.exists' => 'Guru tidak valid.',
            'mata_pelajaran_id.required' => 'Mata pelajaran wajib dipilih.',
            'mata_pelajaran_id.exists' => 'Mata pelajaran tidak valid.',
            'tahun_ajaran_id.required' => 'Tahun ajaran wajib dipilih.',
            'kelas.required' => 'Kelas wajib diisi.',
            'hari.required' => 'Hari wajib dipilih.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.after' => 'Jam selesai harus setelah jam mulai.',
            'lokasi_id.required' => 'Lokasi wajib dipilih.',
        ];
    }
}
