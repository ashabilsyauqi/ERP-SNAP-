<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PrintShop ERP & POS</title>
    <!-- Google Fonts for premium typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Instrument Sans', 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
    </style>
</head>
<body class="relative flex min-h-full items-center justify-center overflow-hidden px-4 py-12 sm:px-6 lg:px-8 bg-slate-950">
    <!-- Mesh Gradients Background -->
    <div class="absolute inset-0 z-0 overflow-hidden">
        <div class="absolute -top-[40%] left-[20%] h-[80%] w-[60%] rounded-full bg-indigo-900/15 blur-[120px]"></div>
        <div class="absolute -bottom-[30%] -left-[10%] h-[70%] w-[50%] rounded-full bg-slate-900/40 blur-[100px]"></div>
        <div class="absolute top-[30%] -right-[10%] h-[60%] w-[45%] rounded-full bg-indigo-950/30 blur-[120px]"></div>
    </div>

    <!-- Main Card Wrapper -->
    <div class="relative z-10 w-full max-w-md space-y-8 animate-fade-in">
        <!-- Logo Section -->
        <div class="flex flex-col items-center text-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600/90 text-white shadow-xl shadow-indigo-600/20 ring-1 ring-white/10">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
            </div>
            <h2 class="mt-6 text-3xl font-bold tracking-tight text-white">Snaprint <span class="text-indigo-400">ERP</span></h2>
            <p class="mt-2 text-sm text-slate-400">Sign in to your workplace account</p>
        </div>

        <!-- Glassmorphism Card -->
        <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-800 p-8 rounded-3xl shadow-2xl shadow-black/40">
            @if($errors->any())
                <div class="bg-rose-500/10 border border-rose-500/20 text-rose-200 p-4 rounded-2xl mb-6">
                    <div class="flex items-center mb-1">
                        <svg class="h-4 w-4 text-rose-400 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="text-xs font-semibold">Sign in failed</span>
                    </div>
                    <ul class="list-disc ml-6 text-xs space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf
                
                <div>
                    <label for="username" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Username</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus
                            placeholder="username"
                            class="block w-full rounded-xl border border-slate-800 bg-slate-950/40 py-3 pl-10 pr-4 text-sm text-white placeholder-slate-600 shadow-inner transition duration-200 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/25">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input type="password" id="password" name="password" required
                            placeholder="••••••••"
                            class="block w-full rounded-xl border border-slate-800 bg-slate-950/40 py-3 pl-10 pr-4 text-sm text-white placeholder-slate-600 shadow-inner transition duration-200 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/25">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="group relative flex w-full justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-lg hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 active:bg-indigo-700 transition duration-150 ease-in-out cursor-pointer">
                        Sign In
                    </button>
                </div>
            </form>
        </div>
        
        <div class="text-center text-xs text-slate-600 font-medium">
            Authorized access only. Logins are audited.
        </div>
    </div>
</body>
</html>
