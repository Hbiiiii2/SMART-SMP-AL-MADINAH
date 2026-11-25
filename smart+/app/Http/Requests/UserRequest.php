<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('user')?->id;

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|in:super_admin,admin_sekolah,kepala_sekolah,direktur,guru,staff_akademik,siswa,akun_demo',
            'is_active' => 'boolean',
            'expired_at' => $this->isMethod('POST')
                ? 'nullable|required_if:role,akun_demo|date|after:now'
                : 'nullable|required_if:role,akun_demo|date',
            'password' => $this->isMethod('POST') ? 'required|min:8' : 'nullable|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan.',
            'role.required' => 'Role wajib dipilih.',
            'expired_at.required_if' => 'Masa berlaku wajib diisi untuk role Akun Demo.',
            'expired_at.date' => 'Format tanggal masa berlaku tidak valid.',
            'expired_at.after' => 'Masa berlaku akun demo harus lebih dari waktu saat ini.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}
