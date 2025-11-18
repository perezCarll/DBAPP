<?php
// pages/manager-dashboard.php
$title = "Manager Dashboard";
require_once __DIR__ . '/../app/session.php';

require_login();
require_role(['Manager', 'Admin']); // Manager + Admin allowed

$user = auth_user();
?>
<?php include __DIR__ . '/../partials/header.php'; ?>

<style>
.card-hover:hover {
    background: #f5f5f5;
    transition: 0.2s;
}
</style>

<div class="container py-4">

    <h1 class="mb-2">Manager Dashboard</h1>
    <p class="text-muted mb-4">
        Welcome, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
        (<?php echo htmlspecialchars($user['role']); ?>)
    </p>

    <div class="alert alert-info small">
        You can manage <strong>Products</strong>, <strong>Orders</strong>, and <strong>Payments</strong>.
        Admin settings (Users, Currency, Logs) are restricted.
    </div>

    <div class="row g-4">

        <!-- Products -->
        <div class="col-md-4">
            <a href="/pages/admin-products.php" class="text-decoration-none">
                <div class="card shadow-sm card-hover h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-1">Products</h5>
                        <p class="text-muted small mb-0">
                            Add/edit items, update stock, manage listings.
                        </p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Orders -->
        <div class="col-md-4">
            <a href="/pages/admin-orders.php" class="text-decoration-none">
                <div class="card shadow-sm card-hover h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-1">Orders</h5>
                        <p class="text-muted small mb-0">
                            View order records and update order status.
                        </p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Payments -->
        <div class="col-md-4">
            <a href="/pages/admin-payments.php" class="text-decoration-none">
                <div class="card shadow-sm card-hover h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-1">Payments</h5>
                        <p class="text-muted small mb-0">
                            View payments, confirmations, and refunds.
                        </p>
                    </div>
                </div>
            </a>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
