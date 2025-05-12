<?php
function handleRegistration(mysqli $connection, array &$errors, array &$values): bool {
    if (empty($_POST['username']) || empty($_POST['email']) || 
        empty($_POST['password']) || empty($_POST['passwordConfirm'])) {
        return false;
    }

    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password_confirm = $_POST['passwordConfirm'];
    $values['register_email'] = htmlspecialchars($email);
    $values['register_username'] = htmlspecialchars($username);

    if ($password !== $password_confirm) {
        $errors['register_password'] = "Hasła nie są zgodne.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{2,32}$/', $password)) {
        $errors['register_password'] = "Hasło musi mieć od 2 do 32 znaków, w tym małą literę, dużą literę, cyfrę i znak specjalny.";
    }

    $query = "SELECT id FROM uzytkownicy WHERE email = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_fetch_assoc($result)) {
        $errors['register_email'] = "Ten adres email jest już zarejestrowany.";
    }
    mysqli_stmt_close($stmt);

    if (!empty($errors['register_email']) || !empty($errors['register_password'])) {
        return true;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $user_query = "INSERT INTO uzytkownicy (nazwa_uzytkownika, email, haslo) VALUES (?, ?, ?)";
    $user_stmt = mysqli_prepare($connection, $user_query);
    mysqli_stmt_bind_param($user_stmt, 'sss', $username, $email, $hashed_password);

    if (!mysqli_stmt_execute($user_stmt)) {
        $errors['register_username'] = "Wystąpił problem podczas rejestracji użytkownika. Spróbuj ponownie.";
        mysqli_stmt_close($user_stmt);
        return true;
    }

    $user_id = mysqli_insert_id($connection);
    $client_query = "INSERT INTO klienci (uzytkownik_id) VALUES (?)";
    $client_stmt = mysqli_prepare($connection, $client_query);
    mysqli_stmt_bind_param($client_stmt, 'i', $user_id);

    if (mysqli_stmt_execute($client_stmt)) {
        $_SESSION['user_id'] = $user_id;
        header("Location: home.php");
        exit();
    } else {
        $errors['register'] = "Wystąpił problem podczas tworzenia konta klienta. Spróbuj ponownie.";
    }

    mysqli_stmt_close($client_stmt);
    mysqli_stmt_close($user_stmt);
    return true;
}
?>