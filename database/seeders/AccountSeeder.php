<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['kode_akun' => '1-1000', 'nama_akun' => 'Kas', 'tipe' => 'aset'],
            ['kode_akun' => '1-1100', 'nama_akun' => 'Kas Kecil', 'tipe' => 'aset'],
            ['kode_akun' => '1-1200', 'nama_akun' => 'Bank', 'tipe' => 'aset'],
            ['kode_akun' => '1-1300', 'nama_akun' => 'Piutang Usaha', 'tipe' => 'aset'],
            ['kode_akun' => '4-1000', 'nama_akun' => 'Pendapatan Penjualan', 'tipe' => 'pendapatan'],
            ['kode_akun' => '4-2000', 'nama_akun' => 'Pendapatan Lain-lain', 'tipe' => 'pendapatan'],
            ['kode_akun' => '5-1000', 'nama_akun' => 'Beban Bahan Baku', 'tipe' => 'beban'],
            ['kode_akun' => '5-2000', 'nama_akun' => 'Beban Gaji', 'tipe' => 'beban'],
            ['kode_akun' => '5-3000', 'nama_akun' => 'Beban Sewa', 'tipe' => 'beban'],
            ['kode_akun' => '5-4000', 'nama_akun' => 'Beban Listrik & Air', 'tipe' => 'beban'],
            ['kode_akun' => '5-5000', 'nama_akun' => 'Beban Operasional', 'tipe' => 'beban'],
            ['kode_akun' => '5-9000', 'nama_akun' => 'Beban Lain-lain', 'tipe' => 'beban'],
            ['kode_akun' => '6-1000', 'nama_akun' => 'Harga Pokok Penjualan', 'tipe' => 'beban'],
        ];

        foreach ($accounts as $account) {
            Account::create(array_merge($account, ['is_active' => true]));
        }
    }
}
