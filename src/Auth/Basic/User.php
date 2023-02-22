<?php

/**
 * 
 * @author Tibor Csik <csulok0000@gmail.com>
 */

namespace Csulok0000\DevTools\Auth\Basic;

class User {
    
    /**
     * 
     * @param string $username
     * @param string $password
     */
    public function __construct(public readonly string $username, public readonly string $password) {
        
    }
}
