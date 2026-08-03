# Prosedur Deploy — Wajib setiap habis push ke main

```bash
sudo steven
git pull origin main
docker compose restart app nginx
docker compose exec app php artisan optimize:clear
```

## PENTING: build frontend setiap ada perubahan `resources/js`

`/public/build` di-gitignore (hasil Vite tidak ikut di-git), jadi perubahan JS/UI tidak aktif di server sampai di-build:

- Bind-mount ke container: `docker compose exec app npm run build`
- Image-based (perlu rebuild image): `docker compose up -d --build`

Selalu ingatkan user langkah build ini setelah push yang menyentuh `resources/js/` atau blade.
