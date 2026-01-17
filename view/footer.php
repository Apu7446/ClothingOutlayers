<?php
/**
 * ========================================
 * FOOTER VIEW
 * ========================================
 * This file closes the HTML structure opened in header.php
 * 
 * Contains:
 * - Closing </main> tag (opened in header.php)
 * - Footer with copyright
 * - JavaScript file inclusion
 * - Closing </body> and </html> tags
 * 
 * This file is included at the bottom of every page view.
 */
?>

<!-- Close main content container (opened in header.php) -->
</main>

<!-- Site Footer -->
<footer class="site-footer">
  <div class="container footer-inner">
    <!-- Dynamic copyright year using PHP date() function -->
    <p>© <?= date('Y') ?> ClothingShop</p>
  </div>
</footer>

<!-- JavaScript file for theme toggle and other functionality -->
<script src="js/script.js"></script>

<!-- Close HTML structure -->
</body>
</html>