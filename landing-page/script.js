// Navigation Mobile Toggle
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    hamburger.addEventListener('click', function() {
        navMenu.classList.toggle('active');
        hamburger.classList.toggle('active');
    });
    
    // Close mobile menu when clicking on a link
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('active');
            hamburger.classList.remove('active');
        });
    });
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Navbar background on scroll
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.style.background = 'rgba(255, 255, 255, 0.98)';
        navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
    } else {
        navbar.style.background = 'rgba(255, 255, 255, 0.95)';
        navbar.style.boxShadow = 'none';
    }
});

// Animate elements on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe all feature cards and other elements
document.querySelectorAll('.feature-card, .step, .pricing-card').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(30px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(el);
});

// WhatsApp link functionality
function openWhatsApp() {
    const phoneNumber = '+14155238886';
    const message = 'join sell-she';
    const whatsappURL = `https://wa.me/${phoneNumber.replace('+', '')}?text=${encodeURIComponent(message)}`;
    window.open(whatsappURL, '_blank');
}

// Add click event to WhatsApp buttons
document.querySelectorAll('.btn-primary').forEach(btn => {
    if (btn.textContent.includes('WhatsApp') || btn.textContent.includes('Commencer')) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            openWhatsApp();
        });
    }
});

// Pricing card hover effects
document.querySelectorAll('.pricing-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-8px)';
        this.style.boxShadow = '0 20px 40px rgba(37, 211, 102, 0.2)';
    });
    
    card.addEventListener('mouseleave', function() {
        if (!this.classList.contains('featured')) {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        }
    });
});

// Demo QR code click functionality
document.querySelector('.qr-code').addEventListener('click', function() {
    openWhatsApp();
});

// Copy WhatsApp number to clipboard
document.querySelector('.whatsapp-number').addEventListener('click', function() {
    const phoneNumber = '+14155238886';
    navigator.clipboard.writeText(phoneNumber).then(function() {
        // Show temporary feedback
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fas fa-check"></i> Numéro copié !';
        this.style.background = '#22c55e';
        
        setTimeout(() => {
            this.innerHTML = originalText;
            this.style.background = '#25d366';
        }, 2000);
    }.bind(this));
});

// Typing animation for hero title
function typeWriter(element, text, speed = 100) {
    let i = 0;
    element.innerHTML = '';
    
    function type() {
        if (i < text.length) {
            element.innerHTML += text.charAt(i);
            i++;
            setTimeout(type, speed);
        }
    }
    
    type();
}

// Initialize typing animation when page loads
window.addEventListener('load', function() {
    const heroTitle = document.querySelector('.hero-title');
    const originalText = heroTitle.textContent;
    
    // Start typing animation after a short delay
    setTimeout(() => {
        typeWriter(heroTitle, originalText, 50);
    }, 500);
});

// Add floating animation to phone mockup
function addFloatingAnimation() {
    const phoneMockup = document.querySelector('.phone-mockup');
    if (phoneMockup) {
        phoneMockup.style.animation = 'float 3s ease-in-out infinite';
    }
}

// Add CSS for floating animation
const style = document.createElement('style');
style.textContent = `
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    .nav-menu.active {
        display: flex !important;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: white;
        flex-direction: column;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .hamburger.active span:nth-child(1) {
        transform: rotate(-45deg) translate(-5px, 6px);
    }
    
    .hamburger.active span:nth-child(2) {
        opacity: 0;
    }
    
    .hamburger.active span:nth-child(3) {
        transform: rotate(45deg) translate(-5px, -6px);
    }
`;
document.head.appendChild(style);

// Initialize floating animation
addFloatingAnimation();

// Form validation for contact (if added later)
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Statistics counter animation
function animateCounter(element, target, duration = 2000) {
    let start = 0;
    const increment = target / (duration / 16);
    
    function updateCounter() {
        start += increment;
        if (start < target) {
            element.textContent = Math.floor(start);
            requestAnimationFrame(updateCounter);
        } else {
            element.textContent = target;
        }
    }
    
    updateCounter();
}

// Animate stats when they come into view
const statsObserver = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const statNumber = entry.target.querySelector('.stat-number');
            const text = statNumber.textContent;
            
            if (text.includes('K')) {
                const number = parseInt(text.replace('K+', ''));
                animateCounter(statNumber, number * 1000);
                statNumber.textContent = number + 'K+';
            } else if (text.includes('%')) {
                const number = parseFloat(text.replace('%', ''));
                animateCounter(statNumber, number);
                statNumber.textContent = number + '%';
            }
            
            statsObserver.unobserve(entry.target);
        }
    });
});

document.querySelectorAll('.stat').forEach(stat => {
    statsObserver.observe(stat);
});

// Add Easter egg - Konami code
let konamiCode = [];
const konamiSequence = [
    'ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown',
    'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight',
    'KeyB', 'KeyA'
];

document.addEventListener('keydown', function(e) {
    konamiCode.push(e.code);
    
    if (konamiCode.length > konamiSequence.length) {
        konamiCode.shift();
    }
    
    if (konamiCode.join(',') === konamiSequence.join(',')) {
        // Easter egg activated!
        document.body.style.filter = 'hue-rotate(180deg)';
        setTimeout(() => {
            document.body.style.filter = 'none';
        }, 3000);
        
        konamiCode = [];
    }
});

console.log('🤖 PDFBot Landing Page chargée avec succès!');
console.log('💡 Conseil: Essayez le code Konami pour une surprise!');
