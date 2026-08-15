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
| Cancel Visit
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["cancel_visit"])
) {

    $visitId = filter_input(
        INPUT_POST,
        "visit_id",
        FILTER_VALIDATE_INT
    );

    if ($visitId) {

        $cancelSQL = "
            UPDATE visits
            SET status = 'cancelled'
            WHERE id = ?
              AND user_id = ?
              AND status = 'pending'
        ";

        $cancelStmt =
            $conn->prepare($cancelSQL);

        $cancelStmt->bind_param(
            "ii",
            $visitId,
            $userId
        );

        $cancelStmt->execute();

        $cancelStmt->close();
    }

    header("Location: visits.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Visits
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        v.id,
        v.property_id,
        v.visit_date,
        v.visit_time,
        v.message,
        v.status,
        v.created_at,

        p.title,
        p.price,
        p.property_type,
        p.city,
        p.state,

        pi.image

    FROM visits v

    INNER JOIN properties p
        ON v.property_id = p.id

    LEFT JOIN property_images pi
        ON p.id = pi.property_id
        AND pi.is_primary = 1

    WHERE v.user_id = ?

    ORDER BY
        v.visit_date DESC,
        v.visit_time DESC
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $userId
);

$stmt->execute();

$result = $stmt->get_result();

$visits = [];

while ($row = $result->fetch_assoc()) {
    $visits[] = $row;
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| Status Counts
|--------------------------------------------------------------------------
*/

$pending = 0;
$confirmed = 0;
$completed = 0;
$cancelled = 0;

foreach ($visits as $visit) {

    switch ($visit["status"]) {

        case "pending":
            $pending++;
            break;

        case "confirmed":
            $confirmed++;
            break;

        case "completed":
            $completed++;
            break;

        case "cancelled":
            $cancelled++;
            break;
    }
}


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
    My Visits | RealEstateHub
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

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        var(--bg);

    color:
        var(--text);

}


/* HEADER */

.header {

    height: 72px;

    background:
        white;

    border-bottom:
        1px solid var(--border);

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    padding:
        0 5%;

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

    color:
        var(--muted);

    text-decoration:
        none;

}

.nav a:hover {

    color:
        var(--primary);

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

    background:
        #e8f2ed;

    color:
        var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: 800;

}


/* CONTAINER */

.container {

    max-width:
        1250px;

    margin:
        auto;

    padding:
        35px 5% 70px;

}


/* HEADER */

.page-header {

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    margin-bottom:
        25px;

}

.page-header h1 {

    font-size:
        30px;

    margin-bottom:
        6px;

}

.page-header p {

    color:
        var(--muted);

    font-size:
        11px;

}


/* STATS */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;

    margin-bottom:
        25px;

}

.stat-card {

    background:
        white;

    border:
        1px solid var(--border);

    border-radius:
        10px;

    padding:
        18px;

}

.stat-icon {

    font-size:
        22px;

    margin-bottom:
        8px;

}

.stat-card strong {

    display: block;

    font-size:
        22px;

    margin-bottom:
        4px;

}

.stat-card span {

    color:
        var(--muted);

    font-size:
        9px;

}


/* VISIT LIST */

.visit-list {

    display:
        flex;

    flex-direction:
        column;

    gap:
        16px;

}


/* VISIT CARD */

.visit-card {

    background:
        white;

    border:
        1px solid var(--border);

    border-radius:
        12px;

    padding:
        18px;

    display:
        grid;

    grid-template-columns:
        190px 1fr auto;

    gap:
        20px;

    align-items:
        center;

    transition:
        .25s;

}

.visit-card:hover {

    box-shadow:
        0 10px 30px
        rgba(20,50,40,.07);

}


/* IMAGE */

.visit-image {

    height:
        130px;

    border-radius:
        9px;

    overflow:
        hidden;

}

.visit-image img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

}


/* DETAILS */

.visit-details h2 {

    font-size:
        16px;

    margin-bottom:
        8px;

}

.location {

    color:
        var(--muted);

    font-size:
        10px;

    margin-bottom:
        15px;

}

.visit-info {

    display:
        flex;

    flex-wrap:
        wrap;

    gap:
        10px;

}

.info-box {

    background:
        #f5f8f6;

    padding:
        9px 12px;

    border-radius:
        6px;

    font-size:
        9px;

}

.info-box strong {

    color:
        var(--primary);

}


/* STATUS */

.status-area {

    text-align:
        right;

}

.status {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    padding:
        7px 10px;

    border-radius:
        20px;

    font-size:
        9px;

    font-weight:
        700;

    text-transform:
        capitalize;

    margin-bottom:
        14px;

}

.status.pending {

    background:
        #fff5dd;

    color:
        #9a6a00;

}

.status.confirmed {

    background:
        #e3f5ea;

    color:
        #17643b;

}

.status.completed {

    background:
        #e7eef8;

    color:
        #345c88;

}

.status.cancelled {

    background:
        #fde9eb;

    color:
        #a5303c;

}

.status-dot {

    width:
        6px;

    height:
        6px;

    border-radius:
        50%;

    background:
        currentColor;

}


/* BUTTONS */

.actions {

    display:
        flex;

    flex-direction:
        column;

    gap:
        7px;

}

.view-button {

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    width:
        125px;

    height:
        38px;

    background:
        var(--primary);

    color:
        white;

    text-decoration:
        none;

    border-radius:
        6px;

    font-size:
        9px;

    font-weight:
        700;

}

.view-button:hover {

    background:
        var(--primary-dark);

}

.cancel-button {

    width:
        125px;

    height:
        38px;

    border:
        1px solid #efc4c8;

    background:
        white;

    color:
        var(--danger);

    border-radius:
        6px;

    font-size:
        9px;

    font-weight:
        700;

    cursor:
        pointer;

}

.cancel-button:hover {

    background:
        #fff4f5;

}


/* EMPTY */

.empty {

    background:
        white;

    border:
        1px solid var(--border);

    border-radius:
        14px;

    padding:
        70px 25px;

    text-align:
        center;

}

.empty-icon {

    font-size:
        55px;

    margin-bottom:
        15px;

}

.empty h2 {

    font-size:
        20px;

    margin-bottom:
        8px;

}

.empty p {

    color:
        var(--muted);

    font-size:
        11px;

    margin-bottom:
        22px;

}

.explore-button {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    height:
        43px;

    padding:
        0 22px;

    background:
        var(--primary);

    color:
        white;

    border-radius:
        7px;

    text-decoration:
        none;

    font-size:
        10px;

    font-weight:
        700;

}


/* RESPONSIVE */

@media(max-width: 950px) {

    .visit-card {

        grid-template-columns:
            150px 1fr;

    }

    .status-area {

        grid-column:
            1 / -1;

        text-align:
            left;

        display:
            flex;

        align-items:
            center;

        gap:
            15px;

    }

    .status {

        margin-bottom:
            0;

    }

    .actions {

        flex-direction:
            row;

    }

}


@media(max-width: 750px) {

    .nav {
        display: none;
    }

    .stats {

        grid-template-columns:
            repeat(2, 1fr);

    }

    .visit-card {

        grid-template-columns:
            1fr;

    }

    .visit-image {

        height:
            200px;

    }

    .status-area {

        display:
            block;

    }

    .status {

        margin-bottom:
            12px;

    }

    .actions {

        flex-direction:
            row;

    }

    .view-button,
    .cancel-button {

        flex:
            1;

    }

}


@media(max-width: 450px) {

    .header {

        padding:
            0 15px;

    }

    .container {

        padding-left:
            15px;

        padding-right:
            15px;

    }

    .stats {

        grid-template-columns:
            1fr;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
====================================================== -->

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



<!-- =====================================================
     MAIN
====================================================== -->

<main class="container">


    <div class="page-header">

        <div>

            <h1>
                My Property Visits
            </h1>

            <p>
                Manage your scheduled property visits.
            </p>

        </div>

    </div>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="stats">


        <div class="stat-card">

            <div class="stat-icon">
                📅
            </div>

            <strong>
                <?php echo $pending; ?>
            </strong>

            <span>
                Pending
            </span>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ✅
            </div>

            <strong>
                <?php echo $confirmed; ?>
            </strong>

            <span>
                Confirmed
            </span>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                🏆
            </div>

            <strong>
                <?php echo $completed; ?>
            </strong>

            <span>
                Completed
            </span>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ❌
            </div>

            <strong>
                <?php echo $cancelled; ?>
            </strong>

            <span>
                Cancelled
            </span>

        </div>


    </div>



    <?php if (empty($visits)): ?>


        <!-- EMPTY STATE -->

        <div class="empty">

            <div class="empty-icon">
                📅
            </div>

            <h2>
                No property visits yet
            </h2>

            <p>
                Schedule a visit to explore your
                favorite properties in person.
            </p>

            <a
                href="properties.php"
                class="explore-button"
            >
                Explore Properties
            </a>

        </div>


    <?php else: ?>


        <!-- VISIT LIST -->

        <div class="visit-list">


            <?php foreach (
                $visits as $visit
            ): ?>


                <?php

                $image =
                    !empty(
                        $visit["image_path"]
                    )

                    ? "../uploads/properties/" .
                        $visit["image_path"]

                    : "../assets/images/property-placeholder.jpg";


                $visitDate =
                    date(
                        "d M Y",
                        strtotime(
                            $visit["visit_date"]
                        )
                    );


                $visitTime =
                    date(
                        "h:i A",
                        strtotime(
                            $visit["visit_time"]
                        )
                    );


                $statusClass =
                    strtolower(
                        $visit["status"]
                    );


                ?>


                <article
                    class="visit-card"
                >


                    <!-- IMAGE -->

                    <div class="visit-image">

                        <img
                            src="<?php
                            echo safe($image);
                            ?>"
                            alt="<?php
                            echo safe(
                                $visit["title"]
                            );
                            ?>"
                        >

                    </div>



                    <!-- DETAILS -->

                    <div class="visit-details">


                        <h2>

                            <?php
                            echo safe(
                                $visit["title"]
                            );
                            ?>

                        </h2>


                        <div class="location">

                            📍

                            <?php
                            echo safe(
                                $visit["city"]
                            );
                            ?>

                            <?php

                            if (
                                !empty(
                                    $visit["state"]
                                )
                            ) {

                                echo ", " .
                                    safe(
                                        $visit["state"]
                                    );

                            }

                            ?>

                        </div>


                        <div class="visit-info">


                            <div class="info-box">

                                📅

                                <strong>
                                    <?php
                                    echo $visitDate;
                                    ?>
                                </strong>

                            </div>


                            <div class="info-box">

                                🕐

                                <strong>
                                    <?php
                                    echo $visitTime;
                                    ?>
                                </strong>

                            </div>


                            <div class="info-box">

                                🏠

                                <?php
                                echo safe(
                                    ucfirst(
                                        $visit[
                                            "property_type"
                                        ]
                                    )
                                );
                                ?>

                            </div>


                        </div>


                    </div>



                    <!-- STATUS -->

                    <div class="status-area">


                        <div
                            class="status
                            <?php
                            echo safe(
                                $statusClass
                            );
                            ?>"
                        >

                            <span
                                class="status-dot"
                            ></span>

                            <?php
                            echo safe(
                                $visit["status"]
                            );
                            ?>

                        </div>


                        <div class="actions">


                            <a
                                href="property-details.php?id=<?php
                                echo (int)
                                    $visit[
                                        "property_id"
                                    ];
                                ?>"
                                class="view-button"
                            >

                                View Property

                            </a>


                            <?php if (
                                $visit["status"]
                                === "pending"
                            ): ?>


                                <form
                                    method="POST"
                                    class="cancel-form"
                                >


                                    <input
                                        type="hidden"
                                        name="visit_id"
                                        value="<?php
                                        echo (int)
                                            $visit["id"];
                                        ?>"
                                    >


                                    <button
                                        type="submit"
                                        name="cancel_visit"
                                        class="cancel-button"
                                    >

                                        Cancel Visit

                                    </button>


                                </form>


                            <?php endif; ?>


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
| Cancel Confirmation
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll(
        ".cancel-form"
    )
    .forEach(function(form) {

        form.addEventListener(
            "submit",
            function(event) {

                const confirmed =
                    confirm(
                        "Are you sure you want to cancel this property visit?"
                    );

                if (!confirmed) {

                    event.preventDefault();

                }

            }
        );

    });

</script>


</body>

</html>