<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    public function updateSignature(Request $request)
    {
        $request->validate([
            'signature_file' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'signature_base64' => 'nullable|string',
        ]);

        $user = Auth::user();

        if ($request->filled('signature_base64')) {
            // Handle drawn canvas signature (data:image/png;base64,...)
            $base64Image = $request->signature_base64;
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $data = substr($base64Image, strpos($base64Image, ',') + 1);
                $type = strtolower($type[1]); // png, jpg, etc.

                $data = base64_decode($data);
                if ($data !== false) {
                    $fileName = 'signatures/sig_' . $user->id . '_' . time() . '.' . $type;
                    Storage::disk('public')->put($fileName, $data);
                    
                    if ($user->signature_path) {
                        Storage::disk('public')->delete($user->signature_path);
                    }

                    $user->signature_path = $fileName;
                    $user->save();
                }
            }
        } elseif ($request->hasFile('signature_file')) {
            // Handle file upload
            if ($user->signature_path) {
                Storage::disk('public')->delete($user->signature_path);
            }
            $path = $request->file('signature_file')->store('signatures', 'public');
            $user->signature_path = $path;
            $user->save();
        }

        return redirect()->route('profile.index')->with('success', 'Tanda Tangan Digital berhasil diperbarui!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return redirect()->route('profile.index')->with('success', 'Password akun berhasil diperbarui!');
    }
}
