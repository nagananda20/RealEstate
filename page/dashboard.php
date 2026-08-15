<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}
$role = $_SESSION["user_role"] ?? $_SESSION["role"] ?? "user";
if ($role === "admin") { header("Location: ../admin/dashboard.php"); exit; }

$userId = (int)$_SESSION["user_id"];
$userName = $_SESSION["user_name"] ?? "User";

function safe($v) { return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8"); }

$stmt = $pdo->prepare("SELECT id,name,email,phone,profile_image,created_at FROM users WHERE id=? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch() ?: [];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id=?");
$stmt->execute([$userId]); $favoriteCount=(int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM enquiries WHERE user_id=?");
$stmt->execute([$userId]); $enquiryCount=(int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM visits WHERE user_id=?");
$stmt->execute([$userId]); $visitCount=(int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT p.id,p.title,p.price,p.property_type,p.listing_type,p.bedrooms,p.bathrooms,p.area,p.address,p.city,p.state,p.image
    FROM favorites f JOIN properties p ON p.id=f.property_id WHERE f.user_id=? ORDER BY f.created_at DESC LIMIT 4");
$stmt->execute([$userId]); $favoriteProperties=$stmt->fetchAll();

$stmt = $pdo->query("SELECT p.id,p.title,p.price,p.property_type,p.listing_type,p.bedrooms,p.bathrooms,p.area,p.address,p.city,p.state,p.image
    FROM properties p WHERE p.status IN ('published','available') ORDER BY p.featured DESC,p.created_at DESC LIMIT 6");
$featuredProperties=$stmt->fetchAll();

$stmt=$pdo->prepare("SELECT v.id,v.visit_date,v.visit_time,v.status,p.title FROM visits v JOIN properties p ON p.id=v.property_id WHERE v.user_id=? ORDER BY v.visit_date DESC,v.visit_time DESC LIMIT 5");
$stmt->execute([$userId]); $recentVisits=$stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard | RealEstateHub</title>
<link rel="stylesheet" href="../assets/css/style.css"><link rel="stylesheet" href="../assets/css/dashboard.css"><link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
<header class="navbar">
<div class="logo"><span>Real</span>EstateHub</div>
<nav><a href="../index.php">Home</a><a href="properties.php">Properties</a><a href="agents.php">Agents</a><a href="favorites.php">Favorites</a><a href="visits.php">Visits</a></nav>
<div class="nav-actions"><span><?=safe($userName)?></span><a href="../auth/logout.php">Logout</a></div>
</header>
<main class="dashboard-page" style="padding:40px 6%">
<h1>Welcome, <?=safe($userName)?> 👋</h1>
<p>Manage your saved properties, enquiries and visits.</p>
<section class="dashboard-statistics" style="margin-top:30px">
<div class="stat-card"><h3><?=$favoriteCount?></h3><p>Favorites</p></div>
<div class="stat-card"><h3><?=$enquiryCount?></h3><p>Enquiries</p></div>
<div class="stat-card"><h3><?=$visitCount?></h3><p>Visits</p></div>
<div class="stat-card"><h3><?=count($featuredProperties)?></h3><p>Featured Listings</p></div>
</section>
<section style="margin-top:40px"><h2>Featured Properties</h2><div class="property-grid">
<?php foreach($featuredProperties as $p): ?>
<article class="property-card">
<div class="property-image"><img src="<?=safe(!empty($p['image']) ? '../uploads/properties/'.$p['image'] : '../assets/images/property-placeholder.jpg')?>" alt="<?=safe($p['title'])?>"></div>
<div class="property-content"><div class="property-price">₹<?=number_format((float)$p['price'])?></div><h3><?=safe($p['title'])?></h3><p><?=safe(trim(($p['city']??'').' '.($p['state']??'')))?></p><a href="property-details.php?id=<?=(int)$p['id']?>">View Property</a></div>
</article>
<?php endforeach; ?>
</div></section>
<section style="margin-top:40px"><h2>Recent Visits</h2>
<?php if($recentVisits): foreach($recentVisits as $v): ?>
<p><?=safe($v['title'])?> — <?=safe($v['visit_date'])?> <?=safe($v['visit_time'])?> — <?=safe($v['status'])?></p>
<?php endforeach; else: ?><p>No visits scheduled yet.</p><?php endif; ?>
</section>
</main>
</body></html>
