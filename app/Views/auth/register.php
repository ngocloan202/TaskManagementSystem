<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký</title>
    <link rel="stylesheet" href="../../../public/css/tailwind.css">
    <style>
        body {
            background-color: #E8E9FE;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .registerContainer {
            width: 100%;
            max-width: 400px;
            height: 520px;
            box-shadow: 0 16px 32px 0 rgba(1, 3, 41, 0.1);
        }

        .mainTitle {
            color: #3189DE;
            margin: 32px 0 16px;
        }

        .registerButton{
            background-color: #2C77E8;
            transition: background-color 0.3s;
            cursor: pointer;
        }

        .registerButton:hover{
            background-color: #bddef5;
        }

        .register-input-group {
            padding: 6px;
            height: 45px;
        }
    </style>
    
</head>

<body>
    <?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once "../../../config/database.php";
$message = "";

if (!$connect) { die("Không kết nối được DB: " . mysqli_connect_error()); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm-password'];

    if ($username == "" || $email == "" || $password == "" || $confirm == "" || $fullname == "") {
        $message = "Vui lòng nhập đầy đủ thông tin!";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email không hợp lệ!";
    } elseif (strlen($password) < 6) {
        $message = "Mật khẩu phải có ít nhất 6 ký tự!";
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $message = "Tên người dùng phải từ 3 đến 20 ký tự!";
    } elseif (strlen($fullname) < 3 || strlen($fullname) > 100) {
        $message = "Họ và tên phải từ 3 đến 100 ký tự!";
    }
    elseif ($password !== $confirm) {
        $message = "Mật khẩu không khớp!";
    }
    else {
        $sql = "SELECT * FROM Users WHERE Username='$username' OR Email='$email'";
        $result = $connect->query($sql);
        if ($result->num_rows > 0) {
            $message = "Username hoặc Email đã tồn tại!";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO Users (Username, Password, Email, Role, FullName, PhoneNumber, Address)
                    VALUES ('$username', '$hashed', '$email', 'USER', '$fullname', '', '')";
            if ($connect->query($sql) === TRUE) {
                $message = "Đăng ký thành công! <a href='login.php'>Đăng nhập</a>";
            } else {
                $message = "Lỗi đăng ký: " . $connect->error;
            }
        }
    }
}
?>
    <div class="registerContainer bg-white rounded-lg relative">
        <!-- Logo -->
        <div class="logoContainer absolute -top-16 left-1/2" style="transform: translateX(-50%);">
            <img src="../../images/cubeflow-logo.png" alt="Logo"
                class="w-24 h-24 rounded-full border-2 border-[#A6A9FC]">
        </div>

        <h2 class="mainTitle text-center text-2xl font-bold">Cube Flow</h2>
        <form action="" method="POST" class="px-10">
            <!-- Full Name field -->
            <div class="bg-blue-300 rounded-lg p-3 flex items-center mb-4 register-input-group">
                <span class="text-gray-600 mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" clip-rule="evenodd" />
                    </svg>
                </span>
                <input class="outline-none font-semibold" type="text" id="fullname" name="fullname" placeholder="Họ và tên">
            </div>
            <!-- Email field -->
            <div class="bg-blue-300 rounded-lg p-3 flex items-center mb-4 register-input-group">
                <span class="text-gray-600 mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path
                            d="M1.5 8.67v8.58a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3V8.67l-8.928 5.493a3 3 0 0 1-3.144 0L1.5 8.67Z" />
                        <path
                            d="M22.5 6.908V6.75a3 3 0 0 0-3-3h-15a3 3 0 0 0-3 3v.158l9.714 5.978a1.5 1.5 0 0 0 1.572 0L22.5 6.908Z" />
                    </svg>

                </span>
                <input class="outline-none font-semibold" type="email" id="email" name="email" placeholder="Email">
            </div>

            <div class="bg-blue-300 rounded-lg p-3 flex items-center mb-4 register-input-group">
                <span class="text-gray-600 mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd"
                            d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"
                            clip-rule="evenodd" />
                    </svg>

                </span>
                <input class="outline-none font-semibold" type="text" id="username" name="username" placeholder="Tên người dùng">
            </div>

            <div class="bg-blue-300 rounded-lg p-3 flex items-center mb-4 register-input-group">
                <span class="text-gray-600 mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd"
                            d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z"
                            clip-rule="evenodd" />
                    </svg>

                </span>
                <input class="outline-none font-semibold" type="password" id="password" name="password" placeholder="Mật khẩu">
            </div>

            <div class="bg-blue-300 rounded-lg p-3 flex items-center mb-4 register-input-group">
                <span class="text-gray-600 mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd"
                            d="M12.516 2.17a.75.75 0 0 0-1.032 0 11.209 11.209 0 0 1-7.877 3.08.75.75 0 0 0-.722.515A12.74 12.74 0 0 0 2.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 0 0 .374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 0 0-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08Zm3.094 8.016a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                            clip-rule="evenodd" />
                    </svg>
                </span>
                <input class="outline-none font-semibold" type="password" id="confirm-password" name="confirm-password" placeholder="Xác nhận mật khẩu">
            </div>

            <button type="submit" class="registerButton w-full px-2 py-2 rounded-lg font-semibold text-white">Đăng ký</button>
            <?php if (!empty($message)): ?>
                <p class="text-center mt-2 text-red-600 font-semibold"><?= $message ?></p>
            <?php endif; ?>
            <p class="text-center mt-4">
                Bạn có tài khoản? <a href="login.php" class="hover:underline font-semibold" style="color: #2F42C0;">Đăng nhập tại đây</a>
            </p>
        </form>
    </div>
</body>

</html>
