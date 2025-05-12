<?php
function handleLogin(mysqli $connection, array &$errors, array &$values): bool {
    if (empty($_POST['loginEmail'])) return false;

    $email = $_POST['loginEmail'];
    $password = $_POST['loginPassword'];
    $values['email'] = htmlspecialchars($email);

    $query = "SELECT id, haslo FROM uzytkownicy WHERE email = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        if ($password === $user['haslo']) {
            $_SESSION['user_id'] = $user['id'];
            loadUserCart($connection, $user['id']);
            header("Location: home.php");
            exit();
        } else {
            $errors['password'] = "Nieprawidłowe hasło.";
        }
    } else {
        $errors['email'] = "Nie znaleziono konta z tym adresem email.";
    }

    mysqli_stmt_close($stmt);
    return true;
}
?>