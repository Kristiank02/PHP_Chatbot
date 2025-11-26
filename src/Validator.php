<?php

class Validator
{
    /**
     * Checks if email is valid using FILTER_VALIDATE_EMAIL
     * 
     * @param string $email
     * @return bool - Valid email return True
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Checks if password towards certain criteria
     * 
     * @param string $password
     * @return array $errors - List invalid password reasons
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        if (!preg_match('/[A-ZÆØÅ]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return $errors;
    }

    /**
     * Checks if password is valid
     * 
     * @param string $password
     * @return bool - True if $errors[] is empty
     */
    public static function isPasswordValid(string $password): bool
    {
        return empty(self::validatePassword($password));
    }
}