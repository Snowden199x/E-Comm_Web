const reduceMotionMQ = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const header = document.getElementById('siteHeader');

// The hero intro fills exactly the first screen (100vh minus the sticky
// nav), so measure the nav's real rendered height and expose it as a CSS
// variable the intro's min-height calc() can use.
function setHeaderHeightVar(){
  document.documentElement.style.setProperty('--header-h', header.offsetHeight + 'px');
}
setHeaderHeightVar();
window.addEventListener('resize', setHeaderHeightVar);

// Sticky header shadow + scroll progress bar, batched per frame
// (the hero blobs now animate continuously via CSS, so no scroll-linked JS needed for them)
const scrollProgressEl = document.getElementById('scrollProgress');
let scrollTicking = false;
function onScrollFrame(){
  const doc = document.documentElement;
  const max = doc.scrollHeight - doc.clientHeight;
  const y = window.scrollY;
  header.classList.toggle('scrolled', y > 12);
  scrollProgressEl.style.width = (max > 0 ? (y / max) * 100 : 0) + '%';
  scrollTicking = false;
}
window.addEventListener('scroll', () => {
  if (!scrollTicking) { requestAnimationFrame(onScrollFrame); scrollTicking = true; }
}, { passive: true });
onScrollFrame();

// Mobile menu
const hamburgerBtn = document.getElementById('hamburgerBtn');
const mobilePanel = document.getElementById('mobilePanel');
hamburgerBtn.addEventListener('click', () => mobilePanel.classList.toggle('open'));
mobilePanel.querySelectorAll('a').forEach(a => a.addEventListener('click', () => mobilePanel.classList.remove('open')));

// Search toggle
const searchToggle = document.getElementById('searchToggle');
const searchBox = document.getElementById('searchBox');
searchToggle.addEventListener('click', () => {
  searchBox.classList.toggle('open');
  if (searchBox.classList.contains('open')) searchBox.querySelector('input').focus();
});

// Cart + toast
let cartCount = 0;
const cartCountEl = document.getElementById('cartCount');
const toast = document.getElementById('toast');
const toastMsg = document.getElementById('toastMsg');
let toastTimer;
function addToCart(name){
  cartCount++;
  cartCountEl.textContent = cartCount;
  toastMsg.textContent = name + ' added to cart';
  toast.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 2200);
}
document.querySelectorAll('.addcart-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    addToCart(btn.dataset.name || 'Item');
    btn.classList.add('added');
    setTimeout(() => btn.classList.remove('added'), 900);
  });
});
document.getElementById('showcaseAdd').addEventListener('click', () => addToCart('Handwoven Market Tote'));

// Product scroller arrows
const scroller = document.getElementById('productScroller');
document.getElementById('scrollLeft').addEventListener('click', () => scroller.scrollBy({left:-270, behavior:'smooth'}));
document.getElementById('scrollRight').addEventListener('click', () => scroller.scrollBy({left:270, behavior:'smooth'}));

// Showcase gallery thumbs — swap the main photo, with active-state highlight
const galleryMainImg = document.getElementById('galleryMainImg');
document.querySelectorAll('.gt').forEach(gt => {
  gt.addEventListener('click', () => {
    document.querySelectorAll('.gt').forEach(x => x.classList.remove('active'));
    gt.classList.add('active');
    const full = gt.dataset.full;
    if (galleryMainImg && full) {
      galleryMainImg.style.display = '';
      galleryMainImg.src = full;
    }
  });
});

// Swatch + size selection
document.querySelectorAll('.swatch-opt').forEach(s => {
  s.addEventListener('click', () => {
    document.querySelectorAll('.swatch-opt').forEach(x => x.classList.remove('active'));
    s.classList.add('active');
  });
});
document.querySelectorAll('.size-opt').forEach(s => {
  s.addEventListener('click', () => {
    document.querySelectorAll('.size-opt').forEach(x => x.classList.remove('active'));
    s.classList.add('active');
  });
});

// Quantity stepper
let qty = 1;
const qtyVal = document.getElementById('qtyVal');
document.getElementById('qtyPlus').addEventListener('click', () => { qty++; qtyVal.textContent = qty; });
document.getElementById('qtyMinus').addEventListener('click', () => { if(qty>1){ qty--; qtyVal.textContent = qty; } });

// Order tracking interactive demo
const trackSteps = document.querySelectorAll('.track-step');
const trackFill = document.getElementById('trackFill');
const trackNoteTitle = document.getElementById('trackNoteTitle');
const trackNoteSub = document.getElementById('trackNoteSub');
const stepInfo = [
  {title:'Your order has been confirmed', sub:'We received your order and payment.'},
  {title:'Your order is being prepared', sub:'The seller is packing your items.'},
  {title:'Your order has been picked up', sub:'The courier has collected your package.'},
  {title:'Your order is in transit', sub:'On its way to your delivery hub.'},
  {title:'Your order is out for delivery', sub:'Arriving today, 2–5 PM.'},
  {title:'Your order has been delivered', sub:"Enjoy! Don't forget to leave feedback."}
];
function setTrackStep(idx){
  trackSteps.forEach(s => {
    const n = Number(s.dataset.step);
    s.classList.remove('done','current');
    if(n < idx) s.classList.add('done');
    else if(n === idx) s.classList.add('current');
  });
  trackFill.style.width = `calc(${(idx/(trackSteps.length-1))*100}% - 20px)`;
  trackNoteTitle.textContent = stepInfo[idx].title;
  trackNoteSub.textContent = stepInfo[idx].sub;
}
trackSteps.forEach(s => s.addEventListener('click', () => setTrackStep(Number(s.dataset.step))));

// Order tracking animates in as it scrolls into view, resets when it scrolls
// out, and replays each time it comes back — either direction
if (reduceMotionMQ) {
  setTrackStep(3);
} else {
  setTrackStep(0);
  const trackPanel = document.querySelector('.track-panel');
  let trackSequenceTimers = [];
  const trackObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      trackSequenceTimers.forEach(t => clearTimeout(t));
      trackSequenceTimers = [];
      if (entry.isIntersecting) {
        const sequence = [0, 1, 2, 3];
        sequence.forEach((step, i) => {
          trackSequenceTimers.push(setTimeout(() => setTrackStep(step), 250 + i * 380));
        });
      } else {
        setTrackStep(0);
      }
    });
  }, { threshold: 0.4 });
  if (trackPanel) trackObserver.observe(trackPanel);
}

// Scroll-triggered reveals — animate in on the way down, back out on the way
// up, and replay every time a section re-enters view from either direction
document.querySelectorAll('.reveal-stagger').forEach(group => {
  Array.from(group.children).forEach((child, i) => {
    if (!group.classList.contains('reveal-stagger-scale')) child.classList.add('reveal');
    child.style.transitionDelay = reduceMotionMQ ? '0ms' : `${Math.min(i * 70, 420)}ms`;
  });
});
if (reduceMotionMQ) {
  document.querySelectorAll('.reveal, .reveal-scale, .reveal-stagger-scale > .step').forEach(el => el.classList.add('in-view'));
} else {
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      entry.target.classList.toggle('in-view', entry.isIntersecting);
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });
  document.querySelectorAll('.reveal, .reveal-scale, .reveal-stagger-scale > .step').forEach(el => revealObserver.observe(el));
}

// Hero parallax — the interface cards tilt gently toward the cursor
const heroVisual = document.getElementById('heroVisual');
const heroVisualInner = document.getElementById('heroVisualInner');
if (heroVisual && !reduceMotionMQ && window.matchMedia('(hover: hover)').matches) {
  heroVisual.addEventListener('mousemove', (e) => {
    const rect = heroVisual.getBoundingClientRect();
    const x = (e.clientX - rect.left) / rect.width - 0.5;
    const y = (e.clientY - rect.top) / rect.height - 0.5;
    heroVisualInner.style.transform = `rotateY(${x * 10}deg) rotateX(${-y * 10}deg) translateZ(10px)`;
  });
  heroVisual.addEventListener('mouseleave', () => {
    heroVisualInner.style.transform = 'rotateY(0deg) rotateX(0deg) translateZ(0)';
  });
}