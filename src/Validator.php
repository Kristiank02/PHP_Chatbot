<?php
declare(strict_types=1);

class Validator
{
    /** Validates email adress */
    public function validateEmail(string $email): string
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Valid email adress: $email";
        }
        return "Invalid email adress: $email<br>Error: email must use format example@email.com";
    }

    /** Validate password */
    public function validatePassword(string $password): string
    {
        $error = [];

        if (strlen($password) < 9) {
            $error[] = "must be at least 9 letters";
        }
        if (!preg_match('/[A-ZÆØÅ]/u', $password)) {
            $error[] = "must contain at least one upper case letter";
        }
        if (preg_match_all('/[0-9]/', $password) < 2) {
            $error[] = "must contain at least two numbers";
        }
        if (!preg_match('/[\W_]/', $password)) {
            $error[] = "must contain at least one special character";
        }

        if (empty($error)) {
            return "Valid password";
        }

        return "Invalid password: $password<br>Error: " . implode(", ", $error) . ".";
    }
}
