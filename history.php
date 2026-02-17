<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotswold League | History</title>
    <link rel="icon" href="images/league-logo.webp" type="image/webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body { background-color: #0f172a; }
        .glass-panel { background: rgba(15, 23, 42, 0.8); backdrop-blur: 12px; border: 1px solid rgba(255, 255, 255, 0.05); }
        .table-row-hover:hover { background-color: rgba(255, 255, 255, 0.03); }
    </style>
</head>
<body class="text-white font-sans min-h-screen flex flex-col">

    <?php include 'nav.php'; ?>

    <div class="py-12 text-center px-4">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl mb-4">
            League <span class="text-sky-500">History</span>
        </h1>
        <p class="text-lg text-slate-400 max-w-2xl mx-auto">
            Archive of past season results and league standings.
        </p>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 flex-grow space-y-20">
        
        <!-- CONTROLS -->
        <div class="glass-panel p-4 rounded-xl flex flex-wrap justify-center gap-4 mb-12 sticky top-4 z-10 backdrop-blur-xl bg-slate-900/80 shadow-2xl border-white/10">
            <div class="flex gap-2">
                <button onclick="toggleAll(false)" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-xs font-bold uppercase tracking-wider text-slate-400 hover:text-white transition-colors border border-slate-700">Collapse All</button>
                <button onclick="toggleAll(true)" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg text-xs font-bold uppercase tracking-wider text-slate-400 hover:text-white transition-colors border border-slate-700">Expand All</button>
            </div>
            <div class="w-px h-auto bg-white/10 mx-2 hidden sm:block"></div>
            <div class="flex gap-2 flex-wrap justify-center">
                <button onclick="jumpTo('2025')" class="px-3 py-1.5 bg-sky-500/10 hover:bg-sky-500/20 text-sky-500 border border-sky-500/20 rounded-lg text-xs font-bold uppercase tracking-wider transition-colors">2025</button>
                <button onclick="jumpTo('2024')" class="px-3 py-1.5 bg-sky-500/10 hover:bg-sky-500/20 text-sky-500 border border-sky-500/20 rounded-lg text-xs font-bold uppercase tracking-wider transition-colors">2024</button>
                <button onclick="jumpTo('2023')" class="px-3 py-1.5 bg-sky-500/10 hover:bg-sky-500/20 text-sky-500 border border-sky-500/20 rounded-lg text-xs font-bold uppercase tracking-wider transition-colors">2023</button>
            </div>
        </div>

        <div id="year-2025" class="space-y-8 scroll-mt-40">
            <button onclick="toggleYear('2025')" class="w-full flex items-center justify-between group focus:outline-none text-left">
                <h2 class="text-3xl font-bold flex items-center gap-3">
                    <span class="text-sky-500">2025</span> Season Results
                </h2>
                <div class="bg-white/5 p-2 rounded-full group-hover:bg-white/10 transition-colors">
                    <i id="icon-2025" data-lucide="chevron-down" class="w-6 h-6 text-slate-400 group-hover:text-white transition-transform duration-300" style="transform: rotate(180deg)"></i>
                </div>
            </button>
            <div id="content-2025" class="space-y-8 transition-all duration-500 origin-top">

            <div class="glass-panel rounded-3xl overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-900/50 border-b border-white/5">
                                <th class="px-6 py-5 text-xs font-black uppercase tracking-widest text-slate-500">Team Name</th>
                                <th class="px-4 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R1</th>
                                <th class="px-4 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R2</th>
                                <th class="px-4 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R3</th>
                                <th class="px-4 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R4</th>
                                <th class="px-6 py-5 text-xs font-black uppercase tracking-widest text-sky-500 text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Southwold SC</td><td class="text-center text-slate-400">165</td><td class="text-center text-slate-400">157</td><td class="text-center text-slate-400">172</td><td class="text-center text-slate-400">169</td><td class="text-center font-black text-sky-400">663</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Bridgwater</td><td class="text-center text-slate-400">171</td><td class="text-center text-slate-400">176</td><td class="text-center text-slate-400">159</td><td class="text-center text-slate-400">139</td><td class="text-center font-black text-sky-400">645</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Bath Dolphin</td><td class="text-center text-slate-400">139</td><td class="text-center text-slate-400">162</td><td class="text-center text-slate-400">173</td><td class="text-center text-slate-400">153</td><td class="text-center font-black text-sky-400">627</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Clevedon</td><td class="text-center text-slate-400">131</td><td class="text-center text-slate-400">169</td><td class="text-center text-slate-400">123</td><td class="text-center text-slate-400">157</td><td class="text-center font-black text-sky-400">580</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Backwell*</td><td class="text-center text-slate-400">130</td><td class="text-center text-slate-400">164</td><td class="text-center text-slate-400">145</td><td class="text-center text-slate-400">141</td><td class="text-center font-black text-sky-400">580</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Brockworth</td><td class="text-center text-slate-400">144</td><td class="text-center text-slate-400">147</td><td class="text-center text-slate-400">128</td><td class="text-center text-slate-400">157</td><td class="text-center font-black text-sky-400">576</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Monnow SC</td><td class="text-center text-slate-400">124</td><td class="text-center text-slate-400">121</td><td class="text-center text-slate-400">157</td><td class="text-center text-slate-400">146</td><td class="text-center font-black text-sky-400">548</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">City Of Bristol</td><td class="text-center text-slate-400">158</td><td class="text-center text-slate-400">122</td><td class="text-center text-slate-400">142</td><td class="text-center text-slate-400">124</td><td class="text-center font-black text-sky-400">546</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Yeovil</td><td class="text-center text-slate-400">158</td><td class="text-center text-slate-400">173</td><td class="text-center text-slate-400">110</td><td class="text-center text-slate-400">85</td><td class="text-center font-black text-sky-400">526</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Cwmbran</td><td class="text-center text-slate-400">90</td><td class="text-center text-slate-400">130</td><td class="text-center text-slate-400">135</td><td class="text-center text-slate-400">155</td><td class="text-center font-black text-sky-400">510</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Swindon ASC</td><td class="text-center text-slate-400">120</td><td class="text-center text-slate-400">111</td><td class="text-center text-slate-400">130</td><td class="text-center text-slate-400">141</td><td class="text-center font-black text-sky-400">502</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Severnside Tritons</td><td class="text-center text-slate-400">129</td><td class="text-center text-slate-400">87</td><td class="text-center text-slate-400">102</td><td class="text-center text-slate-400">161</td><td class="text-center font-black text-sky-400">479</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Cheltenham S & W*</td><td class="text-center text-slate-400">121</td><td class="text-center text-slate-400">115</td><td class="text-center text-slate-400">119</td><td class="text-center text-slate-400">121</td><td class="text-center font-black text-sky-400">476</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Forest Of Dean</td><td class="text-center text-slate-400">124</td><td class="text-center text-slate-400">115</td><td class="text-center text-slate-400">129</td><td class="text-center text-slate-400">104</td><td class="text-center font-black text-sky-400">472</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Bristol North</td><td class="text-center text-slate-400">133</td><td class="text-center text-slate-400">118</td><td class="text-center text-slate-400">108</td><td class="text-center text-slate-400">107</td><td class="text-center font-black text-sky-400">466</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Academy Swim Team</td><td class="text-center text-slate-400">102</td><td class="text-center text-slate-400">113</td><td class="text-center text-slate-400">116</td><td class="text-center text-slate-400">131</td><td class="text-center font-black text-sky-400">462</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Wells</td><td class="text-center text-slate-400">131</td><td class="text-center text-slate-400">117</td><td class="text-center text-slate-400">96</td><td class="text-center text-slate-400">101</td><td class="text-center font-black text-sky-400">445</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Burnham-On-Sea*</td><td class="text-center text-slate-400">92</td><td class="text-center text-slate-400">95</td><td class="text-center text-slate-400">101</td><td class="text-center text-slate-400">115</td><td class="text-center font-black text-sky-400">403</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Dursley</td><td class="text-center text-slate-400">125</td><td class="text-center text-slate-400">74</td><td class="text-center text-slate-400">87</td><td class="text-center text-slate-400">107</td><td class="text-center font-black text-sky-400">393</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Bristol Penguins*</td><td class="text-center text-slate-400">90</td><td class="text-center text-slate-400">117</td><td class="text-center text-slate-400">104</td><td class="text-center text-slate-400">0</td><td class="text-center font-black text-sky-400">311</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 bg-slate-900/30 text-xs italic text-slate-500">
                    * Gala Cancelled - Rule 32 applied to Round 3 for Backwell, Cheltenham S&W, Burnham-On-Sea, and Bristol Penguins.
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="glass-panel p-6 rounded-2xl border-t-4 border-t-emerald-500">
                    <h3 class="font-bold text-xl mb-4 text-emerald-400 flex items-center gap-2">
                        <i data-lucide="trophy" class="w-5 h-5"></i> A Final
                    </h3>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li class="flex justify-between"><span>Southwold SC</span> <span class="font-bold">310</span></li>
                        <li class="flex justify-between"><span>Bridgwater</span> <span class="font-bold">287</span></li>
                        <li class="flex justify-between"><span>Bath Dolphin</span> <span class="font-bold">274</span></li>
                        <li class="flex justify-between"><span>Backwell</span> <span class="font-bold">271</span></li>
                        <li class="flex justify-between"><span>Monnow SC</span> <span class="font-bold">214</span></li>
                        <li class="flex justify-between"><span>Clevedon</span> <span class="font-bold">199</span></li>
                        <li class="flex justify-between"><span>Brockworth</span> <span class="font-bold">165</span></li>
                        <li class="flex justify-between"><span>City Of Bristol</span> <span class="font-bold">160</span></li>
                    </ul>
                </div>
                <div class="glass-panel p-6 rounded-2xl border-t-4 border-t-amber-500">
                    <h3 class="font-bold text-xl mb-4 text-amber-400 flex items-center gap-2">
                        <i data-lucide="medal" class="w-5 h-5"></i> B Final
                    </h3>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li class="flex justify-between"><span>Severnside Tritons</span> <span class="font-bold">225</span></li>
                        <li class="flex justify-between"><span>Forest of Dean</span> <span class="font-bold">218</span></li>
                        <li class="flex justify-between"><span>Cwmbran</span> <span class="font-bold">213</span></li>
                        <li class="flex justify-between"><span>Swindon ASC</span> <span class="font-bold">204</span></li>
                        <li class="flex justify-between"><span>Yeovil</span> <span class="font-bold">187</span></li>
                    </ul>
                </div>
                <div class="glass-panel p-6 rounded-2xl border-t-4 border-t-rose-500">
                    <h3 class="font-bold text-xl mb-4 text-rose-400 flex items-center gap-2">
                        <i data-lucide="award" class="w-5 h-5"></i> C Final
                    </h3>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li class="flex justify-between"><span>Bristol Penguins</span> <span class="font-bold">228</span></li>
                        <li class="flex justify-between"><span>Bristol North</span> <span class="font-bold">205</span></li>
                        <li class="flex justify-between"><span>Academy Swim Team</span> <span class="font-bold">203</span></li>
                        <li class="flex justify-between"><span>Burnham-On-Sea</span> <span class="font-bold">159</span></li>
                        <li class="flex justify-between"><span>Dursley</span> <span class="font-bold">156</span></li>
                        <li class="flex justify-between"><span>Wells</span> <span class="font-bold">148</span></li>
                    </ul>
                </div>
            </div>
                    </div>
                </div>
        
                <div class="w-full h-px bg-white/5 my-12"></div>

        <div id="year-2024" class="space-y-8 scroll-mt-40">
            <button onclick="toggleYear('2024')" class="w-full flex items-center justify-between group focus:outline-none text-left">
                <h2 class="text-3xl font-bold flex items-center gap-3">
                    <span class="text-sky-500">2024</span> Season Results
                </h2>
                <div class="bg-white/5 p-2 rounded-full group-hover:bg-white/10 transition-colors">
                    <i id="icon-2024" data-lucide="chevron-down" class="w-6 h-6 text-slate-400 group-hover:text-white transition-transform duration-300"></i>
                </div>
            </button>
            <div id="content-2024" class="hidden space-y-8 transition-all duration-500 origin-top">

            <div class="glass-panel rounded-3xl overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-900/50 border-b border-white/5">
                                <th class="px-6 py-5 text-xs font-black uppercase tracking-widest text-slate-500">Team Name</th>
                                <th class="px-4 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R1</th>
                                <th class="px-4 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R2</th>
                                <th class="px-4 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R3</th>
                                <th class="px-4 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R4</th>
                                <th class="px-6 py-5 text-xs font-black uppercase tracking-widest text-sky-500 text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Bristol Penguins</td><td class="text-center text-slate-400">164</td><td class="text-center text-slate-400">142</td><td class="text-center text-slate-400">145</td><td class="text-center text-slate-400">172</td><td class="text-center font-black text-sky-400">623</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Bath Dolphin</td><td class="text-center text-slate-400">160</td><td class="text-center text-slate-400">138</td><td class="text-center text-slate-400">164</td><td class="text-center text-slate-400">158</td><td class="text-center font-black text-sky-400">620</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Southwold</td><td class="text-center text-slate-400">164</td><td class="text-center text-slate-400">145</td><td class="text-center text-slate-400">151</td><td class="text-center text-slate-400">140</td><td class="text-center font-black text-sky-400">600</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Backwell</td><td class="text-center text-slate-400">148</td><td class="text-center text-slate-400">142</td><td class="text-center text-slate-400">168</td><td class="text-center text-slate-400">138</td><td class="text-center font-black text-sky-400">596</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Cheltenham S & W</td><td class="text-center text-slate-400">133</td><td class="text-center text-slate-400">148</td><td class="text-center text-slate-400">141</td><td class="text-center text-slate-400">171</td><td class="text-center font-black text-sky-400">593</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">City of Bristol</td><td class="text-center text-slate-400">125</td><td class="text-center text-slate-400">173</td><td class="text-center text-slate-400">136</td><td class="text-center text-slate-400">154</td><td class="text-center font-black text-sky-400">588</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Bridgwater</td><td class="text-center text-slate-400">132</td><td class="text-center text-slate-400">166</td><td class="text-center text-slate-400">125</td><td class="text-center text-slate-400">147</td><td class="text-center font-black text-sky-400">570</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Brockworth</td><td class="text-center text-slate-400">133</td><td class="text-center text-slate-400">127</td><td class="text-center text-slate-400">150</td><td class="text-center text-slate-400">147</td><td class="text-center font-black text-sky-400">557</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Cwmbran</td><td class="text-center text-slate-400">129</td><td class="text-center text-slate-400">94</td><td class="text-center text-slate-400">176</td><td class="text-center text-slate-400">141</td><td class="text-center font-black text-sky-400">540</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Wells</td><td class="text-center text-slate-400">128</td><td class="text-center text-slate-400">127</td><td class="text-center text-slate-400">143</td><td class="text-center text-slate-400">141</td><td class="text-center font-black text-sky-400">539</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Bristol North</td><td class="text-center text-slate-400">157</td><td class="text-center text-slate-400">119</td><td class="text-center text-slate-400">134</td><td class="text-center text-slate-400">117</td><td class="text-center font-black text-sky-400">527</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Evesham</td><td class="text-center text-slate-400">146</td><td class="text-center text-slate-400">172</td><td class="text-center text-slate-400">106</td><td class="text-center text-slate-400">86</td><td class="text-center font-black text-sky-400">510</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Trident</td><td class="text-center text-slate-400">87</td><td class="text-center text-slate-400">139</td><td class="text-center text-slate-400">117</td><td class="text-center text-slate-400">119</td><td class="text-center font-black text-sky-400">462</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Yeovil</td><td class="text-center text-slate-400">145</td><td class="text-center text-slate-400">109</td><td class="text-center text-slate-400">116</td><td class="text-center text-slate-400">88</td><td class="text-center font-black text-sky-400">458</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Clevedon</td><td class="text-center text-slate-400">105</td><td class="text-center text-slate-400">148</td><td class="text-center text-slate-400">91</td><td class="text-center text-slate-400">108</td><td class="text-center font-black text-sky-400">452</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Burnham-On-Sea</td><td class="text-center text-slate-400">108</td><td class="text-center text-slate-400">101</td><td class="text-center text-slate-400">91</td><td class="text-center text-slate-400">145</td><td class="text-center font-black text-sky-400">445</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Cheddar</td><td class="text-center text-slate-400">109</td><td class="text-center text-slate-400">85</td><td class="text-center text-slate-400">108</td><td class="text-center text-slate-400">104</td><td class="text-center font-black text-sky-400">406</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Dursley</td><td class="text-center text-slate-400">115</td><td class="text-center text-slate-400">89</td><td class="text-center text-slate-400">71</td><td class="text-center text-slate-400">102</td><td class="text-center font-black text-sky-400">377</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Cinderford</td><td class="text-center text-slate-400">117</td><td class="text-center text-slate-400">120</td><td class="text-center text-slate-400">0</td><td class="text-center text-slate-400">122</td><td class="text-center font-black text-sky-400">359</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="glass-panel p-6 rounded-2xl border-t-4 border-t-emerald-500">
                    <h3 class="font-bold text-xl mb-4 text-emerald-400 flex items-center gap-2">
                        <i data-lucide="trophy" class="w-5 h-5"></i> A Final
                    </h3>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li class="flex justify-between"><span>Bath Dolphin</span> <span class="font-bold">287</span></li>
                        <li class="flex justify-between"><span>Backwell</span> <span class="font-bold">287</span></li>
                        <li class="flex justify-between"><span>Southwold SC</span> <span class="font-bold">284</span></li>
                        <li class="flex justify-between"><span>Bristol Penguins</span> <span class="font-bold">264</span></li>
                        <li class="flex justify-between"><span>Cheltenham</span> <span class="font-bold">219</span></li>
                        <li class="flex justify-between"><span>City of Bristol</span> <span class="font-bold">215</span></li>
                        <li class="flex justify-between"><span>Brockworth</span> <span class="font-bold">165</span></li>
                        <li class="flex justify-between"><span>Bridgwater</span> <span class="font-bold">158</span></li>
                    </ul>
                </div>
                <div class="glass-panel p-6 rounded-2xl border-t-4 border-t-amber-500">
                    <h3 class="font-bold text-xl mb-4 text-amber-400 flex items-center gap-2">
                        <i data-lucide="medal" class="w-5 h-5"></i> B Final
                    </h3>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li class="flex justify-between"><span>Evesham</span> <span class="font-bold">204</span></li>
                        <li class="flex justify-between"><span>Bristol North</span> <span class="font-bold">167</span></li>
                        <li class="flex justify-between"><span>Cwmbran</span> <span class="font-bold">166</span></li>
                        <li class="flex justify-between"><span>Wells</span> <span class="font-bold">148</span></li>
                        <li class="flex justify-between"><span>Yeovil</span> <span class="font-bold">87</span></li>
                    </ul>
                </div>
                <div class="glass-panel p-6 rounded-2xl border-t-4 border-t-rose-500">
                    <h3 class="font-bold text-xl mb-4 text-rose-400 flex items-center gap-2">
                        <i data-lucide="award" class="w-5 h-5"></i> C Final
                    </h3>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li class="flex justify-between"><span>Clevedon</span> <span class="font-bold">197</span></li>
                        <li class="flex justify-between"><span>Cinderford</span> <span class="font-bold">162</span></li>
                        <li class="flex justify-between"><span>Burnham-On-Sea</span> <span class="font-bold">155</span></li>
                        <li class="flex justify-between"><span>Dursley</span> <span class="font-bold">148</span></li>
                        <li class="flex justify-between"><span>Cheddar</span> <span class="font-bold">114</span></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="w-full h-px bg-white/5 my-12"></div>

        <div id="year-2023" class="space-y-8 scroll-mt-40">
            <button onclick="toggleYear('2023')" class="w-full flex items-center justify-between group focus:outline-none text-left">
                <h2 class="text-3xl font-bold flex items-center gap-3">
                    <span class="text-sky-500">2023</span> Season Results
                </h2>
                <div class="bg-white/5 p-2 rounded-full group-hover:bg-white/10 transition-colors">
                    <i id="icon-2023" data-lucide="chevron-down" class="w-6 h-6 text-slate-400 group-hover:text-white transition-transform duration-300"></i>
                </div>
            </button>
            <div id="content-2023" class="hidden space-y-8 transition-all duration-500 origin-top">

            <div class="glass-panel rounded-3xl overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-900/50 border-b border-white/5">
                                <th class="px-6 py-5 text-xs font-black uppercase tracking-widest text-slate-500">Team Name</th>
                                <th class="px-4 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R1</th>
                                <th class="px-4 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R2</th>
                                <th class="px-4 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R3</th>
                                <th class="px-4 py-5 text-xs font-black uppercase tracking-widest text-slate-500 text-center">R4</th>
                                <th class="px-6 py-5 text-xs font-black uppercase tracking-widest text-sky-500 text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Bath Dolphin</td><td class="text-center text-slate-400">149</td><td class="text-center text-slate-400">175</td><td class="text-center text-slate-400">162</td><td class="text-center text-slate-400">164</td><td class="text-center font-black text-sky-400">650</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Cheltenham S & W</td><td class="text-center text-slate-400">140</td><td class="text-center text-slate-400">150</td><td class="text-center text-slate-400">171</td><td class="text-center text-slate-400">152</td><td class="text-center font-black text-sky-400">613</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Bridgwater</td><td class="text-center text-slate-400">144</td><td class="text-center text-slate-400">151</td><td class="text-center text-slate-400">157</td><td class="text-center text-slate-400">157</td><td class="text-center font-black text-sky-400">609</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Swim Academy</td><td class="text-center text-slate-400">142</td><td class="text-center text-slate-400">147</td><td class="text-center text-slate-400">165</td><td class="text-center text-slate-400">144</td><td class="text-center font-black text-sky-400">598</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Taunton</td><td class="text-center text-slate-400">147</td><td class="text-center text-slate-400">154</td><td class="text-center text-slate-400">137</td><td class="text-center text-slate-400">0</td><td class="text-center font-black text-sky-400">584</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">City of Bristol</td><td class="text-center text-slate-400">132</td><td class="text-center text-slate-400">146</td><td class="text-center text-slate-400">150</td><td class="text-center text-slate-400">118</td><td class="text-center font-black text-sky-400">546</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Clevedon</td><td class="text-center text-slate-400">151</td><td class="text-center text-slate-400">103</td><td class="text-center text-slate-400">126</td><td class="text-center text-slate-400">161</td><td class="text-center font-black text-sky-400">541</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Backwell</td><td class="text-center text-slate-400">118</td><td class="text-center text-slate-400">138</td><td class="text-center text-slate-400">139</td><td class="text-center text-slate-400">134</td><td class="text-center font-black text-sky-400">529</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Wells</td><td class="text-center text-slate-400">115</td><td class="text-center text-slate-400">168</td><td class="text-center text-slate-400">115</td><td class="text-center text-slate-400">130</td><td class="text-center font-black text-sky-400">528</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Cinderford</td><td class="text-center text-slate-400">136</td><td class="text-center text-slate-400">134</td><td class="text-center text-slate-400">107</td><td class="text-center text-slate-400">144</td><td class="text-center font-black text-sky-400">521</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Bristol North</td><td class="text-center text-slate-400">154</td><td class="text-center text-slate-400">120</td><td class="text-center text-slate-400">121</td><td class="text-center text-slate-400">124</td><td class="text-center font-black text-sky-400">519</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Brockworth</td><td class="text-center text-slate-400">113</td><td class="text-center text-slate-400">129</td><td class="text-center text-slate-400">124</td><td class="text-center text-slate-400">146</td><td class="text-center font-black text-sky-400">512</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Bristol Penguins</td><td class="text-center text-slate-400">125</td><td class="text-center text-slate-400">129</td><td class="text-center text-slate-400">139</td><td class="text-center text-slate-400">114</td><td class="text-center font-black text-sky-400">507</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Trident</td><td class="text-center text-slate-400">114</td><td class="text-center text-slate-400">133</td><td class="text-center text-slate-400">130</td><td class="text-center text-slate-400">127</td><td class="text-center font-black text-sky-400">504</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Cwmbran</td><td class="text-center text-slate-400">158</td><td class="text-center text-slate-400">92</td><td class="text-center text-slate-400">120</td><td class="text-center text-slate-400">131</td><td class="text-center font-black text-sky-400">501</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Cheddar</td><td class="text-center text-slate-400">111</td><td class="text-center text-slate-400">107</td><td class="text-center text-slate-400">112</td><td class="text-center text-slate-400">126</td><td class="text-center font-black text-sky-400">456</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Burnham-On-Sea</td><td class="text-center text-slate-400">105</td><td class="text-center text-slate-400">120</td><td class="text-center text-slate-400">115</td><td class="text-center text-slate-400">112</td><td class="text-center font-black text-sky-400">452</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Keynsham</td><td class="text-center text-slate-400">124</td><td class="text-center text-slate-400">102</td><td class="text-center text-slate-400">105</td><td class="text-center text-slate-400">117</td><td class="text-center font-black text-sky-400">448</td></tr>
                            <tr class="table-row-hover"><td class="px-6 py-4 text-sm font-bold text-white">Dursley</td><td class="text-center text-slate-400">101</td><td class="text-center text-slate-400">68</td><td class="text-center text-slate-400">62</td><td class="text-center text-slate-400">47</td><td class="text-center font-black text-sky-400">278</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="glass-panel p-6 rounded-2xl border-t-4 border-t-emerald-500">
                    <h3 class="font-bold text-xl mb-4 text-emerald-400 flex items-center gap-2">
                        <i data-lucide="trophy" class="w-5 h-5"></i> A Final
                    </h3>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li class="flex justify-between"><span>Bath Dolphin</span> <span class="font-bold">254</span></li>
                        <li class="flex justify-between"><span>Clevedon</span> <span class="font-bold">227</span></li>
                        <li class="flex justify-between"><span>Bridgwater</span> <span class="font-bold">217</span></li>
                        <li class="flex justify-between"><span>City of Bristol</span> <span class="font-bold">213</span></li>
                        <li class="flex justify-between"><span>Cheltenham S & W</span> <span class="font-bold">202</span></li>
                        <li class="flex justify-between"><span>Backwell</span> <span class="font-bold">168</span></li>
                        <li class="flex justify-between"><span>Swim Academy</span> <span class="font-bold">167</span></li>
                        <li class="flex justify-between"><span>Taunton</span> <span class="font-bold italic">DNS</span></li>
                    </ul>
                </div>
                <div class="glass-panel p-6 rounded-2xl border-t-4 border-t-amber-500">
                    <h3 class="font-bold text-xl mb-4 text-amber-400 flex items-center gap-2">
                        <i data-lucide="medal" class="w-5 h-5"></i> B Final
                    </h3>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li class="flex justify-between"><span>Bristol North</span> <span class="font-bold">195</span></li>
                        <li class="flex justify-between"><span>Brockworth</span> <span class="font-bold">192</span></li>
                        <li class="flex justify-between"><span>Wells</span> <span class="font-bold">191</span></li>
                        <li class="flex justify-between"><span>Cinderford</span> <span class="font-bold">185</span></li>
                        <li class="flex justify-between"><span>Bristol Penguins</span> <span class="font-bold">175</span></li>
                        <li class="flex justify-between"><span>Trident</span> <span class="font-bold">162</span></li>
                    </ul>
                </div>
                <div class="glass-panel p-6 rounded-2xl border-t-4 border-t-rose-500">
                    <h3 class="font-bold text-xl mb-4 text-rose-400 flex items-center gap-2">
                        <i data-lucide="award" class="w-5 h-5"></i> C Final
                    </h3>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li class="flex justify-between"><span>Cwmbran</span> <span class="font-bold">201</span></li>
                        <li class="flex justify-between"><span>Cheddar</span> <span class="font-bold">169</span></li>
                        <li class="flex justify-between"><span>Keynsham</span> <span class="font-bold">164</span></li>
                        <li class="flex justify-between"><span>Burnham-On-Sea</span> <span class="font-bold">148</span></li>
                        <li class="flex justify-between"><span>Dursley</span> <span class="font-bold">99</span></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="glass-panel p-6 rounded-2xl border-l-4 border-l-amber-500 flex flex-col md:flex-row items-center gap-4">
            <div class="bg-amber-500/10 p-3 rounded-xl text-amber-500">
                <i data-lucide="archive" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-bold text-white">Missing Data?</h3>
                <p class="text-sm text-slate-400">If you have records from previous years detailing scoring or finals results, please reach out so we can update this archive.</p>
            </div>
            <a href="mailto:lewisplume@gmail.com" class="md:ml-auto bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-lg text-sm font-bold transition-colors">Contact Secretary</a>
        </div>

        <footer class="mt-20 text-center text-slate-600 text-[10px] uppercase tracking-[0.3em] py-8">
            &copy; 2026 The Cotswold Swimming League | Built by Lewis Plume
        </footer>
    </div>

    <script>
        function toggleYear(year, forceState = null) {
            const content = document.getElementById(`content-${year}`);
            const icon = document.getElementById(`icon-${year}`);
            
            if (!content || !icon) return;

            // Check if hidden (using Tailwind 'hidden' class or style)
            const isHidden = content.classList.contains('hidden');
            const shouldShow = forceState !== null ? forceState : isHidden;

            if (shouldShow) {
                content.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                content.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }

        function toggleAll(expand) {
            ['2025', '2024', '2023'].forEach(year => toggleYear(year, expand));
        }

        function jumpTo(year) {
            // Expand the target year
            toggleYear(year, true);
            // Scroll to it
            const element = document.getElementById(`year-${year}`);
            if (element) {
                // Determine offset for fixed header if needed, but scroll-mt handled in CSS
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        lucide.createIcons();


    </script>
</body>
</html>