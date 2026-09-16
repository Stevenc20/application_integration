<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name' => 'required|string|max:100',
            'nrp'  => 'required|digits:4|unique:users,nrp,' . $user->id,
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'confirmed|min:6';
        }

        if ($request->hasFile('avatar')) {
            $rules['avatar'] = 'file|mimes:jpg,jpeg,png,webp|max:2048';
        }

        $request->validate($rules, [
            'nrp.digits' => 'NRP harus 4 digit angka.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 6 karakter.',
            'avatar.mimes' => 'Avatar harus format JPG/PNG.',
            'avatar.max' => 'Avatar maksimal 2MB.',
        ]);

        $data = [
            'name' => $request->name,
            'nrp'  => $request->nrp,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $src = @imagecreatefromstring(file_get_contents($image->getRealPath()));
            if ($src !== false) {
                if ($user->avatar) {
                    $oldPath = public_path('uploads/' . $user->avatar);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $filename = 'avatars/' . uniqid() . '.webp';
                $path = public_path('uploads/' . $filename);
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                imagewebp($src, $path, 80);
                imagedestroy($src);
                chmod($path, 0644);
                $data['avatar'] = $filename;
            } else {
                $ext = $image->getClientOriginalExtension();
                $filename = 'avatars/' . uniqid() . '.' . $ext;
                $path = public_path('uploads/' . $filename);
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                copy($image->getRealPath(), $path);
                chmod($path, 0644);
                $data['avatar'] = $filename;
            }
        }

        $user->update($data);

        return redirect()->route('profile.edit')->with('success', 'Profile berhasil diperbarui.');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|file|mimes:jpg,jpeg,png,webp|max:10240',
        ], [
            'avatar.mimes' => 'Avatar harus format JPG/PNG.',
            'avatar.max'   => 'Avatar maksimal 10MB.',
        ]);

        $user = auth()->user();
        $image = $request->file('avatar');
        $src = @imagecreatefromstring(file_get_contents($image->getRealPath()));

        if ($src !== false) {
            if ($user->avatar) {
                $oldPath = public_path('uploads/' . $user->avatar);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            $filename = 'avatars/' . uniqid() . '.webp';
            $path = public_path('uploads/' . $filename);
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            imagewebp($src, $path, 80);
            imagedestroy($src);
            chmod($path, 0644);
            $user->update(['avatar' => $filename]);
        } else {
            $filename = 'avatars/' . uniqid() . '.' . $image->getClientOriginalExtension();
            $path = public_path('uploads/' . $filename);
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            copy($image->getRealPath(), $path);
            chmod($path, 0644);
            $user->update(['avatar' => $filename]);
        }

        return response()->json(['success' => true, 'message' => 'Foto profil berhasil diperbarui.']);
    }
}
