<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit()
    {
        /** @var User $user */
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

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
                    @mkdir($dir, 0775, true);
                }
                @imagewebp($src, $path, 80);
                @imagedestroy($src);
                @chmod($path, 0664);
                $data['avatar'] = $filename;
            } else {
                $ext = $image->getClientOriginalExtension() ?: 'jpg';
                $filename = 'avatars/' . uniqid() . '.' . $ext;
                $path = public_path('uploads/' . $filename);
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0775, true);
                }
                @copy($image->getRealPath(), $path);
                @chmod($path, 0664);
                $data['avatar'] = $filename;
            }
        }

        $user->update($data);

        return redirect()->route('profile.edit')->with('success', 'Profile berhasil diperbarui.');
    }

    public function updateAvatar(Request $request)
    {
        try {
            $request->validate([
                'avatar' => 'required|file|mimes:jpg,jpeg,png,webp|max:10240',
            ], [
                'avatar.required' => 'File foto profil wajib dipilih.',
                'avatar.mimes'    => 'Avatar harus berformat JPG, JPEG, PNG, atau WEBP.',
                'avatar.max'      => 'Ukuran foto maksimal 10MB.',
            ]);

            /** @var User $user */
            $user = Auth::user();
            $image = $request->file('avatar');

            // Pastikan folder uploads/avatars ada
            $uploadDir = public_path('uploads/avatars');
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }

            // Hapus avatar lama jika ada
            if ($user->avatar) {
                $oldPath = public_path('uploads/' . $user->avatar);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $saved = false;
            $filename = '';

            // Coba simpan sebagai WEBP jika fungsi GD tersedia
            if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
                $src = @imagecreatefromstring(file_get_contents($image->getRealPath()));
                if ($src !== false) {
                    $filename = 'avatars/' . uniqid() . '.webp';
                    $path = public_path('uploads/' . $filename);
                    if (@imagewebp($src, $path, 80)) {
                        @imagedestroy($src);
                        @chmod($path, 0664);
                        $saved = true;
                    }
                }
            }

            // Fallback jika GD webp tidak berhasil / tidak tersedia
            if (!$saved) {
                $ext = $image->getClientOriginalExtension() ?: 'jpg';
                $filename = 'avatars/' . uniqid() . '.' . $ext;
                $path = public_path('uploads/' . $filename);
                if (!@copy($image->getRealPath(), $path)) {
                    throw new \RuntimeException('Gagal menyalin file foto ke folder uploads. Pastikan permission folder public/uploads diatur ke 775 atau chown www-data.');
                }
                @chmod($path, 0664);
            }

            $user->update(['avatar' => $filename]);

            return response()->json([
                'success' => true,
                'message' => 'Foto profil berhasil diperbarui.',
                'avatar'  => asset('uploads/' . $filename)
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => collect($ve->errors())->flatten()->first() ?: 'Validasi gagal.',
                'errors'  => $ve->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\FacadesLog::error('Profile avatar upload error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server saat menyimpan foto: ' . $e->getMessage()
            ], 500);
        }
    }
}
