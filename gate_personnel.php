<?php
session_start();
require_once 'session_check.php';
require_once 'db_config.php';

requireLogin();

$property_id = $_SESSION['property_id'];
$landlord_name = $_SESSION['user_name'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'create' && isset($_POST['username'], $_POST['password'], $_POST['full_name'])) {
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $full_name = trim($_POST['full_name']);
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO gate_personnel (property_id, username, password, full_name) 
                                VALUES (?, ?, ?, ?)");
                $stmt->execute([$property_id, $username, $hashed_password, $full_name]);
                $success_message = "Gate personnel added successfully!";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error_message = "Username already exists for this property.";
                } else {
                    $error_message = "Error: " . $e->getMessage();
                }
            }
        } elseif ($action === 'delete' && isset($_POST['id'])) {
            $id = intval($_POST['id']);
            try {
                $stmt = $pdo->prepare("DELETE FROM gate_personnel WHERE id = ? AND property_id = ?");
                $stmt->execute([$id, $property_id]);
                $success_message = "Gate personnel deleted successfully!";
            } catch (PDOException $e) {
                $error_message = "Error deleting personnel: " . $e->getMessage();
            }
        } elseif ($action === 'reset_password' && isset($_POST['id'], $_POST['new_password'])) {
            $id = intval($_POST['id']);
            $new_password = $_POST['new_password'];
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("UPDATE gate_personnel SET password = ? WHERE id = ? AND property_id = ?");
                $stmt->execute([$hashed_password, $id, $property_id]);
                $success_message = "Password reset successfully!";
            } catch (PDOException $e) {
                $error_message = "Error resetting password: " . $e->getMessage();
            }
        }
    }
}

// Fetch all gate personnel for this property
$stmt = $pdo->prepare("SELECT * FROM gate_personnel WHERE property_id = ? ORDER BY created_at DESC");
$stmt->execute([$property_id]);
$personnel_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Personnel Management - HomeSync</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .main-content {
            margin-left: 260px;
            padding: 24px;
        }
        
        .page-header {
            margin-bottom: 32px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #01172e;
            margin-bottom: 8px;
        }
        
        .page-subtitle {
            font-size: 14px;
            color: #6c87a8;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(4, 97, 211, 0.1);
            margin-bottom: 24px;
        }
        
        .card-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #01172e;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #3a506b;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d4e5ff;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #1867ff;
            box-shadow: 0 0 0 3px rgba(24, 103, 255, 0.1);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: #1867ff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0461d3;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e4f0ff;
        }
        
        .table th {
            background: #f0f7ff;
            font-weight: 600;
            color: #01172e;
        }
        
        .table tbody tr:hover {
            background: #f9fbfd;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Gate Personnel Management</h1>
            <p class="page-subtitle">Manage gate access and personnel credentials</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add New Personnel -->
        <div class="card">
            <h2 class="card-title"><i class="fas fa-user-plus"></i> Add New Gate Personnel</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" minlength="6" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Personnel
                </button>
            </form>
        </div>
        
        <!-- Personnel List -->
        <div class="card">
            <h2 class="card-title"><i class="fas fa-users"></i> Current Personnel (<?php echo count($personnel_list); ?>)</h2>
            <?php if (empty($personnel_list)): ?>
                <p style="color: #6c87a8;">No gate personnel added yet. Add the first one above.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personnel_list as $person): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($person['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($person['username']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($person['created_at'])); ?></td>
                                <td><span class="badge badge-success">Active</span></td>
                                <td>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="resetPassword(<?php echo $person['id']; ?>, '<?php echo htmlspecialchars($person['full_name']); ?>')">
                                        <i class="fas fa-key"></i> Reset Password
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this personnel?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $person['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function resetPassword(id, name) {
            const newPassword = prompt(`Reset password for ${name}.\n\nEnter new password (minimum 6 characters):`);
            if (newPassword && newPassword.length >= 6) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="id" value="${id}">
                    <input type="hidden" name="new_password" value="${newPassword}">
                `;
                document.body.appendChild(form);
                form.submit();
            } else if (newPassword !== null) {
                alert('Password must be at least 6 characters long.');
            }
        }
    </script>
</body>
</html>
