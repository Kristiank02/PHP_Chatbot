<?php
declare(strict_types=1);

/**
 * Validator class to validate user data
 * Validates emails and passwords
 */
class Validator
{
    /**
     * Vaildates email
     * 
     * Uses PHP's FILTER_VALIDATE_EMAIL to check for regular expression
     * 
     * @param string $email - Email to be validated
     * @return string - The error-message to indicate invalid email
     */
    public function validateEmail(string $email): string
    {
        // Uses filter_var with FILTER_VALIDATE_EMAIL for validation
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Valid email address: $email";
        }
        return "Invalid email address: $email<br>Error: email must use the format example@email.com";
    }

    /** 
     * Validates password
     * 
     * Password needs to meet certain requirements:
     * - 9 characters long
     * - At least 1 upper case letter
     * - At least 2 numbers
     * - At least 1 special character
     * 
     * @param string $password - Password to be validated
     * @return string - Message to indicate invalid password
     */
    public function validatePassword(string $password): string
    {
        // Array to collect error-messages
        $error = [];

        /**
         * Password requirements:
         * - length
         * - uppercase-letter
         * - numbers
         * - special character
         */
        if (strlen($password) < 9) {
            $error[] = "must be at least 9 characters long";
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

        // If $error[] is empty, password is valid
        if (empty($error)) {
            return "Valid password";
        }

        // Returns error-messages for each requirement that is unmet
        return "Invalid password: $password<br>Error: " . implode(", ", $error) . ".";
    }
}
