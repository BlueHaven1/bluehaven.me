<?php
// Get cart count if user is logged in
function getCartIconHtml($user) {
    if (!$user) {
        return '';
    }
    
    $html = '<a href="cart.php" class="cart-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>';
    
    // Get cart count
    $cartCountResponse = authenticatedRequest(
        '/rest/v1/cart_items?user_id=eq.' . urlencode($user['id']) . '&select=id',
        'GET'
    );
    
    $cartCount = 0;
    if ($cartCountResponse['statusCode'] === 200) {
        $cartCount = count($cartCountResponse['data']);
    }
    
    if ($cartCount > 0) {
        $html .= '<span class="cart-count">' . $cartCount . '</span>';
    }
    
    $html .= '</a>';
    
    return $html;
}

// CSS for cart icon
function getCartIconCss() {
    return '
        .cart-icon {
            position: relative;
            color: var(--text-primary);
            margin-right: 0.5rem;
            transition: color 0.3s ease;
        }
        
        .cart-icon:hover {
            color: var(--accent-color);
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--accent-color);
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    ';
}
?>
