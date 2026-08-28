<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $pusat = Branch::where('nama_cabang', 'like', '%Pusat%')->orWhere('nama_cabang', 'like', '%Grand Wisata%')->first();
        $branchId = $pusat ? $pusat->id : (Branch::first()->id ?? 1);

        $user = User::withTrashed()->where('username', 'KINGAshabil')->first();
        if ($user) {
            $user->restore();
            $user->update([
                'role' => 'owner',
                'full_name' => 'King Ashabil (Super Admin)',
                'branch_id' => $branchId,
            ]);
        } else {
            User::create([
                'username' => 'KINGAshabil',
                'full_name' => 'King Ashabil (Super Admin)',
                'role' => 'owner',
                'password' => Hash::make('dukuhzamrud@j7'),
                'branch_id' => $branchId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No deletion necessary
    }
};
