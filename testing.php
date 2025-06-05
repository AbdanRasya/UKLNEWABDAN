<?php
// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "bengkel"; // Adjust based on your actual database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $table = $_POST['table'] ?? '';
    
    // Handle delete action
    if ($action == 'delete' && !empty($_POST['id'])) {
        $id = $_POST['id'];
        $sql = "DELETE FROM $table WHERE id = $id";
        if ($conn->query($sql) === TRUE) {
            $message = "Record deleted successfully";
        } else {
            $error = "Error deleting record: " . $conn->error;
        }
    }
    
    // Handle add/edit actions
    if (($action == 'add' || $action == 'edit') && !empty($table)) {
        $fields = [];
        $values = [];
        
        // Filter out action and table fields
        foreach ($_POST as $key => $value) {
            if ($key != 'action' && $key != 'table' && $key != 'id') {
                $fields[] = $key;
                $values[] = "'" . $conn->real_escape_string($value) . "'";
            }
        }
        
        if ($action == 'add') {
            $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
        } else {
            $id = $_POST['id'];
            $updates = [];
            for ($i = 0; $i < count($fields); $i++) {
                $updates[] = $fields[$i] . " = " . $values[$i];
            }
            $sql = "UPDATE $table SET " . implode(', ', $updates) . " WHERE id = $id";
        }
        
        if ($conn->query($sql) === TRUE) {
            $message = ($action == 'add') ? "Record added successfully" : "Record updated successfully";
        } else {
            $error = "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Get current table from URL
$currentTable = $_GET['table'] ?? 'users';
$validTables = ['users', 'products', 'pesanan', 'layanan', 'stok', 'ulasan', 'hubungi'];

if (!in_array($currentTable, $validTables)) {
    $currentTable = 'users';
}

// Get table structure
$tableStructure = [];
$result = $conn->query("DESCRIBE $currentTable");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tableStructure[] = $row;
    }
}

// Get table data
$tableData = [];
$result = $conn->query("SELECT * FROM $currentTable");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tableData[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 200px;
            background-color: #333;
            color: white;
            padding: 20px 0;
        }
        
        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        
        .sidebar li {
            padding: 10px 20px;
        }
        
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
        }
        
        .sidebar li:hover {
            background-color: #444;
        }
        
        .content {
            flex: 1;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table, th, td {
            border: 1px solid #ddd;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            width: 400px;
            max-width: 90%;
        }
        
        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 20px;
            cursor: pointer;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 3px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <?php foreach ($validTables as $table): ?>
                <li <?php if ($currentTable == $table) echo 'style="background-color: #444;"'; ?>>
                    <a href="?table=<?php echo $table; ?>"><?php echo ucfirst($table); ?></a>
                </li>
                <?php endforeach; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="content">
            <div class="header">
                <h1>Manage <?php echo ucfirst($currentTable); ?></h1>
                <button class="btn btn-primary" onclick="openAddModal()">Add New</button>
            </div>
            
            <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <?php foreach ($tableStructure as $column): ?>
                        <th><?php echo $column['Field']; ?></th>
                        <?php endforeach; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableData as $row): ?>
                    <tr>
                        <?php foreach ($row as $key => $value): ?>
                        <td><?php echo $value; ?></td>
                        <?php endforeach; ?>
                        <td>
                            <button class="btn btn-primary" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="table" value="<?php echo $currentTable; ?>">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addModal')">&times;</span>
            <h2>Add New <?php echo ucfirst($currentTable); ?></h2>
            <form method="post">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="table" value="<?php echo $currentTable; ?>">
                
                <?php foreach ($tableStructure as $column): ?>
                    <?php if ($column['Field'] != 'id'): ?>
                    <div class="form-group">
                        <label for="<?php echo $column['Field']; ?>"><?php echo ucfirst($column['Field']); ?></label>
                        <input type="text" id="<?php echo $column['Field']; ?>" name="<?php echo $column['Field']; ?>" 
                               <?php if ($column['Null'] == 'NO') echo 'required'; ?>>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('addModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2>Edit <?php echo ucfirst($currentTable); ?></h2>
            <form method="post" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="table" value="<?php echo $currentTable; ?>">
                <input type="hidden" name="id" id="edit_id">
                
                <?php foreach ($tableStructure as $column): ?>
                    <?php if ($column['Field'] != 'id'): ?>
                    <div class="form-group">
                        <label for="edit_<?php echo $column['Field']; ?>"><?php echo ucfirst($column['Field']); ?></label>
                        <input type="text" id="edit_<?php echo $column['Field']; ?>" name="<?php echo $column['Field']; ?>" 
                               <?php if ($column['Null'] == 'NO') echo 'required'; ?>>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-success">Update</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('editModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Open modal functions
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function openEditModal(data) {
            document.getElementById('edit_id').value = data.id;
            
            <?php foreach ($tableStructure as $column): ?>
                <?php if ($column['Field'] != 'id'): ?>
                document.getElementById('edit_<?php echo $column['Field']; ?>').value = data.<?php echo $column['Field']; ?>;
                <?php endif; ?>
            <?php endforeach; ?>
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        // Close modal function
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>