<?php

require_once (__DIR__ . '/../core/Model.php');

class User extends Model
{
    private function validateUsername($username): bool {
        if (!empty($username)) return true;
        $this->error_message = "Username cannot be empty";
        return false;
    }

    private function validateEmail($email): bool
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) return true;
        $this->error_message = 'Invalid email address';
        return false;
    }

    private function validatePassword($password, $confirm_password): bool
    {
        if ($password == '') {
            $this->error_message = 'Password cannot be empty';
            return false;
        }
        if ($confirm_password != '' && $password !== $confirm_password) {
            $this->error_message = 'Passwords do not match';
            return false;
        }
        if (strlen($password) < 9) {
            $this->error_message = 'Password must be at least 9 characters';
            return false;
        }
        return true;
    }

    /**
     * Check if user already exists
     */
    public function validateExistedUser($username): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM Users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            if ((bool)$user) {
                $this->error_message = 'User already exists';
                return false;
            }
            return true;
        } catch (PDOException $e) {
            $this->error_message = 'Failed to check if user already exists';
            return false;
        }
    }

    /**
     * @param $confirm_password string is empty by default to deal with the unit test
     */
    public function registerUser($username, $email, $password, $confirm_password = ''): bool
    {
        if (!$this->validateUsername($username)) return false;
        if (!$this->validateEmail($email)) return false;
        if (!$this->validatePassword($password, $confirm_password)) return false;
        if (!$this->validateExistedUser($username)) return false;
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $this->pdo->prepare("INSERT INTO Users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $password_hash,
            ]);
            return true;
        } catch (PDOException $e) {
            $this->error_message = 'Failed to register user (' . $e->getMessage() . ')';
            return false;
        }
    }

    /**
     * Authenticate user with raw and hashed password
     * @param string $password raw (unhashed) password
     */
    public function authenticateUser($username, $password): bool
    {
        if (!$this->validateUsername($username)) return false;
        if (!$this->validatePassword($password, $password)) return false;
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM Users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            if (!$user) {
                $this->error_message = 'User not found';
                return false;
            }
            if (!password_verify($password, $user['password'])) {
                $this->error_message = 'Invalid password';
                return false;
            }
            return true;
        } catch (PDOException $e) {
            $this->error_message = 'Failed to authenticate user: (' . $e->getMessage() . ')';
            return false;
        }
    }

    /**
     * Get user id by username
     */
    public function getUserId($username): int {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM Users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            if ($user === false) {
                $this->error_message = 'User not found';
                return -1;
            }
            return $user['id'];
        } catch (PDOException $e) {
            $this->error_message = 'Failed to get user id: (' . $e->getMessage() . ')';
            return -1;
        }
    }

    /**
     * Get username by user id
     */
    public function getUsername($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM Users WHERE id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            $user = $stmt->fetch();
            if ($user === false) {
                $this->error_message = 'User not found';
                return 0;
            }
            return $user['username'];
        } catch (PDOException $e) {
            $this->error_message = 'Failed to get user info: (' . $e->getMessage() . ')';
            return 0;
        }
    }
}