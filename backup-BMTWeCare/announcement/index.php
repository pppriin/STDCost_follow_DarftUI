<?php
session_start();
require_once '../config/conn.php';

// ถ้าล็อกอินแล้วให้ redirect ไป management_announ.php
if (isset($_SESSION['user_id'])) {
    // header("Location: Testlink.php");
    header("Location: management_announ.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $sql = "SELECT * FROM user_announ WHERE Username = ? AND Password = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $username, $password);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($user = mysqli_fetch_assoc($result)) {
                // บันทึก session
                $_SESSION['user_id'] = $user['Id'];
                $_SESSION['username'] = $user['Username'];
                // เพิ่มข้อมูลอื่น ๆ ที่ต้องการ

                // header("Location: Testlink.php");
                header("Location: management_announ.php");
                exit();
            } else {
                $error = "Username or Password incorrect.";
            }

            mysqli_stmt_close($stmt);
        } else {
            $error = "Database error.";
        }
    } else {
        $error = "Please enter username and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Company Announcement</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="../image/png" href="../image/favicon.png">
    <link rel="stylesheet" href="../css/indexannoun.css">
</head>
<body>
    <div class="login-container">
        <div class="login-image">
            <img src="../image/logo-announ.jpg" width="100%" alt="Login Illustration">
            <!-- <a href="#" class="create-account">Create an account</a> -->
        </div>
        <div class="login-form">
            <h2 style="text-align: center;">Management Announcement</h2>
            <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>
            <form method="POST" action="">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="input-group password-input">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggle-icon"></i>
                    </button>
                </div>
                <button type="submit" class="login-btn">Log in</button>
            </form>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // เพิ่มเอฟเฟกต์การกด
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            const button = document.querySelector('.login-btn');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });
            
            button.addEventListener('mousedown', function() {
                this.style.transform = 'translateY(0) scale(0.98)';
            });
            
            button.addEventListener('mouseup', function() {
                this.style.transform = 'translateY(-3px) scale(1)';
            });
        });
    </script>
</body>
</html>