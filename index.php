<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>RealEstateHub | Find Your Dream Property</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>

<body>

<header class="navbar">

    <div class="logo">
        <span>Real</span>EstateHub
    </div>

    <nav>
        <a href="index.php" class="active">Home</a>
        <a href="page/properties.php">Properties</a>
        <a href="page/buy.php">Buy</a>
        <a href="page/rent.php">Rent</a>
        <a href="page/agents.php">Agents</a>
        <a href="page/about.php">About</a>
    </nav>

    <div class="nav-actions">
        <a href="auth/login.php" class="login-btn">
            Login
        </a>

        <a href="page/sell.php" class="add-property-btn">
            + Add Property
        </a>
    </div>

</header>


<main>

<section class="hero">

    <div class="hero-content">

        <span class="hero-tag">
            ✦ Find your perfect place
        </span>

        <h1>
            Find a home that
            <span>fits your life.</span>
        </h1>

        <p>
            Discover thousands of verified properties,
            apartments, villas and commercial spaces.
        </p>

        <div class="search-box">

            <div class="search-tabs">

                <button class="tab active">
                    Buy
                </button>

                <button class="tab">
                    Rent
                </button>

                <button class="tab">
                    Commercial
                </button>

            </div>

            <div class="search-fields">

                <div class="search-field">

                    <label>Location</label>

                    <input
                        type="text"
                        placeholder="City, neighborhood or ZIP"
                    >

                </div>


                <div class="search-field">

                    <label>Property Type</label>

                    <select>

                        <option>All Properties</option>
                        <option>Apartment</option>
                        <option>Villa</option>
                        <option>House</option>
                        <option>Land</option>
                        <option>Office</option>

                    </select>

                </div>


                <div class="search-field">

                    <label>Price</label>

                    <select>

                        <option>Any Price</option>
                        <option>Under ₹25L</option>
                        <option>₹25L - ₹50L</option>
                        <option>₹50L - ₹1Cr</option>
                        <option>Above ₹1Cr</option>

                    </select>

                </div>


                <button class="search-btn">
                    🔍 Search
                </button>

            </div>

        </div>

    </div>

</section>


<section class="section">

    <div class="section-header">

        <div>

            <span class="section-tag">
                EXPLORE
            </span>

            <h2>
                Featured Properties
            </h2>

            <p>
                Hand-picked properties you may love.
            </p>

        </div>

        <a href="page/properties.php" class="view-all">
            View All →
        </a>

    </div>


    <div class="property-grid">


        <article class="property-card">

            <div class="property-image">

                <img
                    src="assets/images/property-1.jpg"
                    alt="Modern Apartment"
                >

                <span class="property-badge">
                    Featured
                </span>

                <button class="favorite">
                    ♡
                </button>

            </div>


            <div class="property-content">

                <div class="property-price">
                    ₹52,00,000
                </div>

                <h3>
                    Modern 2BHK Apartment
                </h3>

                <p class="property-location">
                    📍 Bengaluru, Karnataka
                </p>

                <div class="property-info">

                    <span>🛏 2 Beds</span>
                    <span>🚿 2 Baths</span>
                    <span>📐 1,250 sqft</span>

                </div>

            </div>

        </article>


        <article class="property-card">

            <div class="property-image">

                <img
                    src="assets/images/property-2.jpg"
                    alt="Luxury Villa"
                >

                <span class="property-badge">
                    New
                </span>

                <button class="favorite">
                    ♡
                </button>

            </div>


            <div class="property-content">

                <div class="property-price">
                    ₹1.25 Cr
                </div>

                <h3>
                    Luxury Family Villa
                </h3>

                <p class="property-location">
                    📍 Mysuru, Karnataka
                </p>

                <div class="property-info">

                    <span>🛏 4 Beds</span>
                    <span>🚿 3 Baths</span>
                    <span>📐 2,800 sqft</span>

                </div>

            </div>

        </article>


        <article class="property-card">

            <div class="property-image">

                <img
                    src="assets/images/property-3.jpg"
                    alt="Premium House"
                >

                <button class="favorite">
                    ♡
                </button>

            </div>


            <div class="property-content">

                <div class="property-price">
                    ₹78,00,000
                </div>

                <h3>
                    Premium Independent House
                </h3>

                <p class="property-location">
                    📍 Dharwad, Karnataka
                </p>

                <div class="property-info">

                    <span>🛏 3 Beds</span>
                    <span>🚿 2 Baths</span>
                    <span>📐 1,900 sqft</span>

                </div>

            </div>

        </article>

    </div>

</section>


<section class="categories">

    <div class="section-header">

        <div>

            <span class="section-tag">
                CATEGORIES
            </span>

            <h2>
                Explore Property Types
            </h2>

        </div>

    </div>


    <div class="category-grid">

        <div class="category-card">
            <div class="category-icon">🏢</div>
            <h3>Apartments</h3>
            <p>1,240 Properties</p>
        </div>

        <div class="category-card">
            <div class="category-icon">🏡</div>
            <h3>Villas</h3>
            <p>850 Properties</p>
        </div>

        <div class="category-card">
            <div class="category-icon">🏠</div>
            <h3>Houses</h3>
            <p>620 Properties</p>
        </div>

        <div class="category-card">
            <div class="category-icon">🏗️</div>
            <h3>Land</h3>
            <p>410 Properties</p>
        </div>

    </div>

</section>


<section class="why-us">

    <div class="why-content">

        <span class="section-tag">
            WHY REAL ESTATE HUB
        </span>

        <h2>
            Everything you need to
            find your next home.
        </h2>

        <p>
            Search smarter, compare properties,
            connect with verified agents and
            schedule property visits.
        </p>

        <div class="features">

            <div>
                <strong>✓</strong>
                <span>Verified Properties</span>
            </div>

            <div>
                <strong>✓</strong>
                <span>Trusted Agents</span>
            </div>

            <div>
                <strong>✓</strong>
                <span>Smart Search</span>
            </div>

            <div>
                <strong>✓</strong>
                <span>Secure Communication</span>
            </div>

        </div>

    </div>

</section>


<section class="cta">

    <h2>
        Ready to find your dream home?
    </h2>

    <p>
        Explore thousands of properties today.
    </p>

    <a href="page/properties.php">
        Explore Properties
    </a>

</section>

</main>


<footer>

    <div class="footer-content">

        <div>

            <div class="logo">
                <span>Real</span>EstateHub
            </div>

            <p>
                Your trusted platform for
                buying, renting and selling property.
            </p>

        </div>


        <div>

            <h4>Explore</h4>

            <a href="page/properties.php">Properties</a>
            <a href="page/buy.php">Buy</a>
            <a href="page/rent.php">Rent</a>

        </div>


        <div>

            <h4>Company</h4>

            <a href="page/about.php">About</a>
            <a href="page/agents.php">Agents</a>
            <a href="page/contact.php">Contact</a>

        </div>

    </div>

    <div class="footer-bottom">
        © 2026 RealEstateHub. All rights reserved.
    </div>

</footer>


<script src="assets/js/main.js"></script>

</body>
</html>