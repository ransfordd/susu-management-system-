<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/LoanProduct.php';

use function Auth\requireRole;

class AdminProductController {
	public function index(): void {
		requireRole(['business_admin']);
		$products = \LoanProduct::all();
		include __DIR__ . '/../views/admin/products_list.php';
	}

	public function create(): void {
		requireRole(['business_admin']);
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$id = \LoanProduct::create($_POST);
			header('Location: /admin_products.php');
			return;
		}
		include __DIR__ . '/../views/admin/products_create.php';
	}
}







