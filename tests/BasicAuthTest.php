<?php

/**
 * 
 * @author Tibor Csik <csulok0000@gmail.com>
 */

use PHPUnit\Framework\TestCase;

use Csulok0000\DevTools\Auth\Basic\Auth;
use Csulok0000\DevTools\Auth\Basic\AdapterInterface;
use Csulok0000\DevTools\Auth\Basic\User;

class BasicAuthTest extends TestCase {
    
    protected static AdapterInterface $adapter;
    
    public static function setUpBeforeClass(): void {
        self::$adapter = new Class() implements AdapterInterface {
            public function authenticate(User $user): bool {
                return $user->username == 'TestUser' && $user->password == 'TestPW';
            }
        };
    }
    
    public function testBasicAuthUser() {
        $user = new User('TestUser', 'TestPW');
        
        $this->assertSame('TestUser', $user->username);
        $this->assertSame('TestPW', $user->password);
    }
    
    public function testBasicAuthUserChangeName() {
        $user = new User('TestUser', 'TestPW');
        
        $this->expectException(Error::class);
        $user->username = 'Alma';
    }
    
    public function testBasicAuthUserChangePass() {
        $user = new User('TestUser', 'TestPW');
        
        $this->expectException(Error::class);
        $user->password = 'Alma';
    }
    
    /**
     * 
     * @runInSeparateProcess
     */
    public function testFailAuth() {
        $auth = new Class (self::$adapter) extends Auth {
            protected function makeBasicAuth(string $message, string $errorMessage): void {}
            protected function header(string $header) {}
        };
        
        // Null ha még nem lett beállítva
        $this->assertNull($auth->auth('A', 'B'));
        
        $_SERVER['PHP_AUTH_USER'] = 'aaa';
        // False ha volt adat, de sikertelen
        $this->assertFalse($auth->auth('A', 'B'));
        
    }
    
    public function testSuccessAuth() {
        $auth = new Auth(self::$adapter);
        
        $_SERVER['PHP_AUTH_USER'] = 'TestUser';
        $_SERVER['PHP_AUTH_PW'] = 'TestPW';
        
        $user = $auth->auth('A', 'B');
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('TestUser', $user->username);
        $this->assertSame('TestPW', $user->password);
        
    }
    
    
}
