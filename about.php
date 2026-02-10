<?php
$page_title = "About Us";
include("header.php");
?>

<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fadeInUp {
        opacity: 0;
        animation: fadeInUp 0.8s ease-out forwards;
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        transition: all 0.3s ease;
    }

    .glass-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        border-color: #fb923c;
    }

    .feature-icon {
        background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%);
        color: #ea580c;
    }

    .trust-gradient {
        background: linear-gradient(rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.95)), url('images/trust_feature.png');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
    }

    .step-number {
        background: linear-gradient(135deg, #ea580c 0%, #fb923c 100%);
    }
</style>

<main class="flex-1 px-4 md:px-10 py-10 md:ml-20 mb-16 md:mb-0 max-w-7xl overflow-x-hidden">
    
    <!-- Hero Section -->
    <section class="relative rounded-[2.5rem] overflow-hidden mb-16 animate-fadeInUp shadow-2xl group min-h-[400px] flex items-center">
        <div class="absolute inset-0 z-0">
            <img src="images/about_hero.png" alt="About Hero" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
            <div class="absolute inset-0 bg-gradient-to-r from-orange-600 via-orange-500/40 to-transparent"></div>
        </div>
        
        <div class="relative z-10 px-8 py-20 md:px-20 text-white max-w-3xl">
            <span class="inline-block px-4 py-1.5 bg-white/20 backdrop-blur-md rounded-full text-sm font-bold tracking-widest uppercase mb-6 border border-white/30">Trusted by Thousands</span>
            <h1 class="text-4xl md:text-7xl font-extrabold mb-6 leading-tight drop-shadow-lg italic">The Heart of Our Community</h1>
            <p class="text-xl md:text-2xl font-light opacity-95 leading-relaxed drop-shadow-md">
                Sadhuvandna is more than just an app; it's a digital home for our culture, values, and vibrant social bonds.
            </p>
        </div>
    </section>

    <!-- Mission & Vision Section -->
    <div class="grid md:grid-cols-2 gap-10 mb-20">
        <div class="glass-card p-10 rounded-[2rem] shadow-xl animate-fadeInUp" style="animation-delay: 0.1s;">
            <div class="w-16 h-16 feature-icon rounded-2xl flex items-center justify-center mb-8">
                <i class="fa-solid fa-rocket text-2xl"></i>
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 mb-6">Our Mission</h2>
            <p class="text-gray-600 text-lg leading-relaxed">
                To build an unbreakable digital bridge between members of the Sadhuvandna community, providing every family with tools to connect, share news, manage family details, and find life partners in a safe, verified environment.
            </p>
        </div>

        <div class="glass-card p-10 rounded-[2rem] shadow-xl animate-fadeInUp" style="animation-delay: 0.2s;">
            <div class="w-16 h-16 feature-icon rounded-2xl flex items-center justify-center mb-8">
                <i class="fa-solid fa-crown text-2xl"></i>
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 mb-6">Our Vision</h2>
            <p class="text-gray-600 text-lg leading-relaxed">
                To be the primary global destination for our community where technology honors tradition, ensuring that no member ever feels isolated from their heritage, no matter where they are in the world.
            </p>
        </div>
    </div>

    <!-- How It Works Section -->
    <section class="mb-24">
        <div class="text-center mb-16 animate-fadeInUp">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-4 tracking-tight">How Sadhuvandna Works</h2>
            <p class="text-gray-500 text-lg max-w-2xl mx-auto italic">Everything you need, organized and verified for our community members.</p>
            <div class="w-24 h-1.5 bg-orange-500 mx-auto rounded-full mt-6"></div>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <!-- Step 1 -->
            <div class="relative group animate-fadeInUp" style="animation-delay: 0.3s;">
                <div class="absolute -top-6 -left-4 step-number w-12 h-12 rounded-full text-white font-black flex items-center justify-center text-xl shadow-lg z-10 transition-transform group-hover:scale-110">1</div>
                <div class="glass-card p-8 rounded-[2rem] h-full pt-10">
                    <div class="text-orange-600 mb-4"><i class="fa-solid fa-user-check text-4xl"></i></div>
                    <h3 class="text-xl font-bold mb-3 text-gray-800">Verified Onboarding</h3>
                    <p class="text-gray-500">Every profile goes through a strict verification process to ensure the platform remains exclusive and safe for all families.</p>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="relative group animate-fadeInUp" style="animation-delay: 0.4s;">
                <div class="absolute -top-6 -left-4 step-number w-12 h-12 rounded-full text-white font-black flex items-center justify-center text-xl shadow-lg z-10 transition-transform group-hover:scale-110">2</div>
                <div class="glass-card p-8 rounded-[2rem] h-full pt-10">
                    <div class="text-orange-600 mb-4"><i class="fa-solid fa-share-nodes text-4xl"></i></div>
                    <h3 class="text-xl font-bold mb-3 text-gray-800">Social Connectivity</h3>
                    <p class="text-gray-500">Share stories, read verified news, post comments, and stay updated with real-time notifications about community events.</p>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="relative group animate-fadeInUp" style="animation-delay: 0.5s;">
                <div class="absolute -top-6 -left-4 step-number w-12 h-12 rounded-full text-white font-black flex items-center justify-center text-xl shadow-lg z-10 transition-transform group-hover:scale-110">3</div>
                <div class="glass-card p-8 rounded-[2rem] h-full pt-10">
                    <div class="text-orange-600 mb-4"><i class="fa-solid fa-comments text-4xl"></i></div>
                    <h3 class="text-xl font-bold mb-3 text-gray-800">Direct Interaction</h3>
                    <p class="text-gray-500">Use our high-quality audio and video call features to speak with families and members directly within the application.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Trust Us section with image -->
    <section class="trust-gradient rounded-[3rem] p-12 md:p-20 text-white mb-24 relative overflow-hidden animate-fadeInUp shadow-inner" style="animation-delay: 0.6s;">
        <div class="grid md:grid-cols-2 gap-16 items-center">
            <div class="relative z-10">
                <span class="text-orange-400 font-bold tracking-widest uppercase text-sm mb-4 block">Our Foundation</span>
                <h2 class="text-4xl md:text-5xl font-black mb-8 leading-tight">Built on a Foundation of Trust</h2>
                <div class="space-y-8 text-gray-200">
                    <div class="flex gap-6 items-start">
                        <div class="mt-1 flex-shrink-0 w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                            <i class="fa-solid fa-lock text-orange-400"></i>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-white mb-1">Encrypted Communication</h4>
                            <p class="text-sm opacity-80 leading-relaxed">Your chats and calls are private. We prioritize data security and member privacy above everything else.</p>
                        </div>
                    </div>
                    <div class="flex gap-6 items-start">
                        <div class="mt-1 flex-shrink-0 w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                            <i class="fa-solid fa-user-shield text-orange-400"></i>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-white mb-1">Strict Moderation</h4>
                            <p class="text-sm opacity-80 leading-relaxed">Our dedicated team monitor reports and ensures the community remains respectful and professional.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4 relative z-10">
                <div class="glass-card border-white/10 bg-white/5 p-8 rounded-[2rem] text-center backdrop-blur-md">
                    <div class="text-4xl font-extrabold text-orange-500 mb-2">100%</div>
                    <div class="text-xs uppercase tracking-widest font-bold opacity-60">Verified Profiles</div>
                </div>
                <div class="glass-card border-white/10 bg-white/5 p-8 rounded-[2rem] text-center backdrop-blur-md">
                    <div class="text-4xl font-extrabold text-orange-500 mb-2">24/7</div>
                    <div class="text-xs uppercase tracking-widest font-bold opacity-60">System Monitoring</div>
                </div>
                <div class="glass-card border-white/10 bg-white/5 p-8 rounded-[2rem] text-center backdrop-blur-md">
                    <div class="text-4xl font-extrabold text-orange-500 mb-2">Secure</div>
                    <div class="text-xs uppercase tracking-widest font-bold opacity-60">Private Servers</div>
                </div>
                <div class="glass-card border-white/10 bg-white/5 p-8 rounded-[2rem] text-center backdrop-blur-md">
                    <div class="text-4xl font-extrabold text-orange-500 mb-2">Direct</div>
                    <div class="text-xs uppercase tracking-widest font-bold opacity-60">Support Line</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Detailed Features grid -->
    <div class="text-center mb-16 animate-fadeInUp">
        <h2 class="text-4xl font-extrabold text-gray-900 mb-4">Core Application Features</h2>
        <div class="w-24 h-1.5 bg-orange-500 mx-auto rounded-full mt-4"></div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-24">
        <div class="stat-card bg-orange-50 p-8 rounded-3xl text-center shadow-sm animate-fadeInUp hover:bg-orange-100/50" style="animation-delay: 0.1s;">
            <i class="fa-solid fa-newspaper text-3xl text-orange-600 mb-6 block"></i>
            <h3 class="font-bold text-gray-800 text-lg">Daily News</h3>
            <p class="text-xs text-gray-500 mt-2">Verified community updates and alerts.</p>
        </div>
        <div class="stat-card bg-orange-50 p-8 rounded-3xl text-center shadow-sm animate-fadeInUp hover:bg-orange-100/50" style="animation-delay: 0.2s;">
            <i class="fa-solid fa-ring text-3xl text-orange-600 mb-6 block"></i>
            <h3 class="font-bold text-gray-800 text-lg">Matrimony</h3>
            <p class="text-xs text-gray-500 mt-2">Advanced filters for marriage matches.</p>
        </div>
        <div class="stat-card bg-orange-50 p-8 rounded-3xl text-center shadow-sm animate-fadeInUp hover:bg-orange-100/50" style="animation-delay: 0.3s;">
            <i class="fa-solid fa-phone-volume text-3xl text-orange-600 mb-6 block"></i>
            <h3 class="font-bold text-gray-800 text-lg">AV Calling</h3>
            <p class="text-xs text-gray-500 mt-2">Crystal clear audio and video calls.</p>
        </div>
        <div class="stat-card bg-orange-50 p-8 rounded-3xl text-center shadow-sm animate-fadeInUp hover:bg-orange-100/50" style="animation-delay: 0.4s;">
            <i class="fa-solid fa-briefcase text-3xl text-orange-600 mb-6 block"></i>
            <h3 class="font-bold text-gray-800 text-lg">Job Board</h3>
            <p class="text-xs text-gray-500 mt-2">Exclusive career opportunities for members.</p>
        </div>
    </div>

    <!-- Final CTA -->
    <div class="text-center animate-fadeInUp bg-orange-600 py-16 px-10 rounded-[3rem] text-white shadow-2xl relative overflow-hidden" style="animation-delay: 0.5s;">
         <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
         <h2 class="text-3xl md:text-5xl font-black mb-8 leading-tight">Become a Part of Something Bigger</h2>
         <p class="text-xl opacity-90 mb-10 max-w-2xl mx-auto">Join the Sadhuvandna community today and start connecting with verified members instantly.</p>
         <div class="flex flex-wrap justify-center gap-6">
             <a href="index.php" class="px-10 py-4 bg-white text-orange-600 font-black rounded-full shadow-xl hover:bg-orange-50 transition transform hover:scale-105">
                 Get Started Now
             </a>
             <a href="#" class="px-10 py-4 bg-orange-700 text-white font-black rounded-full shadow-xl hover:bg-orange-800 transition transform hover:scale-105">
                 Contact Support
             </a>
         </div>
    </div>

</main>

<script>
    // Smooth reveal observer
    const observerOptions = { threshold: 0.15 };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fadeInUp');
                entry.target.style.opacity = "1";
            }
        });
    }, observerOptions);

    document.querySelectorAll('.animate-fadeInUp').forEach(el => {
        el.style.opacity = "0";
        observer.observe(el);
    });
</script>

<?php echo "</div>"; ?>
</body>
</html>