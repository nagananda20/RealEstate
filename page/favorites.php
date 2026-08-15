<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

function safe($value)
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}


/*
|--------------------------------------------------------------------------
| Get Favorite Properties
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        f.id AS favorite_id,
        p.id,
        p.title,
        p.description,
        p.price,
        p.property_type,
        p.listing_type,
        p.bedrooms,
        p.bathrooms,
        p.area,
        p.address,
        p.city,
        p.state,
        p.featured,
        pi.image

    FROM favorites f

    INNER JOIN properties p
        ON f.property_id = p.id

    LEFT JOIN property_images pi
        ON p.id = pi.property_id
        AND pi.is_primary = 1

    WHERE f.user_id = ?
      AND p.status IN ('published','available')

    ORDER BY f.created_at DESC
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $userId
);

$stmt->execute();

$result = $stmt->get_result();

$favorites = [];

while ($row = $result->fetch_assoc()) {
    $favorites[] = $row;
}

$stmt->close();

$userName =
    $_SESSION["user_name"] ?? "User";

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    My Favorites | RealEstateHub
</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary: #174a3a;
    --primary-dark: #10372b;
    --accent: #d7a94b;
    --bg: #f5f7f5;
    --white: #ffffff;
    --text: #18231f;
    --muted: #707b76;
    --border: #e0e7e3;
    --danger: #d84c59;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: var(--bg);
    color: var(--text);
}


/* HEADER */

.header {
    height: 72px;
    background: white;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 5%;
    position: sticky;
    top: 0;
    z-index: 100;
}

.logo {
    font-size: 21px;
    font-weight: 800;
}

.logo span {
    color: var(--primary);
}

.logo strong {
    color: var(--accent);
}

.nav {
    display: flex;
    gap: 25px;
    font-size: 12px;
}

.nav a {
    color: var(--muted);
    text-decoration: none;
}

.nav a:hover {
    color: var(--primary);
}

.user {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 11px;
}

.avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: #e8f2ed;
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
}


/* CONTAINER */

.container {
    max-width: 1250px;
    margin: auto;
    padding: 35px 5% 70px;
}


/* PAGE HEADER */

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.page-header h1 {
    font-size: 30px;
    margin-bottom: 6px;
}

.page-header p {
    color: var(--muted);
    font-size: 11px;
}

.count {
    background: #e7f1ec;
    color: var(--primary);
    padding: 9px 14px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
}


/* GRID */

.property-grid {
    display: grid;
    grid-template-columns:
        repeat(3, 1fr);
    gap: 22px;
}


/* CARD */

.property-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    transition: .25s;
}

.property-card:hover {
    transform: translateY(-4px);
    box-shadow:
        0 12px 30px rgba(20,50,40,.08);
}


/* IMAGE */

.property-image {
    height: 220px;
    position: relative;
    overflow: hidden;
}

.property-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: .4s;
}

.property-card:hover
.property-image img {
    transform: scale(1.05);
}


/* BADGES */

.featured {
    position: absolute;
    left: 12px;
    top: 12px;
    background: var(--primary);
    color: white;
    padding: 7px 9px;
    border-radius: 5px;
    font-size: 8px;
    font-weight: 800;
}

.listing {
    position: absolute;
    left: 12px;
    bottom: 12px;
    background: white;
    color: var(--primary);
    padding: 7px 10px;
    border-radius: 5px;
    font-size: 8px;
    font-weight: 800;
}


/* REMOVE BUTTON */

.remove-favorite {
    position: absolute;
    right: 12px;
    top: 12px;
    width: 35px;
    height: 35px;
    border: none;
    border-radius: 50%;
    background: white;
    color: var(--danger);
    cursor: pointer;
    font-size: 17px;
    box-shadow: 0 3px 12px rgba(0,0,0,.12);
}

.remove-favorite:hover {
    transform: scale(1.08);
}


/* BODY */

.property-body {
    padding: 18px;
}

.property-body h2 {
    font-size: 15px;
    margin-bottom: 8px;
}

.location {
    color: var(--muted);
    font-size: 10px;
    margin-bottom: 15px;
}

.price {
    color: var(--primary);
    font-size: 21px;
    font-weight: 800;
    margin-bottom: 14px;
}

.details {
    display: flex;
    gap: 12px;
    color: var(--muted);
    font-size: 9px;
    border-top: 1px solid var(--border);
    padding-top: 13px;
}

.card-actions {
    display: flex;
    gap: 8px;
    margin-top: 17px;
}

.view-button {
    flex: 1;
    height: 40px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 10px;
    font-weight: 700;
    text-align: center;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

.view-button:hover {
    background: var(--primary-dark);
}

.remove-button {
    width: 40px;
    height: 40px;
    border: 1px solid var(--border);
    background: white;
    color: var(--danger);
    border-radius: 6px;
    cursor: pointer;
    font-size: 15px;
}

.remove-button:hover {
    background: #fff3f4;
}


/* EMPTY */

.empty {
    background: white;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 70px 25px;
    text-align: center;
}

.empty-icon {
    font-size: 55px;
    margin-bottom: 15px;
}

.empty h2 {
    font-size: 20px;
    margin-bottom: 8px;
}

.empty p {
    color: var(--muted);
    font-size: 11px;
    margin-bottom: 22px;
}

.explore-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 43px;
    padding: 0 22px;
    background: var(--primary);
    color: white;
    border-radius: 7px;
    text-decoration: none;
    font-size: 10px;
    font-weight: 700;
}


/* RESPONSIVE */

@media(max-width: 1000px) {

    .property-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

}

@media(max-width: 700px) {

    .nav {
        display: none;
    }

    .property-grid {
        grid-template-columns: 1fr;
    }

    .page-header {
        align-items: flex-start;
        gap: 15px;
    }

}

@media(max-width: 450px) {

    .header {
        padding: 0 15px;
    }

    .container {
        padding-left: 15px;
        padding-right: 15px;
    }

}

</style>

</head>


<body>


<!-- HEADER -->

<header class="header">

    <a
        href="dashboard.php"
        class="logo"
    >
        <span>Real</span><strong>Estate</strong>Hub
    </a>


    <nav class="nav">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="properties.php">
            Properties
        </a>

        <a href="favorites.php">
            Favorites
        </a>

        <a href="visits.php">
            Visits
        </a>

    </nav>


    <div class="user">

        <div class="avatar">

            <?php

            echo strtoupper(
                substr(
                    $userName,
                    0,
                    1
                )
            );

            ?>

        </div>

        <?php echo safe($userName); ?>

    </div>

</header>



<!-- MAIN -->

<main class="container">


    <div class="page-header">

        <div>

            <h1>
                My Favorites
            </h1>

            <p>
                Properties you've saved for later.
            </p>

        </div>


        <div class="count">

            <?php
            echo count($favorites);
            ?>

            Saved

        </div>

    </div>



    <?php if (empty($favorites)): ?>


        <!-- EMPTY STATE -->

        <div class="empty">

            <div class="empty-icon">
                ♡
            </div>

            <h2>
                No saved properties yet
            </h2>

            <p>
                Start exploring properties and
                save the ones you love.
            </p>

            <a
                href="properties.php"
                class="explore-button"
            >
                Explore Properties
            </a>

        </div>


    <?php else: ?>


        <!-- PROPERTY GRID -->

        <div class="property-grid">


            <?php foreach (
                $favorites as $property
            ): ?>


                <?php

                $image =
                    !empty(
                        $property["image_path"]
                    )

                    ? "../uploads/properties/" .
                        $property["image_path"]

                    : "../assets/images/property-placeholder.jpg";


                $price =
                    (float)$property["price"];


                if ($price >= 10000000) {

                    $formattedPrice =
                        "₹" .
                        number_format(
                            $price / 10000000,
                            2
                        ) .
                        " Cr";

                } elseif ($price >= 100000) {

                    $formattedPrice =
                        "₹" .
                        number_format(
                            $price / 100000,
                            2
                        ) .
                        " L";

                } else {

                    $formattedPrice =
                        "₹" .
                        number_format($price);

                }

                ?>


                <article
                    class="property-card"
                    id="property-<?php
                    echo (int)$property["id"];
                    ?>"
                >


                    <div class="property-image">


                        <img
                            src="<?php
                            echo safe($image);
                            ?>"
                            alt="<?php
                            echo safe(
                                $property["title"]
                            );
                            ?>"
                        >


                        <?php if (
                            $property["featured"]
                        ): ?>

                            <div class="featured">
                                FEATURED
                            </div>

                        <?php endif; ?>


                        <div class="listing">

                            FOR

                            <?php
                            echo strtoupper(
                                safe(
                                    $property[
                                        "listing_type"
                                    ]
                                )
                            );
                            ?>

                        </div>


                        <button
                            class="remove-favorite"
                            data-property-id="<?php
                            echo (int)
                                $property["id"];
                            ?>"
                            title="Remove Favorite"
                        >
                            ♥
                        </button>


                    </div>



                    <div class="property-body">


                        <h2>

                            <?php
                            echo safe(
                                $property["title"]
                            );
                            ?>

                        </h2>


                        <div class="location">

                            📍

                            <?php
                            echo safe(
                                $property["city"]
                            );
                            ?>

                            <?php

                            if (
                                !empty(
                                    $property["state"]
                                )
                            ) {

                                echo ", " .
                                    safe(
                                        $property["state"]
                                    );

                            }

                            ?>

                        </div>


                        <div class="price">

                            <?php
                            echo $formattedPrice;
                            ?>

                        </div>


                        <div class="details">

                            <span>
                                🛏
                                <?php
                                echo (int)
                                    $property[
                                        "bedrooms"
                                    ];
                                ?>
                                Beds
                            </span>


                            <span>
                                🚿
                                <?php
                                echo (int)
                                    $property[
                                        "bathrooms"
                                    ];
                                ?>
                                Baths
                            </span>


                            <span>
                                📐
                                <?php
                                echo number_format(
                                    $property[
                                        "area_sqft"
                                    ]
                                );
                                ?>
                                sqft
                            </span>

                        </div>


                        <div class="card-actions">


                            <a
                                href="property-details.php?id=<?php
                                echo (int)
                                    $property["id"];
                                ?>"
                                class="view-button"
                            >
                                View Property
                            </a>


                            <button
                                class="remove-button"
                                data-property-id="<?php
                                echo (int)
                                    $property["id"];
                                ?>"
                                title="Remove Favorite"
                            >
                                ♥
                            </button>


                        </div>


                    </div>


                </article>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</main>



<script>

/*
|--------------------------------------------------------------------------
| Remove Favorite
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll(
        ".remove-favorite, .remove-button"
    )
    .forEach(function(button) {

        button.addEventListener(
            "click",
            async function() {


                const propertyId =
                    this.dataset.propertyId;


                if (!propertyId) {
                    return;
                }


                try {


                    const response =
                        await fetch(
                            "../api/favorite.php",
                            {

                                method: "POST",

                                headers: {
                                    "Content-Type":
                                        "application/json"
                                },

                                body:
                                    JSON.stringify({
                                        property_id:
                                            parseInt(
                                                propertyId
                                            )
                                    })

                            }
                        );


                    const data =
                        await response.json();


                    if (
                        data.success &&
                        data.action === "removed"
                    ) {


                        const card =
                            document.getElementById(
                                "property-" +
                                propertyId
                            );


                        if (card) {

                            card.style.transition =
                                "all .3s ease";

                            card.style.opacity =
                                "0";

                            card.style.transform =
                                "scale(.95)";


                            setTimeout(
                                function() {

                                    card.remove();

                                    updateCount();

                                },
                                300
                            );

                        }


                    } else {

                        alert(
                            data.message ||
                            "Unable to remove favorite."
                        );

                    }


                } catch (error) {

                    alert(
                        "Server error. Please try again."
                    );

                }

            }
        );

    });


/*
|--------------------------------------------------------------------------
| Update Saved Count
|--------------------------------------------------------------------------
*/

function updateCount()
{

    const cards =
        document.querySelectorAll(
            ".property-card"
        );


    const count =
        document.querySelector(
            ".count"
        );


    if (count) {

        count.textContent =
            cards.length + " Saved";

    }


    if (cards.length === 0) {

        location.reload();

    }

}

</script>


</body>

</html>