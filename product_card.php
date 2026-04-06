<?php 
// Ensure $product is defined
if (!isset($product)) return;

// Format price
$price = number_format($product['product_price'], 2);
?>

<div class="product-card">
    <?php if ($product['is_new']): ?>
        <div class="product-badge">New</div>
    <?php endif; ?>
    
    <?php if ($product['is_bestseller']): ?>
        <div class="product-badge bestseller">Bestseller</div>
    <?php endif; ?>
    
    <div class="product-image">
        <img src="<?= htmlspecialchars($product['product_image']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
    </div>
    
    <div class="product-content">
        <h3><?= htmlspecialchars($product['product_name']) ?></h3>
        
        <div class="product-price">E<?= $price ?></div>
        
        <?php if (!empty($product['specs_text'])): ?>
            <div class="product-specs">
                <ul>
                    <?php 
                    $specs = explode("\n", trim($product['specs_text']));
                    foreach ($specs as $spec): 
                        if (!empty(trim($spec))):
                    ?>
                        <li><i class="fas fa-check-circle"></i> <?= htmlspecialchars(trim($spec)) ?></li>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="products.php" id="add-to-cart-form-<?= $product['product_id'] ?>">
            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
            <input type="hidden" name="add_to_cart" value="1">
            <input type="hidden" name="quantity" value="1">
        </form>
        
        <div class="product-actions">
            <button class="btn-product" 
                    onclick="addToCart(<?= $product['product_id'] ?>)">
                <i class="fas fa-shopping-cart"></i> Add to Cart
            </button>
            
            <button class="btn-product btn-specs" 
                    onclick="showSpecs(<?= $product['product_id'] ?>, '<?= htmlspecialchars($product['product_name']) ?>', <?= htmlspecialchars(json_encode($product['specs_array'])) ?>)">
                <i class="fas fa-info-circle"></i> Specs
            </button>
        </div>
    </div>
</div>