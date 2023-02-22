<?php

/**
 * 
 * @author Tibor Csik <csulok0000@gmail.com>
 */

namespace Csulok0000\DevTools\Auth\Basic;

interface AdapterInterface {
    
    /**
     * 
     * A felhasználó azonosítása ezen a ponton történik. Válaszban true/false értékkel kell jelezni az azonosítás sikerességét.
     * 
     * @param User $user
     * @return bool Sikeres azonosítás esetén TRUE, minden más esetben FALSE
     */
    public function authenticate(User $user): bool;
}
