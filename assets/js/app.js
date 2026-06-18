/* ==========================
AOS INIT
========================== */
AOS.init({
    duration: 1000,
    once: true
});

/* ==========================
TESTIMONIAL SWIPER
========================== */
const testimonialEl = document.querySelector('.testimonialSwiper');
if (testimonialEl) {
    const wrapper = testimonialEl.querySelector('.swiper-wrapper');
    let slides = testimonialEl.querySelectorAll('.swiper-slide');
    let slideCount = slides.length;

    // Swiper loop mode requires at least slidesPerView + 1 (often more for smooth looping).
    // If we have fewer than 6 slides, we duplicate them dynamically to prevent Swiper Loop Warnings.
    if (slideCount > 0 && slideCount < 6) {
        slides.forEach(slide => {
            const clone = slide.cloneNode(true);
            wrapper.appendChild(clone);
        });
        // Re-query slides and update count
        slides = testimonialEl.querySelectorAll('.swiper-slide');
        slideCount = slides.length;
    }

    new Swiper('.testimonialSwiper', {
        loop: true,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false
        },
        spaceBetween: 30,
        breakpoints: {
            0:    { slidesPerView: 1 },
            768:  { slidesPerView: Math.min(2, slideCount) },
            1200: { slidesPerView: Math.min(3, slideCount) }
        }
    });
}


/* ==========================
COUNTER ANIMATION (ScrollTrigger)
========================== */
const counters = document.querySelectorAll('.counter');

counters.forEach(counter => {
    const target = +counter.getAttribute('data-target') || 0;
    counter.innerText = '0';

    if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
        ScrollTrigger.create({
            trigger: counter,
            start: 'top 90%',
            once: true,
            onEnter: () => {
                gsap.to({ val: 0 }, {
                    val: target,
                    duration: 2,
                    ease: 'power2.out',
                    onUpdate: function () {
                        counter.innerText = Math.ceil(this.targets()[0].val).toLocaleString();
                    }
                });
            }
        });
    } else {
        // Fallback plain JS counter
        const updateCounter = () => {
            const current = +counter.innerText.replace(/,/g, '');
            const increment = target / 100;
            if (current < target) {
                counter.innerText = Math.ceil(current + increment);
                setTimeout(updateCounter, 20);
            } else {
                counter.innerText = target;
            }
        };
        updateCounter();
    }
});

/* ==========================
GSAP HERO ANIMATION
(only runs on pages that have .hero-content)
========================== */
if (typeof gsap !== 'undefined' && document.querySelector('.hero-content')) {

    // Custom ease for smooth cinematic feel
    if (typeof CustomEase !== 'undefined') {
        CustomEase.create('heroEase', 'M0,0 C0.215,0.61 0.355,1 1,1');
    }
    const heroEase = (typeof CustomEase !== 'undefined') ? 'heroEase' : 'power3.out';

    const heroTl = gsap.timeline({ defaults: { ease: heroEase } });

    heroTl
        .from('.hero-content h5', { y: -30, opacity: 0, duration: 0.8 })
        .from('.hero-content h1',  { y: 50,  opacity: 0, duration: 1   }, '-=0.4')
        .from('.hero-content p',   { y: 50,  opacity: 0, duration: 0.9 }, '-=0.5')
        .from('.hero-buttons',     { y: 50,  opacity: 0, duration: 0.9 }, '-=0.5');

    // Hero cards (right side on index) — only if they exist
    const heroCards = document.querySelectorAll('.hero-card');
    if (heroCards.length > 0) {
        heroTl.from('.hero-card', {
            y: 40,
            opacity: 0,
            duration: 0.7,
            stagger: 0.15,
            ease: 'back.out(1.4)'
        }, '-=0.8');
    }
}

/* ==========================
GSAP SCROLL ANIMATIONS (ScrollTrigger)
Only fires on elements that actually exist on the current page
========================== */
if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {

    // Helper: animate all matching elements in ONE gsap.from() call
    // Temporarily disables CSS transitions during the animation to avoid conflicts,
    // and clears GSAP inline styles on complete to allow hover transitions to work smoothly.
    function animateIfExists(selector, vars) {
        const els = gsap.utils.toArray(selector);
        if (els.length === 0) return;

        gsap.from(els, {
            scrollTrigger: {
                trigger: els[0],   // use first element as trigger
                start: 'top 88%',
                toggleActions: 'play none none none'
            },
            ...vars,
            onStart: () => {
                els.forEach(el => el.style.transition = 'none');
            },
            onComplete: () => {
                els.forEach(el => el.style.transition = '');
                gsap.set(els, { clearProps: 'all' });
            }
        });
    }


    // Feature cards / program cards — staggered pop-in
    animateIfExists('.feature-card', { y: 40, opacity: 0, duration: 0.7, stagger: 0.12, ease: 'power2.out' });
    animateIfExists('.program-card', { y: 40, opacity: 0, duration: 0.7, stagger: 0.12, ease: 'power2.out' });

    // Section titles
    animateIfExists('.section-title h2', { y: 30, opacity: 0, duration: 0.8, ease: 'power3.out' });
    animateIfExists('.section-title p',  { y: 20, opacity: 0, duration: 0.7, ease: 'power2.out' });

    // Page banner heading (inner pages only)
    if (document.querySelector('.page-banner')) {
        const bannerH1 = document.querySelector('.page-banner h1');
        const bannerP = document.querySelector('.page-banner p');
        
        if (bannerH1) {
            gsap.from(bannerH1, {
                scrollTrigger: {
                    trigger: '.page-banner',
                    start: 'top 80%',
                    toggleActions: 'play none none none'
                },
                y: -20, opacity: 0, duration: 0.9, ease: 'power3.out',
                onStart: () => { bannerH1.style.transition = 'none'; },
                onComplete: () => {
                    bannerH1.style.transition = '';
                    gsap.set(bannerH1, { clearProps: 'all' });
                }
            });
        }
        
        if (bannerP) {
            gsap.from(bannerP, {
                scrollTrigger: {
                    trigger: '.page-banner',
                    start: 'top 80%',
                    toggleActions: 'play none none none'
                },
                y: 15, opacity: 0, duration: 0.8, delay: 0.2, ease: 'power2.out',
                onStart: () => { bannerP.style.transition = 'none'; },
                onComplete: () => {
                    bannerP.style.transition = '';
                    gsap.set(bannerP, { clearProps: 'all' });
                }
            });
        }
    }

    // Testimonial cards — scale in with bounce
    animateIfExists('.testimonial-card', { scale: 0.92, opacity: 0, duration: 0.75, ease: 'back.out(1.4)' });

    // Gallery items
    animateIfExists('.gallery-item', { scale: 0.92, opacity: 0, duration: 0.65, ease: 'back.out(1.4)', stagger: 0.07 });

    // Principal / staff image
    animateIfExists('.principal-image', { scale: 0.93, opacity: 0, duration: 0.85, ease: 'power2.out' });

    // News / event cards — alternate left/right
    gsap.utils.toArray('.news-card, .event-card').forEach((card, i) => {
        gsap.from(card, {
            scrollTrigger: { trigger: card, start: 'top 88%', toggleActions: 'play none none none' },
            x: i % 2 === 0 ? -40 : 40,
            opacity: 0,
            duration: 0.75,
            ease: 'power2.out',
            onStart: () => { card.style.transition = 'none'; },
            onComplete: () => {
                card.style.transition = '';
                gsap.set(card, { clearProps: 'all' });
            }
        });
    });

    // Stats cards
    animateIfExists('.stat-card', { y: 30, opacity: 0, duration: 0.7, stagger: 0.1, ease: 'power2.out' });

    // Admission banner CTA
    const admBanner = document.querySelector('.admission-banner');
    if (admBanner) {
        gsap.from(admBanner, {
            scrollTrigger: {
                trigger: '.admission-banner',
                start: 'top 85%',
                toggleActions: 'play none none none'
            },
            scale: 0.95, opacity: 0, duration: 0.9, ease: 'back.out(1.7)',
            onStart: () => { admBanner.style.transition = 'none'; },
            onComplete: () => {
                admBanner.style.transition = '';
                gsap.set(admBanner, { clearProps: 'all' });
            }
        });
    }

    // Footer
    const mainFooter = document.querySelector('footer.footer');
    if (mainFooter) {
        gsap.from(mainFooter, {
            scrollTrigger: {
                trigger: 'footer.footer',
                start: 'top 95%',
                toggleActions: 'play none none none'
            },
            y: 30, opacity: 0, duration: 1, ease: 'power2.out',
            onStart: () => { mainFooter.style.transition = 'none'; },
            onComplete: () => {
                mainFooter.style.transition = '';
                gsap.set(mainFooter, { clearProps: 'all' });
            }
        });
    }
}
