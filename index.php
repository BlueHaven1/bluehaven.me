<?php
require_once 'auth/config.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlueHaven</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-bg: #0f0f0f;
            --secondary-bg: #1a1a1a;
            --accent-color: #3b82f6;
            --text-primary: #ffffff;
            --text-secondary: #a0a0a0;
            --border-color: #2a2a2a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Styles */
        header {
            background-color: var(--secondary-bg);
            padding: 1.25rem 0;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            text-decoration: none;
        }

        .logo span {
            color: var(--accent-color);
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            color: var(--text-primary);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        nav a:hover {
            color: var(--accent-color);
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .user-greeting {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin-right: 0.5rem;
        }

        .cart-icon {
            position: relative;
            color: var(--text-primary);
            margin-right: 0.5rem;
            transition: color 0.3s ease;
        }

        .cart-icon:hover {
            color: var(--accent-color);
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--accent-color);
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn {
            padding: 0.5rem 1.25rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-outline {
            border: 1px solid var(--border-color);
            background: transparent;
            color: var(--text-primary);
        }

        .btn-outline:hover {
            border-color: var(--accent-color);
            color: var(--accent-color);
        }

        .btn-primary {
            background-color: var(--accent-color);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background-color: #2563eb;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Hero Section */
        .hero {
            padding: 8rem 0;
            text-align: center;
            background-image: url('assets/images/bg-1.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1;
        }

        .hero .container {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero p {
            font-size: 1.25rem;
            color: #e0e0e0;
            max-width: 700px;
            margin: 0 auto 2.5rem;
            line-height: 1.6;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .hero-buttons .btn {
            padding: 0.75rem 1.75rem;
            font-weight: 600;
        }

        /* Featured Section */
        .featured {
            padding: 5rem 0;
            background-color: var(--secondary-bg);
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .section-title p {
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Products Slider */
        .products-slider-container {
            position: relative;
            margin-top: 3rem;
            padding: 0 3rem;
            max-width: 1400px; /* Wider container for the slider */
            margin-left: auto;
            margin-right: auto;
        }

        .products-slider {
            width: 100%;
            overflow: hidden;
            position: relative;
        }

        .products-track {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }

        .product-slide {
            min-width: 100%;
            padding: 0 1rem;
            box-sizing: border-box;
        }

        @media (min-width: 768px) {
            .product-slide {
                min-width: 60%; /* Wider slides on medium screens */
            }
        }

        @media (min-width: 1024px) {
            .product-slide {
                min-width: 40%; /* Wider slides on large screens */
            }
        }

        @media (min-width: 1280px) {
            .product-slide {
                min-width: 33.333%; /* Show 3 per view on extra large screens */
            }
        }

        .slider-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 3rem;
            height: 3rem;
            background-color: var(--primary-bg);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .slider-arrow:hover {
            background-color: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }

        .slider-prev {
            left: 0;
        }

        .slider-next {
            right: 0;
        }

        .slider-dots {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }

        .slider-dot {
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 50%;
            background-color: var(--border-color);
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .slider-dot.active {
            background-color: var(--accent-color);
            transform: scale(1.2);
        }

        .empty-products {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        /* For backward compatibility */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background-color: var(--primary-bg);
            border-radius: 0.5rem;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 400px; /* Maximum width for better readability */
            margin: 0 auto; /* Center the card */
            width: 100%; /* Full width up to max-width */
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .product-image {
            height: 200px;
            width: 100%;
            background-color: #2d3748;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-weight: 500;
            overflow: hidden;
            position: relative;
        }

        .product-image-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(16, 185, 129, 0.9);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            z-index: 5;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .product-image-badge svg {
            width: 14px;
            height: 14px;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .placeholder-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-info {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            min-height: 220px; /* Ensure consistent height for all cards */
        }

        .product-title {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .product-title h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-right: auto; /* Push price to the right */
            width: 100%; /* Full width on small screens or when needed */
        }

        @media (min-width: 1024px) {
            .product-title h3 {
                width: auto; /* Auto width on larger screens */
            }
        }

        .product-price {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--accent-color);
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-weight: 600;
        }

        .subscription-price {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981; /* Green color for subscription */
        }

        .product-badge {
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .subscription-badge {
            display: inline-block;
            background-color: #10b981;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .product-description {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.6;
            font-size: 0.95rem;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 4.8em; /* Fixed height for 3 lines of text */
        }

        .product-card .btn {
            width: 100%;
            text-align: center;
        }

        /* Footer */
        footer {
            background-color: var(--secondary-bg);
            padding: 4rem 0 2rem;
            margin-top: auto;
            border-top: 1px solid var(--border-color);
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }

        .footer-column h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column ul li {
            margin-bottom: 0.75rem;
        }

        .footer-column a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 0.95rem;
        }

        .footer-column a:hover {
            color: var(--accent-color);
        }

        .copyright {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            nav ul, .auth-buttons {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .hero {
                padding: 6rem 0;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .hero-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 300px;
                margin: 0 auto;
            }

            .footer-content {
                grid-template-columns: 1fr;
            }

            .hero {
                padding: 5rem 0;
            }

            .hero h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">Blue<span>Haven</span></a>

                <nav>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Store</a></li>
                        <li><a href="#">Team</a></li>
                        <li><a href="#">Reviews</a></li>
                        <li><a href="#">Documentation</a></li>
                        <?php if (isAdmin()): ?>
                        <li><a href="admin/index.php" style="color: var(--accent-color);">Admin</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <div class="auth-buttons">
                    <?php if ($user): ?>
                    <a href="cart.php" class="cart-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <?php
                        // Get cart count
                        $cartCountResponse = authenticatedRequest(
                            '/rest/v1/cart_items?user_id=eq.' . urlencode($user['id']) . '&select=id',
                            'GET'
                        );
                        $cartCount = 0;
                        if ($cartCountResponse['statusCode'] === 200) {
                            $cartCount = count($cartCountResponse['data']);
                        }
                        if ($cartCount > 0):
                        ?>
                        <span class="cart-count"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <span class="user-greeting">Hello, <?php echo htmlspecialchars($user['user_metadata']['first_name'] ?? $user['email']); ?></span>
                    <a href="profile.php" class="btn btn-primary">My Profile</a>
                    <a href="auth/logout_handler.php" class="btn btn-outline">Logout</a>
                    <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="signup.php" class="btn btn-primary">Sign Up</a>
                    <?php endif; ?>
                </div>

                <button class="mobile-menu-btn">☰</button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Premium FiveM Resources</h1>
            <p>Discover high-quality scripts, vehicle liveries, and custom solutions designed to enhance your FiveM server experience.</p>
            <div class="hero-buttons">
                <a href="products.php" class="btn btn-primary">Browse Store</a>
                <a href="#" class="btn btn-outline">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="featured">
        <div class="container">
            <div class="section-title">
                <h2>Featured Products</h2>
                <p>Explore our most popular resources designed to enhance your server</p>
            </div>

            <?php
            // Get featured products from Supabase
            $featuredProductsResponse = supabaseRequest(
                '/rest/v1/products?is_featured=eq.true&is_active=eq.true&select=*&order=name.asc',
                'GET'
            );

            $featuredProducts = [];
            if ($featuredProductsResponse['statusCode'] === 200) {
                $featuredProducts = $featuredProductsResponse['data'];
            }
            ?>

            <div class="products-slider-container">
                <?php if (empty($featuredProducts)): ?>
                <div class="empty-products">
                    <p>No featured products available at this time.</p>
                </div>
                <?php else: ?>
                <button class="slider-arrow slider-prev" aria-label="Previous product">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>

                <div class="products-slider">
                    <div class="products-track">
                        <?php foreach ($featuredProducts as $product): ?>
                        <div class="product-slide">
                            <div class="product-card">
                                <div class="product-image">
                                    <?php if (!empty($product['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                    <div class="placeholder-image">Product Image</div>
                                    <?php endif; ?>

                                    <?php if ($product['is_subscription']): ?>
                                    <div class="product-image-badge">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path>
                                        </svg>
                                        <span><?php echo $product['subscription_interval'] === 'month' ? 'Monthly' : 'Yearly'; ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <div class="product-title">
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <?php if ($product['is_subscription']): ?>
                                        <span class="product-price subscription-price">
                                            $<?php echo number_format($product['subscription_price'], 2); ?>/<?php echo $product['subscription_interval']; ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="product-price">$<?php echo number_format($product['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($product['is_subscription']): ?>
                                    <div class="product-badge">
                                        <span class="subscription-badge">Subscription</span>
                                    </div>
                                    <?php endif; ?>
                                    <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <a href="product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-primary">View Item</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button class="slider-arrow slider-next" aria-label="Next product">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>

                <div class="slider-dots">
                    <?php for ($i = 0; $i < count($featuredProducts); $i++): ?>
                    <button class="slider-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>" aria-label="Go to slide <?php echo $i + 1; ?>"></button>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>BlueHaven</h3>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Our Team</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>Products</h3>
                    <ul>
                        <li><a href="products.php?category=scripts">Scripts</a></li>
                        <li><a href="products.php?category=vehicles">Vehicle Liveries</a></li>
                        <li><a href="products.php?category=eup">EUP Packages</a></li>
                        <li><a href="products.php?category=websites">Web Solutions</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>Resources</h3>
                    <ul>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">Tutorials</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Support</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>Legal</h3>
                    <ul>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Refund Policy</a></li>
                        <li><a href="#">License</a></li>
                    </ul>
                </div>
            </div>

            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> BlueHaven. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <!-- Slider JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get slider elements
            const track = document.querySelector('.products-track');
            const slides = document.querySelectorAll('.product-slide');
            const prevButton = document.querySelector('.slider-prev');
            const nextButton = document.querySelector('.slider-next');
            const dots = document.querySelectorAll('.slider-dot');

            if (!track || slides.length === 0) return;

            let currentIndex = 0;
            let slideWidth = slides[0].getBoundingClientRect().width;
            let slidesPerView = getSlidesPerView();

            // Set initial position
            updateSlider();

            // Add event listeners
            window.addEventListener('resize', function() {
                slideWidth = slides[0].getBoundingClientRect().width;
                slidesPerView = getSlidesPerView();
                updateSlider();
            });

            if (prevButton) {
                prevButton.addEventListener('click', function() {
                    if (currentIndex > 0) {
                        currentIndex--;
                        updateSlider();
                    }
                });
            }

            if (nextButton) {
                nextButton.addEventListener('click', function() {
                    if (currentIndex < slides.length - slidesPerView) {
                        currentIndex++;
                        updateSlider();
                    }
                });
            }

            dots.forEach((dot, index) => {
                dot.addEventListener('click', function() {
                    currentIndex = index;
                    updateSlider();
                });
            });

            // Auto slide every 5 seconds
            let autoSlideInterval = setInterval(autoSlide, 5000);

            // Pause auto slide on hover
            const sliderContainer = document.querySelector('.products-slider-container');
            if (sliderContainer) {
                sliderContainer.addEventListener('mouseenter', function() {
                    clearInterval(autoSlideInterval);
                });

                sliderContainer.addEventListener('mouseleave', function() {
                    autoSlideInterval = setInterval(autoSlide, 5000);
                });
            }

            // Helper functions
            function updateSlider() {
                // Update track position
                track.style.transform = `translateX(-${currentIndex * slideWidth}px)`;

                // Update dots
                dots.forEach((dot, index) => {
                    if (index === currentIndex) {
                        dot.classList.add('active');
                    } else {
                        dot.classList.remove('active');
                    }
                });

                // Update buttons
                if (prevButton) {
                    prevButton.disabled = currentIndex === 0;
                    prevButton.style.opacity = currentIndex === 0 ? '0.5' : '1';
                }

                if (nextButton) {
                    nextButton.disabled = currentIndex >= slides.length - slidesPerView;
                    nextButton.style.opacity = currentIndex >= slides.length - slidesPerView ? '0.5' : '1';
                }
            }

            function autoSlide() {
                if (currentIndex < slides.length - slidesPerView) {
                    currentIndex++;
                } else {
                    currentIndex = 0;
                }
                updateSlider();
            }

            function getSlidesPerView() {
                const viewportWidth = window.innerWidth;
                if (viewportWidth >= 1280) return 3; // Show 3 slides on extra large screens
                if (viewportWidth >= 1024) return 2; // Show 2 slides on large screens
                if (viewportWidth >= 768) return 1; // Show 1 wider slide on medium screens
                return 1; // Show 1 slide on small screens
            }
        });
    </script>
</body>
</html>
