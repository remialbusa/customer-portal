<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="scroll-smooth h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0A2540">
        <title>BioTechnical Solutions — Customer Portal</title>
        <meta name="description" content="Sign in to the BioTechnical Solutions service portal. Open and track service tickets for the equipment we support.">

        <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('images/brand/favicon.svg')); ?>">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|plus-jakarta-sans:600,700,800&display=swap" rel="stylesheet" />

        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>

    <body class="font-sans antialiased text-brand-slate bg-brand-cream min-h-full flex flex-col">

        
        <header class="border-b border-brand-mist/60 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <a href="/" class="flex items-center" wire:navigate>
                    <img src="<?php echo e(asset('images/brand/logo-horizontal.svg')); ?>"
                         alt="BioTechnical Solutions Inc."
                         class="h-10 w-auto">
                </a>

                <div class="flex items-center gap-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                        <a href="<?php echo e(url('/dashboard')); ?>" wire:navigate
                           class="rounded-lg bg-brand-cta px-4 py-2 text-sm font-semibold text-white hover:opacity-95 transition">
                            Open dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?php echo e(route('login')); ?>" wire:navigate
                           class="text-sm font-semibold text-brand-navy hover:text-brand-blue">
                            Sign in
                        </a>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Route::has('register')): ?>
                            <a href="<?php echo e(route('register')); ?>" wire:navigate
                               class="rounded-lg bg-brand-cta px-4 py-2 text-sm font-semibold text-white hover:opacity-95 transition">
                                Create account
                            </a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </header>

        
        <main class="flex-1 flex items-center">
            <div class="mx-auto grid w-full max-w-6xl items-center gap-12 px-6 py-16 md:py-24 md:grid-cols-2">

                
                <div>
                    <h1 class="font-display text-3xl font-bold leading-tight text-brand-navy sm:text-4xl">
                        Customer Portal
                    </h1>
                    <p class="mt-4 max-w-md text-base text-brand-slate">
                        Sign in to open a service ticket, follow the technician assigned to you,
                        and read signed service reports for the equipment we support.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                            <a href="<?php echo e(url('/dashboard')); ?>" wire:navigate
                               class="inline-flex items-center gap-2 rounded-lg bg-brand-cta px-5 py-3 text-base font-semibold text-white hover:opacity-95 transition">
                                Go to your dashboard
                            </a>
                        <?php else: ?>
                            <a href="<?php echo e(route('login')); ?>" wire:navigate
                               class="inline-flex items-center gap-2 rounded-lg bg-brand-cta px-5 py-3 text-base font-semibold text-white hover:opacity-95 transition">
                                Sign in
                            </a>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Route::has('register')): ?>
                                <a href="<?php echo e(route('register')); ?>" wire:navigate
                                   class="inline-flex items-center rounded-lg border border-brand-mist bg-white px-5 py-3 text-base font-semibold text-brand-navy hover:bg-brand-mist/40 transition">
                                    Create an account
                                </a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <p class="mt-6 text-sm text-brand-slate">
                        Don't have an account?
                        <span class="text-brand-navy">Ask your service coordinator to invite you.</span>
                    </p>
                </div>

                
                <div>
                    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-brand-mist/60">
                        <h2 class="font-display text-base font-semibold text-brand-navy">
                            What you can do here
                        </h2>
                        <ul class="mt-4 space-y-3 text-sm text-brand-slate">
                            <li class="flex items-start gap-3">
                                <span class="mt-1 inline-flex h-1.5 w-1.5 shrink-0 rounded-full bg-brand-blue"></span>
                                Open a service ticket for any machine we support
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-1 inline-flex h-1.5 w-1.5 shrink-0 rounded-full bg-brand-blue"></span>
                                Chat in real time with the technician assigned to your ticket
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-1 inline-flex h-1.5 w-1.5 shrink-0 rounded-full bg-brand-blue"></span>
                                Read the signed service report when the work is done
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-1 inline-flex h-1.5 w-1.5 shrink-0 rounded-full bg-brand-blue"></span>
                                Look up past tickets and reports for your equipment
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>

        
        <footer class="border-t border-brand-mist bg-white">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-6 py-6 sm:flex-row">
                <p class="text-xs text-brand-slate">
                    &copy; <?php echo e(date('Y')); ?> BioTechnical Solutions Inc. &middot; All rights reserved.
                </p>
                <p class="text-xs italic text-brand-slate">
                    Providing Services Beyond Expectations
                </p>
            </div>
        </footer>

    </body>
</html>
<?php /**PATH C:\Users\USER\Documents\MONDAY.COM\Web Side Project\customer-portal\portal\resources\views/welcome.blade.php ENDPATH**/ ?>