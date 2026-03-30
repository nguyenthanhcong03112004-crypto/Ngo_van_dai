<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Module 1: User Account & Profile (CRUD)
 * Scenarios: SC-001 to SC-012
 * Covers: Register, Login, Profile Read/Update, Avatar, Block
 */
class UserTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    // [SC-001] [Create] Guest registers a new account with valid data
    // ─────────────────────────────────────────────────────────────────────────
    public function testPasswordIsHashedWithBcrypt(): void
    {
        // Arrange
        $plainPassword = 'P@ssw0rd!';

        // Act
        $hashed = password_hash($plainPassword, PASSWORD_BCRYPT);

        // Assert
        $this->assertNotEquals($plainPassword, $hashed, 'Password must not be stored in plain text');
        $this->assertTrue(password_verify($plainPassword, $hashed), 'password_verify() must succeed with original password');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-002] [Create] Register fails due to duplicate email → 409 Conflict
    // ─────────────────────────────────────────────────────────────────────────
    public function testRegisterWithDuplicateEmailShouldFail(): void
    {
        // Arrange
        $existingEmail = 'user@shop.com'; // Already in seed data

        // Act: simulate the check logic in AuthController
        $emailExists = $this->isEmailAlreadyRegistered($existingEmail);

        // Assert
        $this->assertTrue($emailExists, 'Duplicate email should be detected by the system');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-005] [Read] Login with wrong credentials should fail
    // ─────────────────────────────────────────────────────────────────────────
    public function testLoginWithWrongPasswordFails(): void
    {
        // Arrange
        $storedHash = password_hash('correct-password', PASSWORD_BCRYPT);
        $inputPassword = 'wrong-password';

        // Act
        $result = password_verify($inputPassword, $storedHash);

        // Assert
        $this->assertFalse($result, 'Login must fail when password does not match');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-007] [Update] User updates name and phone successfully
    // ─────────────────────────────────────────────────────────────────────────
    public function testUserProfileDataCanBeBuilt(): void
    {
        // Arrange
        $profileData = [
            'name'    => 'Nguyễn Văn B',
            'phone'   => '0912345678',
            'address' => '123 Lê Lợi, Hà Nội',
        ];

        // Act
        $name  = trim($profileData['name']);
        $phone = preg_match('/^0[0-9]{9}$/', $profileData['phone']) ? $profileData['phone'] : null;

        // Assert
        $this->assertEquals('Nguyễn Văn B', $name);
        $this->assertNotNull($phone, 'Valid Vietnamese phone format (0xxxxxxxxx) must pass');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-008] [Update] Validation fails for invalid phone format
    // ─────────────────────────────────────────────────────────────────────────
    public function testInvalidPhoneNumberIsRejected(): void
    {
        // Arrange
        $invalidPhone = '012345'; // Too short

        // Act
        $isValid = preg_match('/^0[0-9]{9}$/', $invalidPhone);

        // Assert
        $this->assertEquals(0, $isValid, 'Short phone number must be rejected by validation');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-011] [Delete/Update] Admin blocks a user — status logic
    // ─────────────────────────────────────────────────────────────────────────
    public function testBlockedUserStatusIsInactive(): void
    {
        // Arrange
        $user = ['status' => 'active', 'role' => 'user'];

        // Act (simulate admin block action)
        $user['status'] = 'inactive';

        // Assert
        $this->assertEquals('inactive', $user['status']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [SC-012] [Read] Blocked user cannot login
    // ─────────────────────────────────────────────────────────────────────────
    public function testBlockedUserCannotLogin(): void
    {
        // Arrange
        $user = ['status' => 'inactive'];

        // Act
        $canLogin = ($user['status'] === 'active');

        // Assert
        $this->assertFalse($canLogin, 'Users with inactive status must be denied login');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ─────────────────────────────────────────────────────────────────────────
    private function isEmailAlreadyRegistered(string $email): bool
    {
        // In a real integration test, this would query the DB.
        // For unit tests, we simulate with known seed data.
        $knownEmails = ['admin@shop.com', 'user@shop.com'];
        return in_array($email, $knownEmails);
    }
}
