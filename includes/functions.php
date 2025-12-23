<?php
// includes/functions.php

/**
 * Renders FontAwesome stars based on a numeric rating (1-5)
 * Handles null/zero values gracefully
 */
function render_stars($rating) {
    $rating = (float)($rating ?? 5.0); // Default to 5 stars if null
    
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

    $html = '<div class="star-rating text-warning d-inline-block" title="Rating: '.$rating.'">';
    for ($i = 0; $i < $full_stars; $i++) $html .= '<i class="fas fa-star"></i>';
    if ($half_star) $html .= '<i class="fas fa-star-half-alt"></i>';
    for ($i = 0; $i < $empty_stars; $i++) $html .= '<i class="far fa-star"></i>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Robust XSS Prevention
 */
function sanitize_review($text) {
    if (empty($text)) return "";
    // strip_tags is an extra layer of defense against sneaky <script> injections
    return htmlspecialchars(strip_tags(trim($text)), ENT_QUOTES, 'UTF-8');
}