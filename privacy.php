<?php include_once 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | The Cotswold League</title>
    <link rel="icon" href="images/league-logo.svg" type="image/webp">
    <script src="assets/vendor/tailwindcss-3.4.17.js"></script>
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
                        This policy explains what personal information The Cotswold League website and club portals use,
                        why we use it, who can see it, and the choices and rights available to individuals.
                    </p>
                    <p class="mt-2 text-sm text-slate-400">Last updated: 15 May 2026</p>
                </header>

                <section class="space-y-8 text-slate-200 leading-relaxed">
                    <section>
                        <h2 class="text-xl font-semibold text-white mb-3">1. Who we are</h2>
                        <p class="text-slate-300">
                            The Cotswold League is the data controller for personal information handled through this
                            website, including the club representative portal, digital teamsheets, gala administration
                            tools, and published league information.
                        </p>
                        <p class="mt-3 text-slate-400">
                            If you have a privacy question, please contact the league through your club representative or
                            the usual league administrator channels.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white mb-3">2. Information we collect and use</h2>
                        <p class="text-slate-300">
                            The public website publishes league information intended to be public, including club names,
                            pool names and addresses, gala dates, league tables, and gala results.
                        </p>
                        <p class="mt-3 text-slate-400">
                            The club and administrator portals may hold club representative names and email addresses,
                            club access details, audit history, gala teamsheets, swimmer names, age groups, dates of
                            birth where imported or needed to confirm age group, personal best times, availability,
                            selected events, notes supplied by clubs, uploaded teamsheet documents, and administrative
                            records needed to run league galas.
                        </p>
                        <p class="mt-3 text-slate-400">
                            Swimmer information is normally supplied by clubs or club representatives. Clubs should make
                            sure swimmers, parents, and guardians know that this information is being shared for league
                            administration.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white mb-3">3. Why we use information</h2>
                        <p class="text-slate-300">
                            We use personal information to administer the league, manage gala entries and teamsheets,
                            verify eligibility and age groups, publish appropriate results, support club representatives,
                            maintain audit records, secure the website, and communicate about league administration.
                        </p>
                        <p class="mt-3 text-slate-400">
                            We do not use this website for advertising profiling, automated decision-making with legal or
                            similarly significant effects, or selling personal information.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white mb-3">4. Lawful basis</h2>
                        <p class="text-slate-300">
                            We usually rely on legitimate interests to process personal information for league
                            administration, competition management, safety, record keeping, and website security. These
                            interests are balanced against the privacy rights of swimmers, parents, guardians, club
                            representatives, officials, and other users.
                        </p>
                        <p class="mt-3 text-slate-400">
                            Where information is needed to meet a legal obligation, respond to lawful requests, or manage
                            disputes, we may rely on legal obligation or legitimate interests as appropriate.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white mb-3">5. Cookies and local storage</h2>
                        <p class="text-slate-300">
                            This website does not use tracking cookies, analytics cookies, advertising cookies, or
                            marketing cookies. Login areas may use essential PHP session cookies and browser storage to
                            keep users signed in, protect the service, and remember portal state during use.
                        </p>
                        <p class="mt-3 text-slate-400">
                            Essential session cookies and similar storage are used only to provide the online service
                            requested by the user. They are not used for advertising or cross-site tracking.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white mb-3">6. Who information is shared with</h2>
                        <p class="text-slate-300">
                            Public league information and results may be visible to website visitors. Portal information
                            is shared with league administrators and, where relevant to a gala, authorised club
                            representatives from participating clubs.
                        </p>
                        <p class="mt-3 text-slate-400">
                            We use Cloudflare to help protect and deliver website traffic. Some pages may include links
                            to third-party platforms such as Google Drive, Google Calendar, Google Maps, and WhatsApp.
                            When you follow an external link, you leave this website and are subject to that third party's
                            own privacy and data handling policies.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white mb-3">7. How long we keep information</h2>
                        <p class="text-slate-300">
                            We keep league results and historical competition records where they form part of the
                            league's public sporting record. Portal records, teamsheets, uploads, audit entries, and club
                            contact details are kept only for as long as needed for current season administration,
                            safeguarding of records, dispute handling, continuity into the next season, or legal and
                            operational requirements.
                        </p>
                        <p class="mt-3 text-slate-400">
                            Clubs can ask the league to correct or remove outdated contact and portal information.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white mb-3">8. Security</h2>
                        <p class="text-slate-300">
                            We use access controls, HTTPS, security headers, session protections, restricted file access,
                            audit records, and role-based sharing to protect information. Club representatives must keep
                            passwords and PINs confidential and tell the league if they believe access details have been
                            shared or compromised.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white mb-3">9. Your rights</h2>
                        <p class="text-slate-300">
                            Under UK data protection law, individuals may have rights to be informed, access their
                            personal information, ask for inaccurate information to be corrected, ask for information to
                            be erased or restricted, object to some uses, and complain to the Information Commissioner's
                            Office.
                        </p>
                        <p class="mt-3 text-slate-400">
                            Requests should normally be made through the relevant club representative or league
                            administrator so we can identify the correct records. The ICO can be contacted at
                            <a href="https://ico.org.uk/make-a-complaint/" class="text-sky-300 hover:text-sky-200 underline">ico.org.uk/make-a-complaint</a>.
                        </p>
                    </section>
                </section>
            </div>

            <footer class="mt-10 text-center text-slate-600 text-[10px] uppercase tracking-[0.3em]">
                &copy; <?php echo (int)$current_season_year; ?> The Cotswold Swimming League | Built by Lewis Plume
            </footer>
        </div>
    </main>
</body>

</html>
