@extends('layouts.supervisor')

@section('title', 'My Profile')

@section('content')
<div class="max-w-2xl mx-auto">
    <a href="javascript:history.back()" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 mb-4 transition-colors font-medium">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Kembali
    </a>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-800 to-slate-700">
            <h2 class="text-lg font-black text-white tracking-tight">My Profile</h2>
            <p class="text-xs text-slate-300 mt-0.5">Kelola data akun Anda</p>
        </div>

        @if(session('success'))
        <div class="mx-6 mt-4 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf

            {{-- Avatar --}}
            <div class="flex items-center gap-5">
                <div class="relative shrink-0">
                    <div class="w-20 h-20 rounded-full overflow-hidden bg-slate-100 border-2 border-slate-200">
                        @if($user->avatar)
                            <img src="{{ asset('uploads/'.$user->avatar) }}" alt="Avatar" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-red-100 text-primary-red text-2xl font-black">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                    <label for="avatar" class="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-white border border-slate-200 shadow-sm flex items-center justify-center cursor-pointer hover:bg-slate-50 transition">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </label>
                </div>
                <div>
                    <p class="font-bold text-slate-800 text-sm">{{ $user->name }}</p>
                    <p class="text-xs text-slate-400 capitalize">{{ $user->role }}</p>
                    <p class="text-[10px] text-slate-400 mt-1">Klik icon kamera untuk ganti foto</p>
                </div>
                <input type="file" id="avatar" name="avatar" accept="image/*" class="hidden">
            </div>

            <hr class="border-slate-100">

            {{-- Nama --}}
            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                    class="mt-1 w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-800 focus:border-transparent outline-none transition-all @error('name') border-red-300 @enderror">
                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- NRP --}}
            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">NRP</label>
                <input type="text" name="nrp" value="{{ old('nrp', $user->nrp) }}" required
                    class="mt-1 w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-800 focus:border-transparent outline-none transition-all @error('nrp') border-red-300 @enderror">
                @error('nrp') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <hr class="border-slate-100">

            {{-- Password (opsional) --}}
            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Password Baru <span class="text-slate-300 normal-case">(kosongkan jika tidak ingin ganti)</span></label>
                <input type="password" name="password" autocomplete="new-password"
                    class="mt-1 w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-800 focus:border-transparent outline-none transition-all @error('password') border-red-300 @enderror">
                @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" autocomplete="new-password"
                    class="mt-1 w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-slate-800 focus:border-transparent outline-none transition-all">
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-white text-sm font-bold rounded-xl transition-all shadow-sm hover:shadow-md">
                    Simpan Perubahan
                </button>
                <a href="javascript:history.back()" class="px-6 py-2.5 border border-slate-200 text-slate-500 text-sm font-bold rounded-xl hover:bg-slate-50 transition-all">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('avatar').addEventListener('change', async function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const maxUpload = {{ ini_get('upload_max_filesize') > 10 ? 10 : 5 }};
    if (file.size > maxUpload * 1024 * 1024) {
        alert('Ukuran file terlalu besar. Maksimal ' + maxUpload + 'MB.');
        this.value = '';
        return;
    }

    let uploadFile;
    try {
        uploadFile = await compressImage(file, 800, 0.82);
    } catch (err) {
        console.warn('Kompresi gagal, upload original:', err);
        uploadFile = file;
    }

    const formData = new FormData();
    formData.append('avatar', uploadFile, uploadFile.name);

    try {
        const res = await fetch('{{ route('profile.avatar') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value },
            body: formData
        });

        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Gagal upload foto');
        }
    } catch (err) {
        alert('Gagal upload: ' + err.message);
    }
});

function compressImage(file, maxSize, quality) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                let w = img.width, h = img.height;
                if (w > maxSize || h > maxSize) {
                    const ratio = Math.min(maxSize / w, maxSize / h);
                    w *= ratio; h *= ratio;
                }
                canvas.width = Math.round(w);
                canvas.height = Math.round(h);
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, Math.round(w), Math.round(h));
                const dataUrl = canvas.toDataURL('image/jpeg', quality);
                resolve(dataURLtoFile(dataUrl, 'avatar.jpg'));
            };
            img.onerror = () => reject(new Error('Gagal membaca gambar'));
            img.src = e.target.result;
        };
        reader.onerror = () => reject(new Error('Gagal membaca file'));
        reader.readAsDataURL(file);
    });
}

function dataURLtoFile(dataUrl, filename) {
    const arr = dataUrl.split(',');
    const mime = arr[0].match(/:(.*?);/)[1];
    const bstr = atob(arr[1]);
    let n = bstr.length;
    const u8arr = new Uint8Array(n);
    while (n--) u8arr[n] = bstr.charCodeAt(n);
    return new File([u8arr], filename, { type: mime });
}
</script>
@endsection
