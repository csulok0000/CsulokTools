<?php

/**
 * 
 * @author Tibor Csik <csulok0000@gmail.com>
 */

namespace Csulok0000\DevTools;

interface ViewInterface {
    
    /**
     * 
     * @param string $template
     * @param array $context
     * @return string
     */
    public function render(string $template, array $context = []): string;
}
