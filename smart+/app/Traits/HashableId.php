<?php

namespace App\Traits;

trait HashableId
{
    /**
     * Mengubah ID menjadi string acak pendek (Hash)
     *
     * @return string
     */
    public function getRouteKey()
    {
        $id = $this->getKey();
        $key = config('app.key', 'default_secret_key');
        
        // Ambil 8 karakter pertama dari md5 sebagai salt
        $salt = substr(md5($key . $id), 0, 8); 
        
        // Ubah ID ke basis 36 dan pad dengan '0' di depannya minimal 3 karakter
        // Sehingga total panjang URL selalu minimal 11 karakter (8 salt + 3 ID)
        $encodedId = str_pad(base_convert($id, 10, 36), 3, '0', STR_PAD_LEFT); 
        
        return $salt . $encodedId;
    }

    /**
     * Mengembalikan model dari Hash yang diberikan di URL
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        try {
            // Coba decode sebagai hash terlebih dahulu jika panjangnya > 8
            if (is_string($value) && strlen($value) > 8) {
                $saltFromUrl = substr($value, 0, 8);
                $encodedId = substr($value, 8);
                $id = base_convert($encodedId, 36, 10);
                
                $key = config('app.key', 'default_secret_key');
                $expectedSalt = substr(md5($key . $id), 0, 8);

                // Validasi apakah salt cocok (mencegah orang menebak/mengubah base36-nya saja)
                if ($saltFromUrl === $expectedSalt) {
                    $model = $this->where($field ?? $this->getKeyName(), $id)->first();
                    if ($model) {
                        return $model;
                    }
                }
            }
        } catch (\Exception $e) {
            // Gagal decode
        }

        // Jika bukan hash atau tidak cocok hash-nya, cek apakah numeric ID langsung (fallback)
        if (is_numeric($value)) {
            return parent::resolveRouteBinding($value, $field);
        }

        abort(404, 'Data tidak ditemukan atau URL tidak valid.');
    }
}
