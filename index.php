<?php
// Index con estructura original, sin "backend en JS" y con acceso discreto.
// Sesión para mostrar estado y generar CSRF si está disponible.
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Cargar generador de CSRF si existe en /admin/csrf.php
$csrf = '';
$csrf_file = __DIR__ . '/admin/csrf.php';
if (is_file($csrf_file)) {
  require $csrf_file;
  if (function_exists('csrf_token')) {
    $csrf = htmlspecialchars(csrf_token(), ENT_QUOTES);
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indumaqher - Soluciones Industriales de Alta Productividad</title>
    <meta name="description" content="Diseñamos, fabricamos e instalamos soluciones en acero inoxidable y control industrial que aumentan el rendimiento y reducen costos operativos.">
    <meta name="keywords" content="maquinaria industrial, acero inoxidable, empacadoras, selladoras, transportadores, control industrial, Bogotá, Colombia">
    <meta name="author" content="Indumaqher">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://indumaqher.com/">
    <meta property="og:title" content="Indumaqher - Soluciones Industriales">
    <meta property="og:description" content="Diseñamos, fabricamos e instalamos soluciones en acero inoxidable y control industrial">
    <meta property="og:image" content="https://indumaqher.com/assets/images/og-image.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://indumaqher.com/">
    <meta property="twitter:title" content="Indumaqher - Soluciones Industriales">
    <meta property="twitter:description" content="Diseñamos, fabricamos e instalamos soluciones en acero inoxidable y control industrial">
    <meta property="twitter:image" content="https://indumaqher.com/assets/images/og-image.jpg">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/apple-touch-icon.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS (Animate On Scroll) -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/main.css">

    <!-- Seguridad básica del lado cliente -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <!-- Añadimos 'unsafe-inline' en script-src para permitir el JS inline del acceso discreto -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' https:; img-src 'self' https: data:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' https:; font-src 'self' https:; connect-src 'self' https:; form-action 'self'">
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Indumaqher",
        "alternateName": "Indumaqher SAS",
        "url": "https://indumaqher.com",
        "logo": "https://indumaqher.com/assets/images/logo.png",
        "description": "Diseñamos, fabricamos e instalamos soluciones en acero inoxidable y control industrial",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "Bogotá",
            "addressCountry": "CO"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+57-300-123-4567",
            "contactType": "customer service",
            "email": "info@indumaqher.com"
        },
        "sameAs": [
            "https://www.facebook.com/indumaqher",
            "https://www.linkedin.com/company/indumaqher",
            "https://www.instagram.com/indumaqher"
        ]
    }
    </script>

    <!-- Estilos mínimos para el modal (si tu CSS no lo incluye ya) -->
    <style>
      .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.8);backdrop-filter:blur(8px);z-index:9999}
      .modal.show{display:flex}
      .modal.hidden{display:none}
      .modal .modal-content{background:#1a1f26;border:1px solid #2d3441;border-radius:16px;padding:2rem;max-width:420px;width:90%;box-shadow:0 25px 50px rgba(0,0,0,.35)}
      .modal .modal-header{display:flex;align-items:center;justify-content:space-between;color:#0bcf8e;border-bottom:1px solid #2d3441;margin-bottom:1rem}
      .modal .form-group{margin:.9rem 0}
      .modal .form-group input, .modal .form-group select, .modal .form-group textarea{width:100%;padding:1rem;background:#252b35;border:2px solid #2d3441;border-radius:8px;color:#f4f6fa}
      .login-actions{display:flex;gap:1rem;margin-top:1rem}
      .login-actions .btn{flex:1;padding:1rem 1.25rem;border-radius:8px;border:none;cursor:pointer;font-weight:600}
      .login-actions .btn-secondary{background:#2d3441;color:#a8b2c1}
      .login-actions .btn-primary{background:linear-gradient(135deg,#0bcf8e 0%,#0ab378 100%);color:#fff}
    </style>
</head>
<body>
    <!-- Skip Link para accesibilidad -->
    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>
    
    <!-- Preloader -->
    <div id="preloader">
        <div class="preloader-spinner"></div>
    </div>

    <!-- Topbar -->
    <div class="topbar">
        <div class="container">
            <div class="topbar-content">
                <span><i class="fas fa-phone"></i> +57 300 123 4567</span>
                <span><i class="fas fa-envelope"></i> info@indumaqher.com</span>
                <span><i class="fas fa-map-marker-alt"></i> Bogotá, Colombia</span>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="navbar" id="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="#home" class="site-logo" id="main-logo">
                    <div class="mark"></div>
                    <span>Indumaqher</span>
                </a>
                
                <nav class="navbar-nav" id="navmenu">
                    <a href="#home" class="nav-link active">Inicio</a>
                    <a href="#features" class="nav-link">Ventajas</a>
                    <a href="#brands" class="nav-link">Marcas</a>
                    <a href="#services" class="nav-link">Servicios</a>
                    <a href="#portfolio" class="nav-link">Portafolio</a>
                    <a href="#contact" class="nav-link">Contacto</a>
                </nav>
                
                <button class="mobile-nav-toggle" aria-label="Menú móvil">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main id="main-content">
        <!-- Hero Section -->
        <section id="home" class="hero">
            <div class="hero-bg"></div>
            <div class="container">
                <div class="hero-content" data-aos="fade-up" data-aos-delay="200">
                    <div class="hero-text">
                        <span class="eyebrow">Innovación en empaque</span>
                        <h1>Soluciones industriales de alta productividad</h1>
                        <p class="hero-description">
                            Diseñamos, fabricamos e instalamos soluciones en acero inoxidable y control industrial que aumentan el rendimiento y reducen costos operativos.
                        </p>
                        <div class="hero-actions">
                            <a href="#contact" class="btn btn-primary btn-large">
                                <i class="fas fa-rocket"></i>
                                Solicitar Cotización
                            </a>
                            <a href="#portfolio" class="btn btn-secondary btn-large">
                                <i class="fas fa-play"></i>
                                Ver Proyectos
                            </a>
                            <!-- Sin botón visible de dashboard/login -->
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features">
            <div class="container">
                <div class="features-grid">
                    <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3>10+ años</h3>
                        <p>Experiencia en industria alimentaria y farmacéutica.</p>
                    </div>
                    
                    <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3>Soporte 24/7</h3>
                        <p>Mantenimiento, repuestos y asesoría remota.</p>
                    </div>
                    
                    <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                        <div class="feature-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <h3>Calidad en acero</h3>
                        <p>Estructuras y piezas en acero inoxidable certificados.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Brands Section -->
        <section id="brands" class="section">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Nuestras líneas</h2>
                    <div class="divider-yellow"></div>
                    <p>Distribuidores y marcas asociadas</p>
                </div>
                
                <div class="brands-carousel" data-aos="fade-up" data-aos-delay="200">
                    <div class="swiper" id="brandsSwiper">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <div class="brand-item">
                                    <img src="assets/images/brands/siemens.png" alt="Siemens" loading="lazy">
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="brand-item">
                                    <img src="assets/images/brands/schneider.png" alt="Schneider Electric" loading="lazy">
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="brand-item">
                                    <img src="assets/images/brands/omron.png" alt="Omron" loading="lazy">
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="brand-item">
                                    <img src="assets/images/brands/abb.png" alt="ABB" loading="lazy">
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="brand-item">
                                    <img src="assets/images/brands/mitsubishi.png" alt="Mitsubishi" loading="lazy">
                                </div>
                            </div>
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section id="services" class="section section-dark">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Servicios</h2>
                    <div class="divider-yellow"></div>
                </div>
                
                <div class="services-grid">
                    <div class="service-card" data-aos="fade-up" data-aos-delay="100">
                        <div class="service-icon">
                            <i class="fas fa-drafting-compass"></i>
                        </div>
                        <h3>Diseño Industrial</h3>
                        <p>Desarrollo de soluciones personalizadas para tu industria con tecnología de vanguardia.</p>
                        <a href="#contact" class="service-link">
                            Solicitar cotización <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="service-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="service-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h3>Fabricación</h3>
                        <p>Manufactura de precisión en acero inoxidable certificado con los más altos estándares.</p>
                        <a href="#contact" class="service-link">
                            Solicitar cotización <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="service-card" data-aos="fade-up" data-aos-delay="300">
                        <div class="service-icon">
                            <i class="fas fa-wrench"></i>
                        </div>
                        <h3>Instalación</h3>
                        <p>Montaje profesional y puesta en marcha de equipos con soporte técnico especializado.</p>
                        <a href="#contact" class="service-link">
                            Solicitar cotización <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Portfolio Section -->
        <section id="portfolio" class="section">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Portafolio</h2>
                    <div class="divider-yellow"></div>
                    <p>Proyectos destacados</p>
                </div>
                
                <!-- Portfolio Filters -->
                <div class="portfolio-filters" data-aos="fade-up" data-aos-delay="100">
                    <button class="filter-btn active" data-filter="*">Todas</button>
                    <button class="filter-btn" data-filter=".empacadoras">Empacadoras</button>
                    <button class="filter-btn" data-filter=".selladoras">Selladoras</button>
                    <button class="filter-btn" data-filter=".transportadores">Transportadores</button>
                    <button class="filter-btn" data-filter=".control">Control</button>
                </div>
                
                <!-- Portfolio Grid -->
                <div class="portfolio-grid" id="portfolioGrid" data-aos="fade-up" data-aos-delay="200">
                    <!-- Loading State -->
                    <div class="portfolio-loading" id="portfolioLoading">
                        <div class="preloader-spinner"></div>
                        <p>Cargando proyectos...</p>
                    </div>
                </div>
                
                <!-- Ver más proyectos -->
                <div class="text-center" style="margin-top: 3rem;" data-aos="fade-up" data-aos-delay="300">
                    <a href="#contact" class="btn btn-primary">
                        <i class="fas fa-eye"></i>
                        Ver más proyectos
                    </a>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content" data-aos="fade-up">
                    <h2>¿Listo para mejorar tu producción?</h2>
                    <p>Solicita una visita técnica y recibe una cotización sin compromiso.</p>
                    <a href="#contact" class="btn btn-primary btn-large">
                        <i class="fas fa-rocket"></i>
                        Solicitar Cotización
                    </a>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="section">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Contacto</h2>
                    <div class="divider-yellow"></div>
                </div>
                
                <div class="contact-grid">
                    <div class="contact-info" data-aos="fade-up" data-aos-delay="100">
                        <h3>Información de contacto</h3>
                        
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <strong>Teléfono:</strong>
                                <a href="tel:+573001234567">+57 300 123 4567</a>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Email:</strong>
                                <a href="mailto:info@indumaqher.com">info@indumaqher.com</a>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>Ubicación:</strong>
                                Bogotá, Colombia
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Horario de atención:</strong>
                                Lunes a Viernes: 8:00 AM - 6:00 PM<br>
                                Sábados: 8:00 AM - 12:00 PM
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-form" data-aos="fade-up" data-aos-delay="200">
                        <h3>Envíanos un mensaje</h3>
                        
                        <form id="contactForm" novalidate>
                            <div class="form-group">
                                <label for="name">Nombre completo *</label>
                                <input type="text" id="name" name="name" required>
                                <span class="error-message"></span>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required>
                                <span class="error-message"></span>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Teléfono</label>
                                <input type="tel" id="phone" name="phone">
                                <span class="error-message"></span>
                            </div>
                            
                            <div class="form-group">
                                <label for="company">Empresa</label>
                                <input type="text" id="company" name="company">
                                <span class="error-message"></span>
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">Asunto</label>
                                <select id="subject" name="subject">
                                    <option value="">Selecciona un asunto</option>
                                    <option value="cotizacion">Solicitud de cotización</option>
                                    <option value="empacadoras">Empacadoras</option>
                                    <option value="selladoras">Selladoras</option>
                                    <option value="transportadores">Transportadores</option>
                                    <option value="control">Control Industrial</option>
                                    <option value="mantenimiento">Mantenimiento</option>
                                    <option value="otro">Otro</option>
                                </select>
                                <span class="error-message"></span>
                            </div>
                            
                            <div class="form-group">
                                <label for="message">Mensaje *</label>
                                <textarea id="message" name="message" required placeholder="Describe tu proyecto o necesidad..."></textarea>
                                <span class="error-message"></span>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-large">
                                <span class="btn-text">
                                    <i class="fas fa-paper-plane"></i>
                                    Enviar mensaje
                                </span>
                                <div class="btn-loader">
                                    <div class="preloader-spinner"></div>
                                </div>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="site-logo">
                        <div class="mark"></div>
                        <span>Indumaqher</span>
                    </div>
                    <p class="footer-desc">
                        Soluciones industriales de alta productividad. Diseñamos, fabricamos e instalamos equipos en acero inoxidable y sistemas de control industrial.
                    </p>
                    <div class="social-links">
                        <a href="https://facebook.com/indumaqher" target="_blank" rel="noopener" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://linkedin.com/company/indumaqher" target="_blank" rel="noopener" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="https://instagram.com/indumaqher" target="_blank" rel="noopener" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://youtube.com/indumaqher" target="_blank" rel="noopener" aria-label="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-nav">
                    <h4>Navegación</h4>
                    <ul>
                        <li><a href="#home">Inicio</a></li>
                        <li><a href="#features">Ventajas</a></li>
                        <li><a href="#services">Servicios</a></li>
                        <li><a href="#portfolio">Portafolio</a></li>
                        <li><a href="#contact">Contacto</a></li>
                    </ul>
                </div>
                
                <div class="footer-contact">
                    <h4>Contacto</h4>
                    <p>
                        <i class="fas fa-phone"></i>
                        +57 300 123 4567
                    </p>
                    <p>
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:info@indumaqher.com">info@indumaqher.com</a>
                    </p>
                    <p>
                        <i class="fas fa-map-marker-alt"></i>
                        Bogotá, Colombia
                    </p>
                </div>
            </div>
            
            <hr class="footer-sep">
            
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Indumaqher. Todos los derechos reservados.</p>
                <div class="footer-meta">
                    <p>Desarrollado con 💛 en Colombia</p>
                    <!-- Sin enlaces visibles de login ni dashboard -->
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scrollTop" aria-label="Volver arriba">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- WhatsApp Float Button -->
    <a href="https://wa.me/573001234567?text=Hola%20Indumaqher,%20me%20interesa%20conocer%20sus%20servicios" 
       class="whatsapp-float" 
       target="_blank" 
       rel="noopener"
       aria-label="Contactar por WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Login Modal (oculto). Envía POST a PHP con CSRF. -->
    <div id="login-modal" class="modal hidden" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true" aria-label="Acceso Administrativo">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-shield-alt"></i>
                    Acceso Administrativo
                </h2>
                <button type="button" class="modal-close" aria-label="Cerrar" onclick="document.getElementById('login-modal').classList.add('hidden')">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="login-form" method="post" action="admin/login.php" autocomplete="on">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <div class="form-group">
                    <label for="admin-username">
                        <i class="fas fa-user"></i>
                        Usuario
                    </label>
                    <input type="text" id="admin-username" name="username" required autocomplete="username">
                </div>
                
                <div class="form-group" style="position: relative;">
                    <label for="admin-password">
                        <i class="fas fa-lock"></i>
                        Contraseña
                    </label>
                    <input type="password" id="admin-password" name="password" required autocomplete="current-password">
                    <button type="button" id="toggle-password" tabindex="-1" style="position:absolute; right:10px; top:38px; background:none; border:none; color:#888; cursor:pointer;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <div class="login-actions">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('login-modal').classList.add('hidden')">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        Ingresar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification Container (si no lo usas en otras partes, puedes quitarlo) -->
    <div id="notification-container"></div>

    <!-- Scripts -->
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    
    <!-- AOS JS -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    
    <!-- Custom JavaScript principal del sitio -->
    <script src="assets/js/main.js"></script>

    <!-- Config global del sitio (sin credenciales ni endpoints de auth) + Acceso discreto -->
    <script>
      window.INDUMAQHER_CONFIG = {
        API_BASE: 'http://localhost:3001/api',
        COMPANY: {
          name: 'Indumaqher',
          phone: '+57 300 123 4567',
          email: 'info@indumaqher.com',
          whatsapp: '573001234567'
        }
      };

      // Mostrar/ocultar contraseña en el modal (solo UI)
      document.addEventListener('DOMContentLoaded', () => {
        const pwdInput = document.getElementById('admin-password');
        const toggleBtn = document.getElementById('toggle-password');
        if (pwdInput && toggleBtn) {
          toggleBtn.addEventListener('click', () => {
            const isPwd = pwdInput.type === 'password';
            pwdInput.type = isPwd ? 'text' : 'password';
            toggleBtn.innerHTML = isPwd
              ? '<i class="fas fa-eye-slash"></i>'
              : '<i class="fas fa-eye"></i>';
          });
        }
      });

      // ---- Acceso discreto: solo abre el modal, sin validar en cliente ----
      (function () {
        const modal = document.getElementById('login-modal');
        const logo  = document.getElementById('main-logo');
        if (!modal || !logo) return;

        function openModal() {
          modal.classList.remove('hidden');
          modal.classList.add('show');
          const u = document.getElementById('admin-username');
          if (u) setTimeout(() => u.focus(), 50);
        }

        // Triple clic rápido en el logo (≤ 1500 ms)
        let clicks = 0, firstTs = 0;
        logo.addEventListener('click', (e) => {
          const now = Date.now();
          if (!firstTs) firstTs = now;
          clicks += 1;
          if (clicks === 3 && (now - firstTs) <= 1500) {
            e.preventDefault();
            openModal();
            clicks = 0; firstTs = 0;
          }
          setTimeout(() => { clicks = 0; firstTs = 0; }, 1600);
        }, { passive: false });

        // Atajo de teclado: Ctrl + Alt + I
        document.addEventListener('keydown', (e) => {
          if (e.ctrlKey && e.altKey && (e.key === 'i' || e.key === 'I')) {
            e.preventDefault();
            openModal();
          }
        });

        // Cerrar con Escape
        document.addEventListener('keydown', (e) => {
          if (e.key === 'Escape' && modal.classList.contains('show')) {
            modal.classList.add('hidden');
            modal.classList.remove('show');
          }
        });
      })();
    </script>
</body>
</html>
