<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ rtrim(url('/'), '/') }}">
    <title>Login | QA System IPPI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="relative min-h-screen flex items-center justify-center p-6 overflow-hidden font-sans bg-rose-50">

    <!-- Background Elements -->
    <!-- Pinkish Gradient Base -->
    <div class="absolute inset-0 bg-gradient-to-br from-pink-200 via-rose-100 to-rose-50"></div>

    <!-- Illustrations (Placed before circles so circles render on top) -->
    <div class="absolute inset-0 z-0 pointer-events-none">
        <img src="{{ asset('Cloud.png') }}" class="w-full h-full object-cover object-bottom opacity-10 mix-blend-multiply saturate-50 scale-350 origin-bottom" alt="Cloud Background">
    </div>

    <!-- Floating Glowing Orbs -->
    <div class="absolute top-[15%] left-[10%] w-64 h-64 bg-pink-400/40 rounded-full mix-blend-multiply filter blur-3xl animate-pulse z-0 pointer-events-none" style="animation-duration: 4s;"></div>
    <div class="absolute bottom-[10%] right-[10%] w-80 h-80 bg-rose-400/40 rounded-full mix-blend-multiply filter blur-3xl animate-pulse z-0 pointer-events-none" style="animation-duration: 6s;"></div>
    <div class="absolute top-[55%] left-[25%] w-48 h-48 bg-pink-300/50 rounded-full mix-blend-multiply filter blur-3xl animate-pulse z-0 pointer-events-none" style="animation-duration: 5s;"></div>

    <!-- Small Sparkle Particles -->
    <div class="absolute top-[20%] left-[25%] w-2 h-2 bg-white rounded-full shadow-[0_0_15px_3px_rgba(255,255,255,0.8)] animate-pulse z-0 pointer-events-none" style="animation-duration: 3s;"></div>
    <div class="absolute top-[35%] right-[25%] w-1.5 h-1.5 bg-white rounded-full shadow-[0_0_10px_2px_rgba(255,255,255,0.8)] animate-pulse z-0 pointer-events-none" style="animation-duration: 4s;"></div>
    <div class="absolute bottom-[30%] left-[15%] w-2.5 h-2.5 bg-white rounded-full shadow-[0_0_15px_3px_rgba(255,255,255,0.8)] animate-pulse z-0 pointer-events-none" style="animation-duration: 2.5s;"></div>
    <div class="absolute top-[50%] right-[10%] w-2 h-2 bg-white rounded-full shadow-[0_0_15px_3px_rgba(255,255,255,0.8)] animate-pulse z-0 pointer-events-none" style="animation-duration: 5s;"></div>
    <div class="absolute bottom-[15%] right-[35%] w-1.5 h-1.5 bg-white rounded-full shadow-[0_0_10px_2px_rgba(255,255,255,0.8)] animate-pulse z-0 pointer-events-none" style="animation-duration: 3.5s;"></div>

    <!-- Concentric Circles Background (Rendered OVER the illustrations) -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] border border-white/70 rounded-full z-0 pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[1000px] h-[1000px] border border-white/50 rounded-full z-0 pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[1400px] h-[1400px] border border-white/30 rounded-full z-0 pointer-events-none"></div>

    <!-- Login Card -->
    <div class="relative z-10 w-full max-w-[420px]" x-data="loginForm()">
        <div class="bg-white backdrop-blur-xl border border-white/60 rounded-[1rem] p-10 shadow-[0_8px_32px_0_rgba(225,29,72,0.1)] relative">
            
            <!-- Header with Logo -->
            <div class="text-center mb-8">
                <div class="h-30 flex items-center justify-center mx-auto mb-6">
                    <img src="{{ asset('IPPII.png') }}" class="h-full object-contain drop-shadow-sm" alt="IPPI Logo">
                </div>
                <h2 class="text-2xl font-extrabold text-slate-800 mb-1">Selamat Datang</h2>
                <p class="text-slate-600 text-sm font-medium">Masuk untuk melanjutkan ke platform QA Anda</p>
            </div>
            
            <form @submit.prevent="submit" class="space-y-5">
                <!-- Employee ID Input -->
                <div class="relative group">
                    <!-- Ubah class 'text-slate-400' di baris ini untuk warna ikon default -->
                    <span class="absolute z-10 inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-red-500 group-focus-within:text-rose-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" /></svg>
                    </span>
                    <input 
                        type="text" 
                        x-model="form.employee_id" 
                        required 
                        placeholder="Employee ID"
                        class="w-full pl-14 pr-4 py-4 bg-white/70 border border-black/10 rounded-2xl focus:ring-4 focus:ring-rose-500/20 focus:border-rose-400 outline-none transition-all font-semibold text-slate-700 placeholder:text-slate-500 shadow-[0_2px_10px_rgba(0,0,0,0.02)] backdrop-blur-md"
                    >
                </div>

                <!-- Password Input -->
                <div class="relative group" x-data="{ show: false }">
                    <!-- Ubah class 'text-slate-400' di baris ini untuk warna ikon default -->
                    <span class="absolute z-10 inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-red-500 group-focus-within:text-rose-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    </span>
                    <input 
                        :type="show ? 'text' : 'password'" 
                        x-model="form.password" 
                        required 
                        placeholder="Password"
                        class="w-full pl-14 pr-12 py-4 bg-white/70 border border-black/10 rounded-2xl focus:ring-4 focus:ring-rose-500/20 focus:border-rose-400 outline-none transition-all font-semibold text-slate-700 placeholder:text-slate-500 shadow-[0_2px_10px_rgba(0,0,0,0.02)] backdrop-blur-md"
                    >
                    <button type="button" @click="show = !show" class="absolute z-10 inset-y-0 right-0 pr-5 flex items-center text-slate-400 hover:text-slate-600 transition-colors focus:outline-none">
                        <!-- Eye icon (password hidden) -->
                        <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <!-- Eye-slash icon (password shown) -->
                        <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>

                <!-- Error Alert -->
                <template x-if="error">
                    <div class="bg-rose-50/80 backdrop-blur-sm border border-rose-200 text-rose-600 px-4 py-3 rounded-2xl text-xs font-bold text-center" x-text="error"></div>
                </template>

                <!-- Submit Button -->
                <button type="submit" :disabled="loading"
                        class="w-full py-4 mt-4 bg-gradient-to-r from-red-500 to-rose-500 text-white text-[15px] font-bold rounded-2xl hover:from-pink-500 hover:to-rose-600 transition-all shadow-[0_8px_20px_rgba(225,29,72,0.3)] hover:-translate-y-0.5 hover:shadow-[0_10px_25px_rgba(225,29,72,0.4)] active:scale-[0.98] disabled:opacity-50 disabled:active:scale-100 flex items-center justify-center gap-3 tracking-wide">
                    <span x-show="!loading">Masuk</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Segera Masuk...
                    </span>
                </button>
            </form>
        </div>
    </div>

    <script>
        function loginForm() {
            return {
                form: {
                    employee_id: '',
                    password: '',
                    remember: false
                },
                loading: false,
                error: null,

                async submit() {
                    this.loading = true;
                    this.error = null;
                    
                    const appUrl = document.querySelector('meta[name="app-url"]').getAttribute('content');
                    
                    try {
                        const response = await axios.post(`${appUrl}/login`, this.form);
                        
                        // Simpan user info ke localStorage
                        localStorage.setItem('user', JSON.stringify(response.data.user));
                        
                        // Redirect menggunakan URL yang diberikan backend
                        window.location.href = response.data.redirect;
                    } catch (e) {
                        this.error = e.response?.data?.message || 'Terjadi kesalahan sistem';
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>