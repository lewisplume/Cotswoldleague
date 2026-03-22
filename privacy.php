<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | The Cotswold League</title>
    <link rel="icon" href="images/league-logo.webp" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #0f172a;
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.8);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>

<body class="text-white font-sans min-h-screen flex flex-col">
    <?php include 'nav.php'; ?>

    <main class="flex-grow py-10 md:py-16 px-4">
        <div class="max-w-4xl mx-auto">
            <div class="glass-panel rounded-3xl p-6 sm:p-8 md:p-10 shadow-2xl">
                <header class="mb-10">
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-400 mb-3">Legal</p>
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-sky-400">Privacy Policy</h1>
                    <p class="mt-3 text-slate-300 leading-relaxed">
                        This policy explains what information The Cotswold League website uses and how it is handled.
                    </p>
                    <p class="mt-2 text-sm text-slate-400">Last updated: 22 March 2026</p>
                </header>

                <section class="space-y-8 text-slate-200 leading-relaxed">
                    <section>
                        <h2 class="text-xl font-semibold text-white mb-3">1. Data Collection</h2>
                        <p class="text-slate-300">
                            The website publishes league information that is intended to be public. This includes club
                            names, pool addresses, and gala results.
                        </p>
                        <p class="mt-3 text-slate-400">
                            We do not request sensitive personal data through this website for public league display.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white mb-3">2. Cookies</h2>
                        <p class="text-slate-300">
                            This website does not use tracking cookies, analytics cookies, advertising cookies, or
                            marketing cookies.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white mb-3">3. Session Storage</h2>
                        <p class="text-slate-300">
                            For the Club Rep Portal login, the website uses browser <span class="font-semibold text-slate-100">sessionStorage</span>
                            only to maintain login state during your current browser session.
                        </p>
                        <p class="mt-3 text-slate-400">
                            This data is stored locally in your browser and is automatically removed when the browser
                            is closed.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white mb-3">4. Third-Party Services and Links</h2>
                        <p class="text-slate-300">
                            We use Cloudflare to help protect and secure website traffic. Some pages may also include
                            links to third-party platforms such as Google Drive and WhatsApp.
                        </p>
                        <p class="mt-3 text-slate-400">
                            When you follow an external link, you leave this website and are subject to that third
                            party's own privacy and data handling policies.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white mb-3">5. Contact</h2>
                        <p class="text-slate-300">
                            If your club has questions about this policy, please contact the league through the usual
                            club representative channels.
                        </p>
                    </section>
                </section>
            </div>

            <footer class="mt-10 text-center text-slate-600 text-[10px] uppercase tracking-[0.3em]">
                &copy; 2026 The Cotswold Swimming League | Built by Lewis Plume
            </footer>
        </div>
    </main>
</body>

</html>