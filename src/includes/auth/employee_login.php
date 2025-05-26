<?php
function handleEmployeeLogin(mysqli $connection, array &$errors, array &$values): bool {
    if (empty($_POST['employeeId'])) return false;

    $employee_id = $_POST['employeeId'];
    $employee_password = $_POST['employeePassword'];
    $values['employee_id'] = htmlspecialchars($employee_id);

    $query = "
        SELECT pracownicy.*, uzytkownicy.haslo 
        FROM pracownicy 
        JOIN uzytkownicy ON pracownicy.uzytkownik_id = uzytkownicy.id 
        WHERE pracownicy.identyfikator = ?
    ";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 's', $employee_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($employee = mysqli_fetch_assoc($result)) {
        if (password_verify($employee_password, $employee['haslo'])) {
            $_SESSION['user_id'] = $employee['uzytkownik_id'];
            $_SESSION['employee_id'] = $employee['identyfikator'];;
            loadUserCart($connection, $employee['uzytkownik_id']);
            header("Location: home.php");
            exit();
        } else {
            $errors['employee_password'] = "Nieprawidłowe hasło.";
        }
    } else {
        $errors['employee'] = "Nie znaleziono pracownika z tym ID.";
    }

    mysqli_stmt_close($stmt);
    return true;
}
?>  