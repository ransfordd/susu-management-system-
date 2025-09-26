<?php
?><!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>The Determiners Susu System</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
	<link href="/assets/css/app.css" rel="stylesheet">
	<!-- Password toggle script removed to prevent duplicate buttons -->
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark modern-navbar">
	<div class="container-fluid">
		<a class="navbar-brand modern-brand" href="/index.php">
			<i class="fas fa-coins me-2"></i>
			<span class="brand-text">The Determiners Susu System</span>
		</a>
		<button class="navbar-toggler modern-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarNav">
			<?php if (!empty($_SESSION['user'])) { include __DIR__ . '/../views/shared/menu.php'; } ?>
		</div>
	</div>
</nav>

<style>
/* Modern Navbar Styles */
.modern-navbar {
	background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
	box-shadow: 0 2px 20px rgba(0,0,0,0.3);
	border-bottom: 1px solid rgba(255,255,255,0.1);
}

.modern-brand {
	font-size: 1.3rem;
	font-weight: 700;
	color: white !important;
	text-decoration: none;
	display: flex;
	align-items: center;
	transition: all 0.3s ease;
}

.modern-brand:hover {
	color: #f8f9fa !important;
	transform: translateY(-1px);
	text-decoration: none;
}

.modern-brand i {
	color: #ffd700;
	font-size: 1.4rem;
	text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
}

.brand-text {
	background: linear-gradient(45deg, #ffffff, #f8f9fa);
	-webkit-background-clip: text;
	-webkit-text-fill-color: transparent;
	background-clip: text;
}

.modern-toggler {
	border: 2px solid rgba(255,255,255,0.3);
	border-radius: 8px;
	padding: 0.5rem;
	transition: all 0.3s ease;
}

.modern-toggler:hover {
	border-color: rgba(255,255,255,0.6);
	background: rgba(255,255,255,0.1);
}

.modern-toggler:focus {
	box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.25);
}

.navbar-nav .nav-link {
	color: rgba(255,255,255,0.9) !important;
	font-weight: 500;
	transition: all 0.3s ease;
	padding: 0.75rem 1rem;
	border-radius: 8px;
	margin: 0 0.25rem;
}

.navbar-nav .nav-link:hover {
	color: white !important;
	background: rgba(255,255,255,0.1);
	transform: translateY(-1px);
}

.navbar-nav .dropdown-toggle::after {
	border-top-color: rgba(255,255,255,0.8);
}

.dropdown-menu {
	background: white;
	border: none;
	border-radius: 12px;
	box-shadow: 0 8px 30px rgba(0,0,0,0.15);
	margin-top: 0.5rem;
	overflow: hidden;
}

.dropdown-item {
	padding: 0.75rem 1rem;
	transition: all 0.3s ease;
	color: #495057;
	font-weight: 500;
}

.dropdown-item:hover {
	background: linear-gradient(135deg, #f8f9fa, #e9ecef);
	color: #2c3e50;
	transform: translateX(5px);
}

.dropdown-item i {
	width: 20px;
	color: #6c757d;
}

.dropdown-header {
	background: linear-gradient(135deg, #f8f9fa, #e9ecef);
	color: #495057;
	font-weight: 600;
	border-bottom: 1px solid #e9ecef;
}

.dropdown-divider {
	margin: 0.5rem 0;
	border-color: #e9ecef;
}

/* Responsive Design */
@media (max-width: 991px) {
	.modern-brand {
		font-size: 1.1rem;
	}
	
	.brand-text {
		display: none;
	}
	
	.modern-brand i {
		font-size: 1.2rem;
	}
}

@media (max-width: 576px) {
	.modern-brand {
		font-size: 1rem;
	}
	
	.modern-brand i {
		font-size: 1.1rem;
	}
}
</style>
<main class="container my-4">




