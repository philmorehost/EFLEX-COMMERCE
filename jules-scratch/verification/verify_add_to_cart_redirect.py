from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Navigate to a product detail page for a product without variants
    page.goto("http://localhost:8080/product_detail.php?id=1")

    # Click the "Add to Cart" button
    add_to_cart_button = page.locator("#add-to-cart-btn")
    add_to_cart_button.click()

    # Wait for the navigation to the cart page
    page.wait_for_url("**/cart.php")

    # Take a screenshot of the cart page
    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)