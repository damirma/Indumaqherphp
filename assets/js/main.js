// ========================================
// MAIN.JS — INDUMAQHER FRONTEND
// Script principal: navegación, formularios y portafolio (sin autenticación)
// ========================================

/* =============================
 * CONFIGURACIÓN GLOBAL
 * =============================
 */
const CONFIG = {
    animationDuration: 300,
    scrollOffset: 80,
    API_BASE: window.INDUMAQHER_CONFIG?.API_BASE || 'http://localhost:3000/api',
    swiper: {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true,
        autoplay: { delay: 3000, disableOnInteraction: false }
    }
};

/* =============================
 * UTILIDADES GENERALES
 * =============================
 */
const utils = {
    debounce: (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => { clearTimeout(timeout); func(...args); };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    throttle: (func, limit) => {
        let inThrottle;
        return function () {
            if (!inThrottle) {
                func.apply(this, arguments);
                inThrottle = true;
                setTimeout(() => (inThrottle = false), limit);
            }
        };
    },

    scrollToElement: (elementId, offset = CONFIG.scrollOffset) => {
        const element = document.getElementById(elementId.replace('#', ''));
        if (!element) return;
        const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
        const offsetPosition = elementPosition - offset;
        window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
    },

    animateElement: (el, animationClass, duration = CONFIG.animationDuration) => {
        el.classList.add(animationClass);
        setTimeout(() => el.classList.remove(animationClass), duration);
    },

    validateEmail: (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email),
    validatePhone: (phone) => /^[\+]?[1-9][\d]{0,15}$/.test(phone.replace(/\s/g, '')),

    // Función para generar slug
    generateSlug: (text) => {
        return text
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    },

    // Formatear fecha
    formatDate: (dateString) => {
        return new Date(dateString).toLocaleDateString('es-CO', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
};

/* =============================
 * API CLIENT SIMPLIFICADO
 * =============================
 */
class SimpleAPIClient {
    constructor() {
        this.baseURL = CONFIG.API_BASE;
        this.headers = {
            'Content-Type': 'application/json',
        };
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            headers: this.headers,
            ...options
        };

        try {
            const response = await fetch(url, config);
            
            // Verificar si la respuesta es JSON
            const contentType = response.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                data = { message: await response.text() };
            }
            
            if (!response.ok) {
                throw new Error(data.message || 'Error en la petición');
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    async getMachines(params = {}) {
        try {
            const queryString = new URLSearchParams(params).toString();
            return await this.request(`/machines/public?${queryString}`);
        } catch (error) {
            // Fallback con datos mock si falla la API
            console.warn('API no disponible, usando datos mock');
            return this.getMockMachines();
        }
    }

    async getCategories() {
        try {
            return await this.request('/categories/public');
        } catch (error) {
            console.warn('API no disponible, usando categorías mock');
            return this.getMockCategories();
        }
    }

    async submitInquiry(data) {
        try {
            return await this.request('/inquiries', {
                method: 'POST',
                body: JSON.stringify(data)
            });
        } catch (error) {
            console.warn('API no disponible, simulando envío');
            return this.mockSubmitInquiry(data);
        }
    }

    // Datos mock para cuando la API no esté disponible
    getMockMachines() {
        return {
            success: true,
            data: {
                machines: [
                    {
                        id: 1,
                        name: 'Empacadora Vertical EV-200',
                        slug: 'empacadora-vertical-ev-200',
                        description: 'Empacadora vertical de alta velocidad para productos granulados',
                        short_description: 'Empacadora vertical con control PLC',
                        category_slug: 'empacadoras',
                        category_name: 'Empacadoras',
                        main_image: 'assets/images/machines/placeholder.jpg',
                        status: 'published'
                    },
                    {
                        id: 2,
                        name: 'Selladora Continua SC-150',
                        slug: 'selladora-continua-sc-150',
                        description: 'Selladora continua para empaques flexibles',
                        short_description: 'Selladora profesional con control digital',
                        category_slug: 'selladoras',
                        category_name: 'Selladoras',
                        main_image: 'assets/images/machines/placeholder.jpg',
                        status: 'published'
                    }
                ]
            }
        };
    }

    getMockCategories() {
        return {
            success: true,
            data: {
                categories: [
                    { id: 1, name: 'Empacadoras', slug: 'empacadoras' },
                    { id: 2, name: 'Selladoras', slug: 'selladoras' },
                    { id: 3, name: 'Transportadores', slug: 'transportadores' }
                ]
            }
        };
    }

    mockSubmitInquiry(data) {
        return new Promise(resolve => {
            setTimeout(() => {
                resolve({
                    success: true,
                    message: 'Consulta recibida correctamente (modo demo)'
                });
            }, 1000);
        });
    }
}

/* =============================
 * PRELOADER
 * =============================
 */
class Preloader {
    constructor() {
        this.preloader = document.getElementById('preloader');
        this.init();
    }

    init() {
        window.addEventListener('load', () => {
            setTimeout(() => {
                if (this.preloader) {
                    this.preloader.classList.add('loaded');
                    setTimeout(() => this.preloader?.remove(), 500);
                }
            }, 500);
        });
    }
}

/* =============================
 * NAVEGACIÓN
 * =============================
 */
class Navigation {
    constructor() {
        this.navbar = document.getElementById('navbar');
        this.navMenu = document.getElementById('navmenu');
        this.mobileToggle = document.querySelector('.mobile-nav-toggle');
        this.navLinks = document.querySelectorAll('.nav-link');
        this.isMenuOpen = false;
        this.init();
    }

    init() {
        this.handleScroll();
        this.handleMobileToggle();
        this.handleNavLinks();
        this.setActiveLink();
    }

    handleScroll() {
        const onScroll = utils.throttle(() => {
            if (!this.navbar) return;
            this.navbar.classList.toggle('scrolled', window.scrollY > 100);
        }, 10);
        window.addEventListener('scroll', onScroll);
    }

    handleMobileToggle() {
        if (this.mobileToggle) {
            this.mobileToggle.addEventListener('click', () => this.toggleMobileMenu());
        }

        document.addEventListener('click', (e) => {
            if (this.isMenuOpen && 
                !this.navMenu?.contains(e.target) && 
                !this.mobileToggle?.contains(e.target)) {
                this.closeMobileMenu();
            }
        });
    }

    toggleMobileMenu() {
        this.isMenuOpen = !this.isMenuOpen;
        this.navMenu?.classList.toggle('active');
        
        const icon = this.mobileToggle?.querySelector('i') || this.mobileToggle;
        if (!icon) return;
        
        icon.classList.toggle('fa-bars');
        icon.classList.toggle('fa-times');
    }

    closeMobileMenu() {
        this.isMenuOpen = false;
        this.navMenu?.classList.remove('active');
        
        const icon = this.mobileToggle?.querySelector('i') || this.mobileToggle;
        if (icon) {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    }

    handleNavLinks() {
        this.navLinks.forEach((link) => {
            link.addEventListener('click', (e) => {
                const targetId = link.getAttribute('href') || '';
                if (!targetId.startsWith('#')) return;
                
                e.preventDefault();
                utils.scrollToElement(targetId);
                this.closeMobileMenu();
            });
        });
    }

    setActiveLink() {
        const onScroll = utils.throttle(() => {
            const sections = document.querySelectorAll('section[id]');
            let current = '';
            
            sections.forEach((section) => {
                const top = section.getBoundingClientRect().top;
                if (top <= CONFIG.scrollOffset + 50) current = section.id;
            });
            
            this.navLinks.forEach((link) => {
                link.classList.toggle('active', link.getAttribute('href') === `#${current}`);
            });
        }, 10);
        
        window.addEventListener('scroll', onScroll);
    }
}

/* =============================
 * CAROUSEL DE MARCAS
 * =============================
 */
class BrandsCarousel {
    constructor() {
        this.swiperContainer = document.getElementById('brandsSwiper');
        this.init();
    }

    init() {
        if (this.swiperContainer && typeof Swiper !== 'undefined') {
            try {
                this.swiper = new Swiper('#brandsSwiper', {
                    ...CONFIG.swiper,
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev'
                    },
                    breakpoints: {
                        576: { slidesPerView: 2 },
                        768: { slidesPerView: 3 },
                        992: { slidesPerView: 4 }
                    }
                });
            } catch (error) {
                console.warn('Swiper no disponible o error al inicializar:', error);
            }
        }
    }
}

/* =============================
 * PORTAFOLIO DE MÁQUINAS
 * =============================
 */
class MachinesPortfolio {
    constructor() {
        this.filterBtns = document.querySelectorAll('.filter-btn');
        this.portfolioGrid = document.getElementById('portfolioGrid');
        this.portfolioLoading = document.getElementById('portfolioLoading');
        this.machines = [];
        this.categories = [];
        this.currentFilter = '*';
        this.api = new SimpleAPIClient();
        this.loading = false; // Prevenir múltiples cargas
        this.init();
    }

    init() {
        this.setupFilters();
        this.loadData();
    }

    setupFilters() {
        this.filterBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                const filter = btn.getAttribute('data-filter') || '*';
                this.filterMachines(filter);
                this.setActiveFilter(btn);
            });
        });
    }

    async loadData() {
        if (this.loading) return; // Prevenir cargas múltiples
        
        try {
            this.loading = true;
            this.showLoading(true);
            
            // Solo cargar categorías si no las tenemos
            if (this.categories.length === 0) {
                const categoriesData = await this.api.getCategories();
                if (categoriesData.success) {
                    this.categories = categoriesData.data.categories;
                }
            }
            
            // Cargar máquinas con parámetros básicos
            const machinesData = await this.api.getMachines({ 
                limit: 12,
                status: 'published'
            });
            
            if (machinesData.success) {
                this.machines = machinesData.data.machines;
                this.renderMachines();
            } else {
                this.showError('Error cargando las máquinas');
            }
        } catch (error) {
            console.error('Error loading portfolio:', error);
            this.showError('Error de conexión. Mostrando contenido de ejemplo.');
            
            // Mostrar datos mock como fallback
            const mockData = this.api.getMockMachines();
            this.machines = mockData.data.machines;
            this.renderMachines();
        } finally {
            this.loading = false;
            this.showLoading(false);
        }
    }

    showLoading(show) {
        if (this.portfolioLoading) {
            this.portfolioLoading.style.display = show ? 'block' : 'none';
        }
    }

    showError(message) {
        if (this.portfolioGrid) {
            this.portfolioGrid.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error cargando contenido</h3>
                    <p>${message}</p>
                    <button class="btn btn-primary" onclick="window.machinesPortfolio.loadData()">
                        <i class="fas fa-refresh"></i> Reintentar
                    </button>
                </div>
            `;
        }
    }

    renderMachines() {
        if (!this.portfolioGrid) return;
        
        if (this.machines.length === 0) {
            this.portfolioGrid.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-cogs"></i>
                    <h3>No hay máquinas disponibles</h3>
                    <p>Próximamente estarán disponibles más proyectos.</p>
                </div>
            `;
            return;
        }
        
        this.portfolioGrid.innerHTML = '';
        
        this.machines.forEach((machine) => {
            const machineElement = this.createMachineCard(machine);
            this.portfolioGrid.appendChild(machineElement);
        });
    }

    createMachineCard(machine) {
        const div = document.createElement('div');
        const categorySlug = machine.category_slug || 'other';
        
        // Imagen con placeholder y manejo de errores mejorado
        const imageSrc = machine.main_image && machine.main_image !== '' 
            ? machine.main_image 
            : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0ic2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlbiBubyBkaXNwb25pYmxlPC90ZXh0Pjwvc3ZnPg==';

        div.className = `portfolio-item ${categorySlug}`;
        div.setAttribute('data-aos', 'fade-up');
        
        div.innerHTML = `
            <img src="${imageSrc}" 
                 alt="${machine.name}" 
                 loading="lazy"
                 style="background-color: #f0f0f0;"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="image-placeholder" style="display: none; width: 100%; height: 200px; background: #f0f0f0; align-items: center; justify-content: center; color: #999; font-size: 14px;">
                <div style="text-align: center;">
                    <i class="fas fa-image" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                    Imagen no disponible
                </div>
            </div>
            <div class="portfolio-overlay">
                <div class="portfolio-info">
                    <h4>${machine.name}</h4>
                    <p>${machine.short_description || machine.description.substring(0, 100) + '...'}</p>
                    <a href="#" class="portfolio-link" onclick="window.machinesPortfolio.showMachineDetail('${machine.slug}'); return false;">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            </div>
        `;
        
        return div;
    }

    filterMachines(filter) {
        this.currentFilter = filter;
        const items = this.portfolioGrid?.querySelectorAll('.portfolio-item') || [];
        
        items.forEach((item) => {
            const match = filter === '*' || item.classList.contains(filter.substring(1));
            item.style.display = match ? 'block' : 'none';
            if (match) {
                utils.animateElement(item, 'fade-in-up');
            }
        });
    }

    setActiveFilter(activeBtn) {
        this.filterBtns.forEach((btn) => btn.classList.remove('active'));
        activeBtn.classList.add('active');
    }

    showMachineDetail(slug) {
        // Por ahora, scroll al formulario de contacto
        if (window.contactForm) {
            window.contactForm.prefillForMachine(slug);
        }
        utils.scrollToElement('contact');
    }
}

/* =============================
 * FORMULARIO DE CONTACTO
 * =============================
 */
class ContactForm {
    constructor() {
        this.form = document.getElementById('contactForm');
        this.api = new SimpleAPIClient();
        this.init();
    }

    init() {
        if (!this.form) return;
        
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSubmit();
        });
        
        // Validación en tiempo real
        this.form.querySelectorAll('input, textarea, select').forEach((input) => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearError(input));
        });
    }

    validateField(field) {
        const value = field.value.trim();
        const name = field.name;
        let isValid = true;
        let message = '';
        
        switch (name) {
            case 'name':
                if (value.length < 2) {
                    isValid = false;
                    message = 'El nombre debe tener al menos 2 caracteres';
                }
                break;
                
            case 'email':
                if (!utils.validateEmail(value)) {
                    isValid = false;
                    message = 'Por favor ingresa un email válido';
                }
                break;
                
            case 'phone':
                if (value && !utils.validatePhone(value)) {
                    isValid = false;
                    message = 'Por favor ingresa un teléfono válido';
                }
                break;
                
            case 'message':
                if (value.length < 10) {
                    isValid = false;
                    message = 'El mensaje debe tener al menos 10 caracteres';
                }
                break;
        }
        
        this.showFieldError(field, isValid, message);
        return isValid;
    }

    showFieldError(field, isValid, message) {
        const errorElement = field.parentNode.querySelector('.error-message');
        if (errorElement) {
            errorElement.textContent = isValid ? '' : message;
        }
        
        field.style.borderColor = isValid ? '' : '#dc3545';
    }

    clearError(field) {
        this.showFieldError(field, true, '');
    }

    async handleSubmit() {
        // Validar todos los campos requeridos
        const requiredFields = this.form.querySelectorAll('[required]');
        let isFormValid = true;
        
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isFormValid = false;
            }
        });
        
        if (!isFormValid) {
            this.showMessage('Por favor corrige los errores en el formulario', 'error');
            return;
        }
        
        // Preparar datos
        const formData = new FormData(this.form);
        const data = {
            customer_name: formData.get('name'),
            customer_email: formData.get('email'),
            customer_phone: formData.get('phone') || null,
            customer_company: formData.get('company') || null,
            message: formData.get('message'),
            subject: formData.get('subject') || null
        };
        
        // Mostrar estado de carga
        const submitBtn = this.form.querySelector('button[type="submit"]');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoader = submitBtn.querySelector('.btn-loader');
        
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'block';
        
        try {
            const response = await this.api.submitInquiry(data);
            
            if (response.success) {
                this.showMessage(response.message || 'Mensaje enviado correctamente', 'success');
                this.form.reset();
            } else {
                this.showMessage(response.message || 'Error enviando el mensaje', 'error');
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            this.showMessage('Error de conexión. Por favor intenta más tarde.', 'error');
        } finally {
            submitBtn.disabled = false;
            btnText.style.display = 'inline-flex';
            btnLoader.style.display = 'none';
        }
    }

    prefillForMachine(machineName) {
        if (!this.form) return;
        
        const messageField = this.form.querySelector('#message');
        const subjectField = this.form.querySelector('#subject');
        
        if (messageField) {
            messageField.value = `Hola, me interesa obtener más información sobre: ${machineName}.\n\nPor favor envíenme una cotización detallada.`;
        }
        
        if (subjectField) {
            subjectField.value = 'cotizacion';
        }
        
        // Scroll al formulario
        utils.scrollToElement('contact');
    }

    showMessage(message, type) {
        // Crear toast notification
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        // Estilos inline para el toast
        Object.assign(toast.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '15px 20px',
            borderRadius: '8px',
            color: 'white',
            fontWeight: '500',
            zIndex: '9999',
            opacity: '0',
            transform: 'translateX(100%)',
            transition: 'all 0.3s ease',
            backgroundColor: type === 'success' ? '#28a745' : '#dc3545'
        });
        
        document.body.appendChild(toast);
        
        // Animar entrada
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        }, 100);
        
        // Remover después de 5 segundos
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
}

/* =============================
 * SCROLL TO TOP
 * =============================
 */
class ScrollToTop {
    constructor() {
        this.scrollBtn = document.getElementById('scrollTop');
        this.init();
    }

    init() {
        if (!this.scrollBtn) return;
        
        const onScroll = utils.throttle(() => {
            this.scrollBtn.classList.toggle('show', window.pageYOffset > 300);
        }, 10);
        
        window.addEventListener('scroll', onScroll);
        this.scrollBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
}

/* =============================
 * CONTROLADOR DE ANIMACIONES
 * =============================
 */
class AnimationController {
    constructor() {
        this.init();
    }

    init() {
        // Inicializar AOS si está disponible
        if (typeof AOS !== 'undefined') {
            try {
                AOS.init({
                    duration: 1000,
                    once: true,
                    offset: 100,
                    easing: 'ease-out-cubic'
                });
            } catch (error) {
                console.warn('Error inicializando AOS:', error);
            }
        }
        
        this.initLazyLoading();
        this.initParallaxEffects();
    }

    initLazyLoading() {
        const images = document.querySelectorAll('img[loading="lazy"]');
        if (!('IntersectionObserver' in window)) return;
        
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('loaded');
                    imageObserver.unobserve(entry.target);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }

    initParallaxEffects() {
        const parallaxElements = document.querySelectorAll('.hero-bg');
        if (!parallaxElements.length) return;
        
        const onScroll = utils.throttle(() => {
            const scrolled = window.pageYOffset;
            parallaxElements.forEach(el => {
                el.style.transform = `translateY(${scrolled * -0.5}px)`;
            });
        }, 10);
        
        window.addEventListener('scroll', onScroll);
    }
}

/* =============================
 * APLICACIÓN PRINCIPAL
 * =============================
 */
class IndumaqherApp {
    constructor() {
        this.components = {};
        this.init();
    }

    init() {
        try {
            // Inicializar componentes
            this.components.preloader = new Preloader();
            this.components.navigation = new Navigation();
            this.components.brandsCarousel = new BrandsCarousel();
            this.components.machinesPortfolio = new MachinesPortfolio();
            this.components.contactForm = new ContactForm();
            this.components.scrollToTop = new ScrollToTop();
            this.components.animationController = new AnimationController();
            
            // Configurar eventos globales
            this.setupGlobalEvents();
            
            console.log('🚀 Indumaqher App inicializada correctamente');
        } catch (error) {
            console.error('Error inicializando la aplicación:', error);
        }
    }

    setupGlobalEvents() {
        // Enlaces externos en nueva pestaña
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href^="http"]');
            if (link && !link.hostname.includes(window.location.hostname)) {
                link.setAttribute('target', '_blank');
                link.setAttribute('rel', 'noopener noreferrer');
            }
        });
        
        // Cerrar menú con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.components.navigation?.isMenuOpen) {
                this.components.navigation.closeMobileMenu();
            }
        });
        
        // Refrescar AOS en resize
        window.addEventListener('resize', utils.debounce(() => {
            if (typeof AOS !== 'undefined') {
                try {
                    AOS.refresh();
                } catch (error) {
                    console.warn('Error refrescando AOS:', error);
                }
            }
        }, 250));
    }

    // Exponer componentes para debugging
    getComponent(name) {
        return this.components[name];
    }

    // Recargar portfolio
    async reloadPortfolio() {
        if (this.components.machinesPortfolio) {
            await this.components.machinesPortfolio.loadData();
        }
    }
}

/* =============================
 * INICIALIZACIÓN
 * =============================
 */
document.addEventListener('DOMContentLoaded', () => {
    try {
        // Crear instancia global de la aplicación
        window.IndumaqherApp = new IndumaqherApp();
        
        // Exponer componentes útiles globalmente
        window.machinesPortfolio = window.IndumaqherApp.components.machinesPortfolio;
        window.contactForm = window.IndumaqherApp.components.contactForm;
        window.navigation = window.IndumaqherApp.components.navigation;
        
        console.log('✅ Sistema principal cargado correctamente');
    } catch (error) {
        console.error('❌ Error crítico cargando la aplicación:', error);
    }

    /* =============================
     * LOGIN ADMIN: Mostrar/Ocultar contraseña
     * =============================
     */
    function setupAdminPasswordToggle() {
        const pwdInput = document.getElementById('p');
        const toggleBtn = document.getElementById('toggle-password');
        if (pwdInput && toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                const isPwd = pwdInput.type === 'password';
                pwdInput.type = isPwd ? 'text' : 'password';
                toggleBtn.innerHTML = isPwd
                    ? '<i class="fas fa-eye-slash"></i>'
                    : '<i class="fas fa-eye"></i>';
            });
        }
    }

    setupAdminPasswordToggle();
});

// Exports globales para debugging
window.utils = utils;
window.CONFIG = CONFIG;