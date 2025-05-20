<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../../../public/css/tailwind.css">
    <style>
        body {
            background-color: #E8E9FE;
            min-height: 100vh;
        }

        .registerContainer {
            margin: 7rem auto;
            width: 100%;
            max-width: 400px;
            padding: 1rem 0;
            box-shadow: 0 16px 32px 0 rgba(1, 3, 41, 0.1);
            position: relative;
            z-index: 10;
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

        .hidden { display: none !important; }

        #successModal{
            position:fixed;
            top:0;
            right:0;
            bottom:0;
            left:0;
            background-color: rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        #successModal.hidden { display: none !important; }

        #successModal-container{
            background-color: #fff;
            width: 100%;
            max-width: 500px;
            padding: 0.75rem;
            border-radius: 1rem;
            position: relative;
            animation: modalFadeIn ease 0.4s;

        }
    </style>
    
</head>

<body>
    <?php
    ini_set("display_errors", 1);
    ini_set("display_startup_errors", 1);
    error_reporting(E_ALL);

    session_start();
    include_once "../../../config/database.php";
    $message = "";
    $registerSuccess = false;

    if (!$connect) {
      die("Could not connect to DB: " . $connect->connect_error);
      exit();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $fullname = trim($_POST["fullname"]);
      $username = trim($_POST["username"]);
      $email = trim($_POST["email"]);
      $password = $_POST["password"];
      $confirm = $_POST["confirm-password"];

      if ($username == "" || $email == "" || $password == "" || $confirm == "" || $fullname == "") {
        $message = "Please fill in all fields!";
      } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format!";
      } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters!";
      } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $message = "Username must be between 3 and 20 characters!";
      } elseif (strlen($fullname) < 3 || strlen($fullname) > 100) {
        $message = "Full name must be between 3 and 100 characters!";
      } elseif ($password !== $confirm) {
        $message = "Passwords do not match!";
      } else {
        $sql = "SELECT * FROM Users WHERE Username='$username' OR Email='$email'";
        $result = $connect->query($sql);
        if ($result->num_rows > 0) {
          $message = "Username or Email already exists!";
        } else {
          $hashed = md5($password);
          $sql = "INSERT INTO Users (Username, Password, Email, Role, FullName, PhoneNumber, Avatar)
                    VALUES ('$username', '$hashed', '$email', 'USER', '$fullname', '', '')";
          if ($connect->query($sql) === true) {
            $registerSuccess = true;
          } else {
            $message = "Registration error: " . $connect->error;
          }
        }
      }
    }
    ?>
    <div class="registerContainer bg-white rounded-lg relative">
        <!-- Logo -->
        <div class="logoContainer absolute -top-16 left-1/2" style="transform: translateX(-50%);">
            <img src="../../../public/images/cubeflow-logo.png" alt="Logo"
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
                <input class="outline-none font-semibold" type="text" id="fullname" name="fullname" placeholder="Full name">
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
                <input class="outline-none font-semibold" type="text" id="username" name="username" placeholder="Username">
            </div>

            <div class="bg-blue-300 rounded-lg p-3 flex items-center mb-4 register-input-group">
                <span class="text-gray-600 mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd"
                            d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z"
                            clip-rule="evenodd" />
                    </svg>

                </span>
                <input class="outline-none font-semibold" type="password" id="password" name="password" placeholder="Password">
            </div>

            <div class="bg-blue-300 rounded-lg p-3 flex items-center mb-4 register-input-group">
                <span class="text-gray-600 mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd"
                            d="M12.516 2.17a.75.75 0 0 0-1.032 0 11.209 11.209 0 0 1-7.877 3.08.75.75 0 0 0-.722.515A12.74 12.74 0 0 0 2.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 0 0 .374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 0 0-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08Zm3.094 8.016a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                            clip-rule="evenodd" />
                    </svg>
                </span>
                <input class="outline-none font-semibold" type="password" id="confirm-password" name="confirm-password" placeholder="Confirm password">
            </div>

            <button type="submit" class="registerButton w-full px-2 py-2 rounded-lg font-semibold text-white">Register</button>
            <?php if (!empty($message)): ?>
                <p class="text-center mt-2 text-red-600 font-semibold"><?= $message ?></p>
            <?php endif; ?>
            <p class="text-center mt-4">
                Already have an account? <a href="login.php" class="hover:underline font-semibold" style="color: #2F42C0;">Login here</a>
            </p>
        </form>
    </div>

    
        <!-- Modal Success -->
<div id="successModal" class="hidden">
    <div id="successModal-container">
        <div class="bg-white rounded-xl shadow-lg border border-[#A6A9FC] text-center relative p-6">
            <button onclick="document.getElementById('successModal').classList.add('hidden')" class="absolute top-2 right-2 text-2xl font-bold text-gray-400 hover:text-gray-700">&times;</button>
            <img src="../../images/cubeflow-logo.png" alt="Cube Flow" class="w-20 h-20 mx-auto mb-4">
            <h2 class="text-2xl font-bold text-[#2C77E8] mb-2">🎉 Registration successful!</h2>
            <p class="text-gray-700 mb-4">Your account has been created. You can now log in.</p>
            <a href="login.php" class="inline-block bg-[#2C77E8] hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition">Go to login</a>
        </div>
    </div>
</div>
        
    <?php if ($registerSuccess): ?>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("successModal");
    modal.classList.remove("hidden");
    document.querySelector('form').reset();
  });
</script>
<?php endif; ?>
</body>

</html>
