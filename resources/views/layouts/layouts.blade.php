<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PT INTI PANTJA PRESS INDUSTRY</title>
  @php
      $isLocal = in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1']);
      $hasHot = file_exists(public_path('hot'));
      
      $buildCssUrl = '';
      $buildJsUrl = '';
      
      if (!$isLocal || !$hasHot) {
          $manifestPath = public_path('build/manifest.json');
          if (file_exists($manifestPath)) {
              $manifest = json_decode(file_get_contents($manifestPath), true);
              if (isset($manifest['resources/css/app.css']['file'])) {
                  $buildCssUrl = asset('build/' . $manifest['resources/css/app.css']['file']);
              }
              if (isset($manifest['resources/js/app.js']['file'])) {
                  $buildJsUrl = asset('build/' . $manifest['resources/js/app.js']['file']);
              }
          }
      }
  @endphp

  @if($isLocal && $hasHot)
      @vite(['resources/css/app.css', 'resources/js/app.js'])
  @else
      @if($buildCssUrl)
          <link rel="stylesheet" href="{{ $buildCssUrl }}">
      @endif
      @if($buildJsUrl)
          <script type="module" src="{{ $buildJsUrl }}"></script>
      @endif
  @endif

  <!-- Preconnect CDNs to boost Largest Contentful Paint (LCP) performance -->
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

  {{-- font awesome 4.7 --}}
   <link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>

<body class="bg-gray-100">

   @include('components.sidebar')


  <div class="md:ml-64 flex flex-col min-h-screen"> 

    <!-- RIGHT SIDE -->
    <div class="flex-1 flex flex-col">

      <!-- NAVBAR (BELONGS TO CONTENT) -->
      @include('components.navbar')                                                                                                                                                                                                                                                            

      <!-- PAGE CONTENT -->
      <main class="flex-1">
        @yield('content')
      </main>

      <!-- FOOTER -->
      @include('components.footer')

    </div>

  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      function updateClock() {
        const clockEl = document.getElementById('liveClockTopbar');
        if(clockEl) {
          const now = new Date();
          clockEl.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
        }
      }
      setInterval(updateClock, 1000);
      updateClock();
    });
  </script>
  @yield('scripts')

</body>
</html>
