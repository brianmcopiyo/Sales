@extends('layouts.landing')

@section('content')
    {{-- 1. Navigation bar (theme toggles via .nav-over-dark for contrast over dark sections; default dark when hero is first) --}}
    <nav id="main-nav"
        class="fixed top-0 left-0 right-0 bg-themeCard/98 backdrop-blur-sm border-b border-themeBorder z-50 shadow-soft transition-colors duration-300 nav-over-dark">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-18 min-h-[4.5rem]">
                <a href="{{ url('/') }}"
                    class="nav-logo flex items-center shrink-0 transition-opacity duration-300 hover:opacity-90"
                    aria-label="{{ config('app.name', 'TajaCore') }} - Home">
                    @php $logoUrl = file_exists(public_path('logo.jpg')) ? asset('logo.jpg') : asset('assets/img/logo.jpeg'); @endphp
                    <img src="{{ $logoUrl }}" alt="{{ config('app.name', 'TajaCore') }}" class="h-9 w-auto max-h-9 object-contain block"
                        loading="eager">
                </a>
                <div class="hidden md:flex items-center gap-10">
                    <a href="#services"
                        class="nav-link text-sm text-themeBody hover:text-primary font-medium transition-colors duration-300">Services</a>
                    <a href="#about"
                        class="nav-link text-sm text-themeBody hover:text-primary font-medium transition-colors duration-300">About
                        Us</a>
                    <a href="#testimonials"
                        class="nav-link text-sm text-themeBody hover:text-primary font-medium transition-colors duration-300">Testimonials</a>
                    <a href="#contact"
                        class="nav-link text-sm text-themeBody hover:text-primary font-medium transition-colors duration-300">Contact</a>
                    <a href="{{ route('login') }}"
                        class="nav-btn-login inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-primary hover:bg-primary-dark rounded-lg shadow-soft transition-all duration-300 hover:shadow-soft-lg border-2 border-transparent">Login</a>
                </div>
                <button type="button"
                    class="nav-menu-btn md:hidden p-2.5 text-themeBody hover:text-primary rounded-lg hover:bg-themeInput transition-colors duration-300"
                    id="mobile-menu-btn" aria-label="Menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
        <div id="mobile-menu" class="hidden md:hidden border-t border-themeBorder bg-themeCard">
            <div class="px-4 py-5 space-y-1">
                <a href="#services"
                    class="mobile-nav-link block py-3 text-themeBody hover:text-primary font-medium border-b border-themeBorder">Services</a>
                <a href="#about"
                    class="mobile-nav-link block py-3 text-themeBody hover:text-primary font-medium border-b border-themeBorder">About
                    Us</a>
                <a href="#testimonials"
                    class="mobile-nav-link block py-3 text-themeBody hover:text-primary font-medium border-b border-themeBorder">Testimonials</a>
                <a href="#contact"
                    class="mobile-nav-link block py-3 text-themeBody hover:text-primary font-medium border-b border-themeBorder">Contact</a>
                <a href="{{ route('login') }}" class="block py-3 text-primary font-medium">Login</a>
            </div>
        </div>
    </nav>
    <style>
        #main-nav.nav-over-dark {
            background-color: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
        }

        #main-nav.nav-over-dark .nav-logo {
            color: #fff !important;
        }

        #main-nav.nav-over-dark .nav-link {
            color: rgba(255, 255, 255, 0.92) !important;
        }

        #main-nav.nav-over-dark .nav-link:hover {
            color: #fff !important;
        }

        #main-nav.nav-over-dark .nav-btn-login {
            background: transparent !important;
            border-color: rgba(255, 255, 255, 0.9) !important;
            color: #fff !important;
            box-shadow: none !important;
        }

        #main-nav.nav-over-dark .nav-btn-login:hover {
            background: rgba(255, 255, 255, 0.12) !important;
            border-color: #fff !important;
        }

        #main-nav.nav-over-dark .nav-menu-btn {
            color: rgba(255, 255, 255, 0.92) !important;
        }

        #main-nav.nav-over-dark .nav-menu-btn:hover {
            color: #fff !important;
            background: rgba(255, 255, 255, 0.1) !important;
        }
    </style>

    {{-- 2. Hero section with carousel (hero starts at 0 so teal extends behind navbar; content padded to sit below nav) --}}
    <section class="relative" data-nav-theme="dark" x-data="heroCarousel()" x-init="start()">
        <div class="relative min-h-[75vh] min-h-[480px] overflow-hidden">
            <div
                class="absolute inset-0 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(0,111,120,0.25),transparent)] z-10 pointer-events-none">
            </div>
            <template x-for="(slide, i) in slides" :key="i">
                <div x-show="active === i" x-transition:enter="transition ease-out duration-600"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-500" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0" class="absolute inset-0 flex items-center justify-center"
                    :style="'background: linear-gradient(160deg, ' + slide.bgFrom + ' 0%, ' + slide.bgTo + ' 70%);'">
                    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10 pt-[5rem] pb-8">
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-semibold text-white mb-6 tracking-tight leading-[1.1] text-balance"
                            x-text="slide.title"></h1>
                        <p class="text-lg sm:text-xl text-white/90 max-w-2xl mx-auto font-normal leading-relaxed"
                            x-text="slide.subtitle"></p>
                    </div>
                </div>
            </template>
            <div class="absolute bottom-8 left-0 right-0 flex justify-center gap-2.5 z-20">
                <template x-for="(slide, i) in slides" :key="'dot-' + i">
                    <button @click="active = i; resetTimer()" class="h-2 rounded-full transition-all duration-300"
                        :class="active === i ? 'w-8 bg-themeCard' : 'w-2 bg-themeCard/40 hover:bg-themeCard/70'"
                        :aria-label="'Slide ' + (i + 1)"></button>
                </template>
            </div>
            <button @click="prev()"
                class="absolute left-6 top-1/2 -translate-y-1/2 p-3 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors z-20 backdrop-blur-sm"
                aria-label="Previous">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button @click="next()"
                class="absolute right-6 top-1/2 -translate-y-1/2 p-3 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors z-20 backdrop-blur-sm"
                aria-label="Next">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </section>

    {{-- 3. Services --}}
    <section id="services" class="py-24 bg-themeCard" data-nav-theme="light">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-medium text-primary uppercase tracking-wider text-center mb-3">What we do</p>
            <h2 class="text-3xl sm:text-4xl font-semibold text-themeHeading mb-4 text-center tracking-tight">Our Services</h2>
            <p class="text-themeBody text-center max-w-2xl mx-auto mb-16 leading-relaxed">Manage inventory, sales, branches, and customer support in one place. Stay stocked, track orders, and serve your customers with clarity.</p>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    class="group p-8 rounded-2xl bg-themeCard border border-themeBorder shadow-card hover:shadow-card-hover hover:border-[#006F78]/30 transition-all duration-300 text-center">
                    <div
                        class="w-12 h-12 mx-auto mb-5 rounded-xl bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8 4-8-4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-primary mb-2">Inventory & Distribution</h3>
                    <p class="text-themeBody text-sm leading-relaxed">Move stock from hub to branches so every location stays supplied and in sync.</p>
                </div>
                <div
                    class="group p-8 rounded-2xl bg-themeCard border border-themeBorder shadow-card hover:shadow-card-hover hover:border-[#006F78]/30 transition-all duration-300 text-center">
                    <div
                        class="w-12 h-12 mx-auto mb-5 rounded-xl bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-primary mb-2">Sales</h3>
                    <p class="text-themeBody text-sm leading-relaxed">Sell products with clear, region-based pricing and full visibility on orders and stock.</p>
                </div>
                <div
                    class="group p-8 rounded-2xl bg-themeCard border border-themeBorder shadow-card hover:shadow-card-hover hover:border-[#006F78]/30 transition-all duration-300 text-center">
                    <div
                        class="w-12 h-12 mx-auto mb-5 rounded-xl bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-primary mb-2">Branch Network</h3>
                    <p class="text-themeBody text-sm leading-relaxed">We operate from a head location and regional branches
                        so we can serve you locally.</p>
                </div>
                <div
                    class="group p-8 rounded-2xl bg-themeCard border border-themeBorder shadow-card hover:shadow-card-hover hover:border-[#006F78]/30 transition-all duration-300 text-center">
                    <div
                        class="w-12 h-12 mx-auto mb-5 rounded-xl bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-primary mb-2">Customer Support</h3>
                    <p class="text-themeBody text-sm leading-relaxed">We handle inquiries and follow-up so you get answers
                        and resolution when you need it.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 4. Featured testimonial --}}
    <section class="py-24 bg-primary text-white" data-nav-theme="dark">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <svg class="w-10 h-10 mx-auto mb-6 text-white/30" fill="currentColor" viewBox="0 0 24 24"
                aria-hidden="true">
                <path
                    d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z" />
            </svg>
            <blockquote class="text-xl sm:text-2xl font-medium leading-relaxed mb-6">"Branches stay stocked, orders are clear, and we always know where we stand. Exactly what we needed."</blockquote>
            <cite class="text-white/80 text-sm font-medium not-italic">— Team using {{ config('app.name', 'TajaCore') }}</cite>
        </div>
    </section>

    {{-- 5. About us --}}
    <section id="about" class="py-24 bg-surface" data-nav-theme="light">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <p class="text-sm font-medium text-primary uppercase tracking-wider mb-3">Who we are</p>
                    <h2 class="text-3xl sm:text-4xl font-semibold text-themeHeading mb-6 tracking-tight">About Us</h2>
                    <p class="text-themeBody leading-relaxed mb-4">{{ config('app.name', 'TajaCore') }} is a sales and inventory management platform. Manage stock across branches, run sales with clear pricing, and support your customers from order to delivery.</p>
                    <p class="text-themeBody leading-relaxed mb-8">Keep branches supplied, track sales and stock, work with field agents and commissions, and handle support—all with one system built for clarity and control.</p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-themeBody">
                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            <span>Stock moved from hub to branches</span>
                        </li>
                        <li class="flex items-start gap-3 text-themeBody">
                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            <span>Clear, region-based pricing</span>
                        </li>
                        <li class="flex items-start gap-3 text-themeBody">
                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            <span>Field agents and commissions</span>
                        </li>
                        <li class="flex items-start gap-3 text-themeBody">
                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            <span>Support and follow-up when you need it</span>
                        </li>
                    </ul>
                </div>
                <div class="bg-gradient-to-br from-primary/10 to-primary/5 rounded-2xl border border-primary/20 shadow-soft h-96 flex items-center justify-center">
                    <div class="text-center px-8">
                        <div class="text-5xl font-bold text-primary mb-2">{{ config('app.name', 'TajaCore') }}</div>
                        <p class="text-themeBody text-sm max-w-xs">Sales, stock, and support in one place.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 6. Testimonials --}}
    <section id="testimonials" class="py-24 bg-themeCard" data-nav-theme="light">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-medium text-primary uppercase tracking-wider text-center mb-3">Testimonials</p>
            <h2 class="text-3xl sm:text-4xl font-semibold text-themeHeading mb-4 text-center tracking-tight">What People Say
            </h2>
            <p class="text-themeBody text-center max-w-xl mx-auto mb-14">What teams say about using the platform.</p>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-surface border-l-4 border-primary p-8 rounded-r-2xl shadow-card">
                    <p class="text-themeBody leading-relaxed mb-6">"We always know what’s in stock and when the next
                        delivery is. No surprises."</p>
                    <p class="text-primary text-sm font-semibold">Regional partner</p>
                </div>
                <div class="bg-surface border-l-4 border-primary p-8 rounded-r-2xl shadow-card">
                    <p class="text-themeBody leading-relaxed mb-6">"Orders are clear, pricing is transparent, and we get
                        support when we need it."</p>
                    <p class="text-primary text-sm font-semibold">Branch team</p>
                </div>
                <div class="bg-surface border-l-4 border-primary p-8 rounded-r-2xl shadow-card">
                    <p class="text-themeBody leading-relaxed mb-6">"When we have an issue, they respond and sort it out.
                        Reliable service."</p>
                    <p class="text-primary text-sm font-semibold">Customer</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 7. Contact --}}
    <section id="contact" class="py-24 bg-surface" data-nav-theme="light">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-medium text-primary uppercase tracking-wider text-center mb-3">Contact</p>
            <h2 class="text-3xl sm:text-4xl font-semibold text-themeHeading mb-4 text-center tracking-tight">Get in Touch</h2>
            <p class="text-themeBody text-center max-w-xl mx-auto mb-14">Questions about sales, inventory, or support? Get in touch.</p>
            <div class="max-w-lg mx-auto">
                <div class="space-y-6 bg-themeCard p-8 rounded-2xl border border-themeBorder shadow-soft text-center">
                    <p class="text-themeBody">Sign in to your account to manage sales, stock, and support. Need access? Contact your administrator.</p>
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center px-6 py-3 rounded-lg text-white font-medium bg-primary hover:bg-primary-dark shadow-soft hover:shadow-soft-lg transition-all duration-200">
                        Sign in
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- 8. Footer --}}
    <footer class="bg-themeInput text-themeMuted py-14" data-nav-theme="dark">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-8">
                <div class="text-primary font-semibold text-lg tracking-tight">{{ config('app.name', 'TajaCore') }}</div>
                <nav class="flex flex-wrap justify-center gap-8 text-sm font-medium">
                    <a href="#services" class="hover:text-white transition-colors">Services</a>
                    <a href="#about" class="hover:text-white transition-colors">About</a>
                    <a href="#testimonials" class="hover:text-white transition-colors">Testimonials</a>
                    <a href="#contact" class="hover:text-white transition-colors">Contact</a>
                    <a href="{{ route('login') }}" class="hover:text-white transition-colors">Login</a>
                </nav>
            </div>
            <div class="mt-10 pt-8 border-t border-themeBorder text-center text-sm text-themeMuted">© {{ date('Y') }} {{ config('app.name', 'TajaCore') }}. All rights reserved.</div>
        </div>
    </footer>

    <script>
        function heroCarousel() {
            return {
                active: 0,
                slides: [{
                        title: 'Sales & Inventory, One Platform',
                        subtitle: 'Manage stock across branches, run sales with clear pricing, and support your customers—all in one place.',
                        bgFrom: '#006F78',
                        bgTo: '#004d54'
                    },
                    {
                        title: 'Stock Where You Need It',
                        subtitle: 'Move inventory from hub to branches so the right products are in the right place when you need them.',
                        bgFrom: '#0d9488',
                        bgTo: '#006F78'
                    },
                    {
                        title: 'Clarity & Control',
                        subtitle: 'Clear pricing, straightforward sales, and support when you need it. Run your operations with full visibility.',
                        bgFrom: '#0f766e',
                        bgTo: '#0d4f4f'
                    }
                ],
                interval: null,
                start() {
                    this.interval = setInterval(() => {
                        this.next();
                    }, 5000);
                },
                next() {
                    this.active = (this.active + 1) % this.slides.length;
                },
                prev() {
                    this.active = (this.active - 1 + this.slides.length) % this.slides.length;
                },
                resetTimer() {
                    clearInterval(this.interval);
                    this.start();
                }
            };
        }
        var mobileMenu = document.getElementById('mobile-menu');
        var mobileMenuBtn = document.getElementById('mobile-menu-btn');
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
            document.querySelectorAll('.mobile-nav-link').forEach(function(link) {
                link.addEventListener('click', function() {
                    mobileMenu.classList.add('hidden');
                });
            });
            window.addEventListener('resize', function() {
                mobileMenu.classList.add('hidden');
            });
        }

        (function navThemeByScroll() {
            var nav = document.getElementById('main-nav');
            if (!nav) return;
            var sections = document.querySelectorAll('[data-nav-theme]');
            var navHeight = 76;

            function updateNavTheme() {
                var currentTheme = 'light';
                sections.forEach(function(el) {
                    var rect = el.getBoundingClientRect();
                    if (rect.top <= navHeight && rect.bottom >= navHeight) {
                        currentTheme = el.getAttribute('data-nav-theme') || 'light';
                    }
                });
                if (currentTheme === 'dark') {
                    nav.classList.add('nav-over-dark');
                } else {
                    nav.classList.remove('nav-over-dark');
                }
            }

            var ticking = false;

            function onScroll() {
                if (!ticking) {
                    requestAnimationFrame(function() {
                        updateNavTheme();
                        ticking = false;
                    });
                    ticking = true;
                }
            }

            updateNavTheme();
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', updateNavTheme);
            }
            window.addEventListener('scroll', onScroll, {
                passive: true
            });
            window.addEventListener('resize', updateNavTheme);
        })();
    </script>
@endsection
