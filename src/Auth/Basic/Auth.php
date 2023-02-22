<?php

/**
 * 
 * @author Tibor Csik <csulok0000@gmail.com>
 */

namespace Csulok0000\DevTools\Auth\Basic;

class Auth {
    
    public function __construct(public readonly AdapterInterface $adapter) {
        ;
    }
    
    /**
     * 
     * Basic auth kérése és a sikeres azonosítást követően a felhasználó visszaadása
     * 
     * Használata:
     *      if (!($user = $basicAuth->auth('message...', 'error...'))) {
     *          exit;
     *      }
     * 
     * 
     * @param string $message Azonosításhoz kiírt üzenet
     * @param string $errorMessage Sikertelen azonosítás esetén megjelenő üzenet(Böngészőben a mégsére kattintva)
     * @return User|null|false User: sikeres azonosítás, false: sikertelen azonosítás, null: nem történt azonosítás
     * @throws HeaderSentException
     */
    public function auth(string $message, string $errorMessage): User|null|false {
        // Felhasználó létrehozása
        $user = new User($_SERVER['PHP_AUTH_USER'] ?? '', $_SERVER['PHP_AUTH_PW'] ?? '');
        
        // Azonosítás
        if ($this->adapter->authenticate($user)) {
            // Sikeres azonosítás esetén visszaadjuk a felhasználót
            return $user;
        }
        
        if (headers_sent()) {
            throw new HeaderSentException('Fejléc(header) már kiküldésre került!');
        }
        
        // Kimentre küldjük a header-t és kiírjuk az üzenetet
        $this->makeBasicAuth($message, $errorMessage);
        
        // Ha ideáig eljut akkor még nem volt azonosítás vagy sikertelen volt
        return isset($_SERVER['PHP_AUTH_USER']) || isset($_SERVER['PHP_AUTH_PW']) ? false : null;
    }
    
    /**
     * 
     * @param string $message
     * @param string $errorMessage
     * @return void
     */
    protected function makeBasicAuth(string $message, string $errorMessage): void {
        $this->header('WWW-Authenticate: Basic realm="' . $message . '"');
        $this->header('HTTP/1.0 401 Unauthorized');
        echo ($errorMessage);
    }
    
    /**
     * 
     * Teszteléshez/Mockoláshoz
     * 
     * @param string $header
     */
    protected function header(string $header) {
        header($header);
    }
}
