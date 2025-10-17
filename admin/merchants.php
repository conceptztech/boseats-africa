<?php
include '../includes/protect_admin.php';

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $merchant_id = $_GET['id'];
    
    try {
        switch ($_GET['action']) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE merchants SET is_approved = 1, account_status = 'active' WHERE id = ?");
                $stmt->execute([$merchant_id]);
                break;
            case 'reject':
                $stmt = $pdo->prepare("UPDATE merchants SET is_approved = 0, account_status = 'inactive' WHERE id = ?");
                $stmt->execute([$merchant_id]);
                break;
            case 'activate':
                $stmt = $pdo->prepare("UPDATE merchants SET is_active = 1, account_status = 'active' WHERE id = ?");
                $stmt->execute([$merchant_id]);
                break;
            case 'deactivate':
                $stmt = $pdo->prepare("UPDATE merchants SET is_active = 0, account_status = 'inactive' WHERE id = ?");
                $stmt->execute([$merchant_id]);
                break;
        }
        header("Location: merchants.php?success=1");
        exit();
    } catch (PDOException $e) {
        error_log("Merchant action error: " . $e->getMessage());
    }
}

// Fetch all merchants
try {
    $stmt = $pdo->query("SELECT * FROM merchants ORDER BY created_at DESC");
    $merchants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Merchants fetch error: " . $e->getMessage());
    $merchants = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchants Management</title>
      <style>
        :root {
            --primary: #28a745;
            --primary-dark: #218838;
            --primary-light: #34ce57;
            --secondary: #FFFFFF;
            --accent: #DDDDDD;
            --text: #1F2937;
            --text-light: #6B7280;
            --border: #E5E7EB;
            --success: #D1FAE5;
            --warning: #FEF3C7;
            --error: #FEE2E2;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: var(--text);
            line-height: 1.6;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: var(--secondary);
            border-right: 1px solid var(--border);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .logo {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
        }

        .logo h2 {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background-color: var(--primary-light);
            color: var(--secondary);
            border-left-color: var(--primary);
        }

        .nav-link.active {
            background-color: var(--primary);
            color: var(--secondary);
            border-left-color: var(--primary-dark);
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            background: var(--secondary);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .header h1 {
            color: var(--text);
            font-size: 1.8rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info span {
            color: var(--text-light);
        }

        .logout-btn {
            background: var(--error);
            color: var(--text);
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .logout-btn:hover {
            opacity: 0.8;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--secondary);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid var(--primary);
        }

        .stat-card h3 {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-change {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .stat-change.positive {
            color: var(--primary);
        }

        .stat-change.negative {
            color: #dc3545;
        }

        /* Tables */
        .content-section {
            background: var(--secondary);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .section-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .section-header h2 {
            color: var(--text);
            font-size: 1.3rem;
        }

        .view-all {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background-color: var(--accent);
            font-weight: 600;
            color: var(--text);
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status.active {
            background-color: var(--success);
            color: #065f46;
        }

        .status.pending {
            background-color: var(--warning);
            color: #92400e;
        }

        .status.inactive {
            background-color: var(--error);
            color: #991b1b;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--secondary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-warning {
            background: #ffc107;
            color: var(--text);
        }

        .btn-danger {
            background: #dc3545;
            color: var(--secondary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .admin-container {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Include sidebar from admin.php -->
        <main class="main-content">
            <header class="header">
                <h1>Merchants Management</h1>
            </header>

            <?php if (isset($_GET['success'])): ?>
                <div style="background: var(--success); color: #065f46; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    Action completed successfully!
                </div>
            <?php endif; ?>

            <section class="content-section">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Owner</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Country</th>
                                <th>Status</th>
                                <th>Approved</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($merchants)): ?>
                                <?php foreach ($merchants as $merchant): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($merchant['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($merchant['owners_name']); ?></td>
                                    <td><?php echo htmlspecialchars($merchant['email']); ?></td>
                                    <td><?php echo htmlspecialchars($merchant['phone_code'] . $merchant['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($merchant['country']); ?></td>
                                    <td>
                                        <span class="status <?php echo $merchant['account_status']; ?>">
                                            <?php echo ucfirst($merchant['account_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status <?php echo $merchant['is_approved'] ? 'active' : 'pending'; ?>">
                                            <?php echo $merchant['is_approved'] ? 'Approved' : 'Pending'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!$merchant['is_approved']): ?>
                                            <a href="merchants.php?action=approve&id=<?php echo $merchant['id']; ?>" class="btn btn-primary">Approve</a>
                                            <a href="merchants.php?action=reject&id=<?php echo $merchant['id']; ?>" class="btn btn-danger">Reject</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($merchant['is_active']): ?>
                                            <a href="merchants.php?action=deactivate&id=<?php echo $merchant['id']; ?>" class="btn btn-warning">Deactivate</a>
                                        <?php else: ?>
                                            <a href="merchants.php?action=activate&id=<?php echo $merchant['id']; ?>" class="btn btn-primary">Activate</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">No merchants found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>