<!DOCTYPE html>
<?php
$slidesJson = __DIR__ . '/ev-slides.json';
if (file_exists($slidesJson)) {
    $slides = json_decode(file_get_contents($slidesJson), true);
    if (!is_array($slides)) $slides = [];
} else {
    // Sample slides (admin can replace ev-slides.json in the same folder with a JSON array)
    $slides = [
        ["type"=>"image", "src"=>"slides/ev1.jpg", "title"=>"Welcome to EV LGRRC", "caption"=>"Knowledge hub for Region VIII", "duration"=>5000],
        ["type"=>"text", "title"=>"Our Mission", "caption"=>"To uphold integrity and social responsibility that promote collaboration for excellence in local governance.", "duration"=>5000],
        ["type"=>"video", "src"=>"slides/overview.mp4", "poster"=>"slides/overview-poster.jpg", "title"=>"MMK Overview", "duration"=>8000]
    ];
}
?><html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eastern Visayas Local Governance Regional Resource Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Poppins:wght@400;600;700&display=swap');
        :root{ --dilg-red: #8b0b09; --dilg-yellow: #f2c94c; --dilg-navy: #0b2545; --bg: #ffffff; --muted:#6b7280; }
        html,body{height:100%;}
        body { font-family: 'Inter', sans-serif; background:var(--bg); color:#0f172a; -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale; }
        h1,h2,h3,h4 { font-family: 'Poppins', sans-serif; }
        .skip-link{position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;}
        .skip-link:focus{left:12px;top:12px;width:auto;height:auto;background:#fff;padding:8px;border-radius:4px;z-index:9999;box-shadow:0 6px 18px rgba(2,6,23,0.15);}
        /* Hero / 3D canvas */
        #hero{position:relative;overflow:hidden;min-height:360px;height:64vh;display:block;}
        #hero-canvas{position:absolute;inset:0;width:100%;height:100%;display:block;z-index:1;}
        .hero-fallback{position:absolute;inset:0;background:linear-gradient(135deg,var(--dilg-red) 0%, var(--dilg-navy) 60%, var(--dilg-yellow)100%);opacity:0.95;z-index:0;}
        .hero-overlay{position:relative;z-index:2;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:3rem 1rem;color:#fff;}
        .hero-badge{background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);padding:.35rem .6rem;border-radius:9999px;}
        /* Slideshow */
        .slideshow{max-width:1100px;margin:0 auto;position:relative;padding:1rem;}
        .slide{display:none;width:100%;min-height:240px;align-items:center;justify-content:center;background:#f8fafc;border-radius:12px;overflow:hidden;}
        .slide.active{display:flex;}
        .slide img,.slide video{width:100%;height:auto;object-fit:cover;display:block;}
        .slide .slide-text{padding:1.5rem;color:#111827;}
        .slide-controls{display:flex;gap:.5rem;justify-content:center;margin-top:.75rem;}
        .slide-button{background:#fff;border:1px solid rgba(2,6,23,0.06);padding:.5rem .75rem;border-radius:8px;cursor:pointer;}
        /* Facilities */
        .facility-card{transition:transform .24s ease, box-shadow .24s ease;border-radius:12px;background:#fff;padding:1.25rem;border:1px solid rgba(2,6,23,0.06);}
        .facility-card:hover{transform:translateY(-6px);box-shadow:0 10px 30px rgba(2,6,23,0.08);}
        .member-avatar{width:56px;height:56px;border-radius:9999px;background:#e6eef9;display:inline-flex;align-items:center;justify-content:center;color:var(--dilg-navy);font-weight:600;}
        /* Accessibility */
        @media (prefers-reduced-motion: reduce){ .animate-3d{animation:none!important;} }
        @media (max-width:640px){ .hero-overlay h1{font-size:1.6rem;} }
        /* Full-width facility sections (parallax) */
        .facility-full { position: relative; overflow: hidden; min-height: 320px; display: flex; align-items: center; color: #fff; }
        .facility-full .parallax-bg { position: absolute; inset: 0; background-size: cover; background-position: center; will-change: transform; transform: translate3d(0,0,0); filter: brightness(0.85); }
        .facility-full .facility-full-inner { position: relative; z-index: 2; width: 100%; padding: 4rem 1rem; }
        .facility-full .facility-full-inner .container { max-width: 1100px; margin: 0 auto; }
        @media (max-width: 640px) { .facility-full { min-height: 240px; } .facility-full .facility-full-inner { padding: 2rem 1rem; } }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <!-- Navigation -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="flex-shrink-0 flex items-center">
                        <i class="fas fa-landmark text-blue-700 text-2xl mr-2"></i>
                        <span class="font-bold text-xl text-gray-900 tracking-tight">EV LGRRC</span>
                    </span>
                </div>
                <div class="hidden md:flex items-center">
                    <a href="http://kphub.dilgrictu8.com" class="bg-[#8b0b09] text-white px-4 py-2 rounded-md text-sm font-medium hover:brightness-90 transition">Go Back To Main</a>
                </div>
            </div>
        </div>
    </nav>

    <a href="#main" class="skip-link">Skip to content</a>

    <!-- Hero Section -->
    <header id="hero" class="text-white" aria-hidden="false">
        <canvas id="hero-canvas" aria-hidden="true"></canvas>
        <div class="hero-fallback" aria-hidden="true"></div>
        <div class="hero-overlay" role="banner">
            <span class="hero-badge">DILG Region VIII</span>
            <h1 class="text-4xl md:text-6xl font-bold mb-4 leading-tight">Eastern Visayas Local Governance<br/>Regional Resource Center</h1>
            <p class="text-lg md:text-xl max-w-3xl opacity-95 mb-6 font-light">A strategic knowledge hub empowering Local Government Units (LGUs) for peaceful, progressive, and resilient communities by 2040.</p>
            <div class="flex gap-3">
                <a href="#about" class="bg-white text-[#8b0b09] font-bold py-3 px-6 rounded-full hover:brightness-95 transition shadow">Learn More</a>
                <a href="#facilities" class="bg-transparent border border-white text-white py-3 px-6 rounded-full hover:bg-white hover:text-[#8b0b09] transition">Our Facilities</a>
            </div>
        </div>
    </header>

    <!-- About Section -->
    <section id="about" class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">What is EV LGRRC?</h2>
            <div class="prose prose-lg mx-auto text-gray-600 leading-relaxed">
                <p class="mb-4">
                    The EV LGRRC serves as a dynamic platform for knowledge management, multi-stakeholder partnerships, and the convergence of local governance initiatives.
                </p>
                <p>
                    We support the <strong>Department of the Interior and Local Government (DILG) Region VIII</strong> by strengthening LGU capabilities and promoting excellence in local governance through collaboration and resource sharing.
                </p>
            </div>
        </div>
    </section>

    <!-- Full-width Facility Sections (parallax) -->
    <?php
    // Ensure $facilities is defined. Load from JSON fallback if available.
    $facilitiesFile = __DIR__ . '/ev-facilities.json';
    $facilities = [];
    if (file_exists($facilitiesFile)) {
        $json = file_get_contents($facilitiesFile);
        $data = json_decode($json, true);
        if (is_array($data)) {
            $facilities = $data;
        }
    }

    // Provide sensible defaults when no facilities data is present so the page
    // renders without runtime notices. These can be overridden by creating
    // an `ev-facilities.json` file next to this script.
    if (empty($facilities)) {
        $facilities = [
            [
                'title' => 'Decision Support',
                'description' => 'Decision Support: tools and guidance for evidence-based planning and policy.',
                'color' => '#B22222',
            ],
            [
                'title' => 'Knowledge Sharing & Networking',
                'description' => 'Knowledge sharing platforms and opportunities for peer learning and collaboration.',
                'color' => '#FFD700',
            ],
            [
                'title' => 'Learning & Development',
                'description' => 'Capacity building, training resources, and professional development initiatives.',
                'color' => '#1A237E',
            ],
            [
                'title' => 'Strategic Planning & Implementation',
                'description' => 'Support for strategic planning, monitoring, and implementation of local programs.',
                'color' => '#FF8C00',
            ],
        ];
    }

    foreach ($facilities as $f): ?>
        <?php $slug = htmlspecialchars(strtolower(str_replace(' ', '-', $f['title']))); ?>
        <section id="facility-<?= $slug ?>" class="facility-full" aria-labelledby="fac-<?= $slug ?>">
            <div class="parallax-bg" style="background: linear-gradient(135deg, <?= htmlspecialchars($f['color']) ?> 0%, rgba(11,37,69,0.75) 60%);"></div>
            <div class="facility-full-inner">
                <div class="container">
                    <h2 id="fac-<?= $slug ?>" class="text-3xl font-bold"><?= htmlspecialchars($f['title']) ?></h2>
                    <p class="mt-4 max-w-3xl text-white/90"><?= htmlspecialchars($f['desc']) ?></p>
                </div>
            </div>
        </section>
    <?php endforeach; ?>

    <!-- Slideshow -->
    <section id="slideshow" class="py-8 bg-slate-50">
        <div class="slideshow">
            <?php foreach ($slides as $i => $s): ?>
                <div class="slide" data-index="<?= $i ?>" data-duration="<?= intval($s['duration'] ?? 5000) ?>" data-type="<?= htmlspecialchars($s['type'] ?? 'text') ?>" role="group" aria-roledescription="slide">
                    <?php if (!empty($s['type']) && $s['type'] === 'image'): ?>
                        <img data-src="<?= htmlspecialchars($s['src']) ?>" loading="lazy" alt="<?= htmlspecialchars($s['title'] ?? 'slide') ?>">
                    <?php elseif (!empty($s['type']) && $s['type'] === 'video'): ?>
                        <video data-src="<?= htmlspecialchars($s['src']) ?>" muted playsinline preload="none" poster="<?= htmlspecialchars($s['poster'] ?? '') ?>" aria-label="<?= htmlspecialchars($s['title'] ?? 'video slide') ?>"></video>
                    <?php else: ?>
                        <div class="slide-text">
                            <h3 class="text-2xl font-semibold"><?= htmlspecialchars($s['title'] ?? '') ?></h3>
                            <p class="mt-2"><?= htmlspecialchars($s['caption'] ?? '') ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div class="slide-controls" aria-hidden="false">
                <button id="slidePrev" class="slide-button" aria-label="Previous slide">Prev</button>
                <button id="slidePlay" class="slide-button" aria-label="Play/Pause">Play</button>
                <button id="slideNext" class="slide-button" aria-label="Next slide">Next</button>
            </div>
        </div>
    </section>

    <!-- Facilities Section -->
    <section id="facilities" class="py-16 bg-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">Our Facilities</h2>
                <p class="mt-4 text-lg text-gray-600">Specialized units designed to support every aspect of local governance.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Facility template: replicate or generate dynamically from backend -->
                <?php
                $facilities = [
                    ['title'=>'Capacity Development','color'=>'#8b0b09','icon'=>'graduation-cap','desc'=>'Enhances LGU personnel skills and knowledge through rigorous training programs and technical assistance.','members'=>['JR','MK','AS']],
                    ['title'=>'Linkage Facility','color'=>'#f2c94c','icon'=>'handshake','desc'=>'Establishes and maintains strategic partnerships with stakeholders to maximize resource sharing and collaboration.','members'=>['LP','DV']],
                    ['title'=>'Public Education & Citizenship','color'=>'#0b2545','icon'=>'users','desc'=>'Conducts awareness campaigns and citizenship programs to empower citizens and foster participation.','members'=>['PR','SL','JM']],
                    ['title'=>'Multi-Media & Knowledge (MMK)','color'=>'#8b0b09','icon'=>'laptop-code','desc'=>'Manages knowledge resources, disseminates information, and shares best practices through multi-media platforms.','members'=>['MM','KT']],
                    ['title'=>'Institutional & Legal Support','color'=>'#f2c94c','icon'=>'balance-scale','desc'=>'Provides specialized technical assistance on administrative and legal matters.','members'=>['LN','RH']]
                ];
                foreach ($facilities as $f): ?>
                    <div class="facility-card" role="region" aria-labelledby="fac-<?= htmlspecialchars(strtolower(str_replace(' ','-',$f['title']))) ?>">
                        <div class="flex items-start gap-4">
                            <div class="h-12 w-12 rounded-lg flex items-center justify-center" style="background:rgba(11,37,69,0.06);color:<?= htmlspecialchars($f['color']) ?>;">
                                <i class="fas fa-<?= htmlspecialchars($f['icon']) ?> text-lg"></i>
                            </div>
                            <div>
                                <h3 id="fac-<?= htmlspecialchars(strtolower(str_replace(' ','-',$f['title']))) ?>" class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($f['title']) ?></h3>
                                <p class="mt-1 text-sm text-slate-600"><?= htmlspecialchars($f['desc']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Core Services -->
    <section id="services" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900">Core Services Offered</h2>
            </div>

            <div class="space-y-12">
                <div class="flex flex-col md:flex-row items-start gap-6">
                    <div class="flex-shrink-0 bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg">1</div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Multimedia Knowledge and Information</h3>
                        <p class="mt-2 text-gray-600">Management of knowledge products, data processing, and distribution through library services, data management, web administration, and knowledge product development.</p>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row items-start gap-6">
                    <div class="flex-shrink-0 bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg">2</div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Public Education on Good Governance</h3>
                        <p class="mt-2 text-gray-600">Raising public awareness and engagement through comprehensive communication strategies, articles, audio-visual content, and infographics.</p>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row items-start gap-6">
                    <div class="flex-shrink-0 bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg">3</div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Linkage and Networking</h3>
                        <p class="mt-2 text-gray-600">Fostering collaboration among stakeholders, including national agencies, civil society organizations, and academic institutions, to enhance knowledge sharing.</p>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row items-start gap-6">
                    <div class="flex-shrink-0 bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg">4</div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Capacity Development Services</h3>
                        <p class="mt-2 text-gray-600">Providing training, workshops, and seminars to enhance skills in areas like disaster risk reduction, local economic development, and environmental governance.</p>
                    </div>
                </div>

                 <div class="flex flex-col md:flex-row items-start gap-6">
                    <div class="flex-shrink-0 bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg">5</div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Institutional and Legal Support</h3>
                        <p class="mt-2 text-gray-600">Offering administrative, technical, and legal assistance to internal and external clients, ensuring compliance and effective implementation of activities.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="flex justify-center items-center mb-8">
                 <i class="fas fa-landmark text-blue-500 text-2xl mr-2"></i>
                 <span class="text-white font-bold text-xl">EV LGRRC</span>
            </div>

            <!-- Vision & Mission Grid -->
            <div class="grid md:grid-cols-2 gap-8 text-left mb-10 max-w-4xl mx-auto">
                <div class="bg-gray-800 p-6 rounded-lg">
                    <h3 class="text-white font-bold text-lg mb-2 border-b border-gray-600 pb-2">Vision</h3>
                    <p class="text-sm leading-relaxed">A champion and development partner on knowledge management, multi-stakeholdership, and convergence for peaceful, progressive, empowered, and resilient communities by 2040.</p>
                </div>
                <div class="bg-gray-800 p-6 rounded-lg">
                    <h3 class="text-white font-bold text-lg mb-2 border-b border-gray-600 pb-2">Mission</h3>
                    <p class="text-sm leading-relaxed">To uphold integrity and social responsibility that promote collaboration for excellence in local governance.</p>
                </div>
            </div>

            <!-- Hashtags -->
            <div class="text-blue-400 font-semibold tracking-wide mb-6 text-sm md:text-base space-x-2">
                <span>#SerbisyoRehiyonOtso</span>
                <span>#Matino</span>
                <span>#Mahusay</span>
                <span>#Maaasahan</span>
            </div>

            <div class="mt-8 border-t border-gray-800 pt-8 text-xs">
                &copy; 2025 Eastern Visayas Local Governance Regional Resource Center. All rights reserved.
            </div>
        </div>
    </footer>

        <script>
        (function(){
            // HERO: lazy-init Three.js and pause when offscreen
            const heroEl = document.getElementById('hero');
            const canvas = document.getElementById('hero-canvas');
            const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            let heroInitialized = false;
            let renderer, scene, camera, points, rafId = null, running = false, animateFn = null;

            function setupHero(){
                console.debug && console.debug('setupHero');
                try {
                    renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
                    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                    renderer.setSize(canvas.clientWidth, canvas.clientHeight, false);
                    scene = new THREE.Scene();
                    camera = new THREE.PerspectiveCamera(50, canvas.clientWidth / canvas.clientHeight, 0.1, 1000);
                    camera.position.z = 80;
                    const geom = new THREE.BufferGeometry();
                    const count = 800;
                    const positions = new Float32Array(count * 3);
                    for (let i=0;i<count;i++){ positions[i*3+0]=(Math.random()-0.5)*200; positions[i*3+1]=(Math.random()-0.5)*120; positions[i*3+2]=(Math.random()-0.5)*200; }
                    geom.setAttribute('position', new THREE.BufferAttribute(positions,3));
                    const mat = new THREE.PointsMaterial({ size: 1.6, color: 0xffffff, transparent:true, opacity:0.9 });
                    points = new THREE.Points(geom, mat);
                    scene.add(points);
                    window.addEventListener('resize', onResize);
                    let t = 0;
                    animateFn = function(){
                        t += 0.002;
                        points.rotation.y = t*0.6;
                        points.rotation.x = Math.sin(t*0.2)*0.1;
                        renderer.render(scene, camera);
                        rafId = requestAnimationFrame(animateFn);
                    };
                    running = true;
                    animateFn();
                } catch(e) {
                    console.warn('3D hero initialization failed', e);
                    if (heroEl) heroEl.classList.add('hero-fallback-visible');
                }
            }

            function onResize(){ if (renderer && camera) { renderer.setSize(canvas.clientWidth, canvas.clientHeight, false); camera.aspect = canvas.clientWidth / canvas.clientHeight; camera.updateProjectionMatrix(); } }
            function initHero(){
                console.debug && console.debug('initHero');
                if (heroInitialized) return; heroInitialized = true;
                if (!canvas || prefersReduced) { if (heroEl) heroEl.classList.add('hero-fallback-visible'); return; }
                if (typeof THREE === 'undefined') {
                    const s = document.createElement('script');
                    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r152/three.min.js';
                    s.onload = setupHero;
                    s.onerror = function(){ console.warn('Failed to load Three.js'); if (heroEl) heroEl.classList.add('hero-fallback-visible'); };
                    document.head.appendChild(s);
                } else {
                    setupHero();
                }
            }
            function pauseHero(){ running = false; if (rafId) { cancelAnimationFrame(rafId); rafId = null; } }
            function resumeHero(){ if (!heroInitialized) initHero(); if (heroInitialized && !rafId && animateFn) { rafId = requestAnimationFrame(animateFn); } }

            if ('IntersectionObserver' in window && heroEl) {
                const io = new IntersectionObserver((entries)=>{ entries.forEach(ent=>{ console.debug && console.debug('hero visibility', ent.isIntersecting); if (ent.isIntersecting) { resumeHero(); } else { pauseHero(); } }); }, { threshold: 0.05 });
                io.observe(heroEl);
            } else {
                setTimeout(initHero, 600);
            }

            // SLIDESHOW: lazy-load media, pause when offscreen
            const slideshowEl = document.querySelector('.slideshow');
            const slides = Array.from(document.querySelectorAll('.slide'));
            if (!slides.length) return;
            let idx = 0, playing = true, timer = null, slidesVisible = true;
            const durationDefaults = 5000;

            function loadMediaForSlide(index){
                console.debug && console.debug('loadMediaForSlide', index);
                const s = slides[index]; if (!s) return;
                const img = s.querySelector('img[data-src]');
                if (img && !img.src){ img.src = img.dataset.src; img.addEventListener('error', ()=>{ img.style.display='none'; }); }
                const vid = s.querySelector('video[data-src]');
                if (vid && !vid.dataset.loaded){ const src = vid.dataset.src; if (src){ try{ vid.src = src; vid.load(); }catch(e){} vid.dataset.loaded = '1'; } }
            }
            function preloadAdjacent(index){ loadMediaForSlide((index+1)%slides.length); }
            function show(i){
                slides.forEach((s,si)=>{
                    s.classList.toggle('active', si===i);
                    if (si !== i){ const v = s.querySelector('video'); if (v && !v.paused) { try{ v.pause(); v.currentTime = 0; }catch(e){} } }
                });
                loadMediaForSlide(i);
                preloadAdjacent(i);
                const vid = slides[i].querySelector('video'); if (vid && vid.dataset.loaded) { try{ vid.currentTime = 0; vid.play(); }catch(e){} }
            }
            function next(){ idx=(idx+1)%slides.length; show(idx); schedule(); }
            function prev(){ idx=(idx-1+slides.length)%slides.length; show(idx); schedule(); }
            function schedule(){ if (timer) clearTimeout(timer); if (!playing || !slidesVisible) return; const s = slides[idx]; const d = Number(s.dataset.duration) || durationDefaults; timer = setTimeout(next, d); }

            if ('IntersectionObserver' in window && slideshowEl){
                const sio = new IntersectionObserver((entries)=>{ entries.forEach(ent=>{ console.debug && console.debug('slideshow visibility', ent.isIntersecting); slidesVisible = ent.isIntersecting; if (!slidesVisible && timer) { clearTimeout(timer); } if (slidesVisible && playing) schedule(); }); }, { threshold: 0.25 });
                sio.observe(slideshowEl);
            }

            // Attach controls
            console.debug && console.debug('slideshow init slides', slides.length);
            show(idx);
            schedule();
            document.getElementById('slideNext').addEventListener('click', ()=>{ next(); playing=false; });
            document.getElementById('slidePrev').addEventListener('click', ()=>{ prev(); playing=false; });
            document.getElementById('slidePlay').addEventListener('click', ()=>{ playing=!playing; if (playing) schedule(); });

            // PARALLAX: simple, efficient transform-based parallax for facility-full backgrounds
            (function(){
                const prefsReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (prefsReduced) return;
                const sections = Array.from(document.querySelectorAll('.facility-full'));
                if (!sections.length) return;
                function update(){
                    const vh = window.innerHeight;
                    sections.forEach(sec => {
                        const rect = sec.getBoundingClientRect();
                        if (rect.bottom >= 0 && rect.top <= vh) {
                            const rel = ((rect.top + rect.height/2) - vh/2) / vh; // roughly -0.5 .. 0.5
                            const offset = rel * 30; // px
                            const bg = sec.querySelector('.parallax-bg');
                            if (bg) bg.style.transform = `translate3d(0,${offset}px,0)`;
                        }
                    });
                }
                let raf = null;
                function onScroll(){ if (raf) cancelAnimationFrame(raf); raf = requestAnimationFrame(update); }
                update();
                window.addEventListener('scroll', onScroll, { passive: true });
                window.addEventListener('resize', onScroll);
            })();

        })();
        </script>

</body>
</html>