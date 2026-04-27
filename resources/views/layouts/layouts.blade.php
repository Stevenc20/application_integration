<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PT INTI PANTJA PRESS INDUSTRY</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])

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

  @yield('scripts')

</body>
</html>
