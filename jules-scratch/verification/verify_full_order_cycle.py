
import re
from playwright.sync_api import sync_playwright, Page, expect
import random
import string

def get_random_string(length):
    letters = string.ascii_lowercase
    return ''.join(random.choice(letters) for i in range(length))

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    try:
        # --- Admin Preamble ---

        # 1. Log in as admin
        page.goto("http://localhost:8000/admin/index.php")
        page.locator('input[name="username"]').fill("admin")
        page.locator('input[name="password"]').fill("password")
        page.get_by_role("button", name="Sign in").click()
        expect(page.get_by_role("heading", name="Admin Dashboard")).to_be_visible()

        # 2. Enable Bank Transfer
        page.goto("http://localhost:8000/admin/site_settings.php")
        page.locator('input[name="bank_transfer_enabled"]').check()
        page.get_by_role("button", name="Save All Settings").click()
        expect(page.get_by_text("Settings saved successfully.")).to_be_visible()

        # 3. Create a category
        category_name = f"Test Category {get_random_string(6)}"
        page.goto("http://localhost:8000/admin/manage_categories.php")
        page.locator('input[name="name"]').fill(category_name)
        page.get_by_role("button", name="Add Category").click()
        expect(page.get_by_text("Category added successfully.")).to_be_visible()

        # 4. Create a product
        product_name = f"Test Product {get_random_string(6)}"
        page.goto("http://localhost:8000/admin/add_product.php")
        page.locator('input[name="name"]').fill(product_name)
        page.locator('textarea[name="description"]').fill("This is a test product.")
        page.locator('input[name="price"]').fill("123")
        page.get_by_label("Category").select_option(label=category_name)
        page.get_by_label("Featured Product").check()
        page.get_by_role("button", name="Add Product").click()

        # 5. Log out admin
        page.goto("http://localhost:8000/logout.php")

        # --- Customer Journey ---

        # 6. Register a new user
        unique_user = f"testuser_{get_random_string(6)}"
        unique_email = f"{unique_user}@example.com"
        page.goto("http://localhost:8000/register.php")
        page.locator('input[name="username"]').fill(unique_user)
        page.locator('input[name="email"]').fill(unique_email)
        page.locator('input[name="password"]').fill("password")
        page.locator('input[name="confirm_password"]').fill("password")
        page.get_by_role("button", name="Submit").click()

        # 7. Find the created product and navigate to its page
        page.goto("http://localhost:8000/index.php")
        page.get_by_text(product_name).first.click()

        # 8. Add product to cart
        page.get_by_role("button", name="Add to Cart").click()

        # 9. Proceed to checkout
        page.goto("http://localhost:8000/checkout.php")

        # 10. Fill checkout form and place order
        page.locator('input[value="bank_transfer"]').check()
        page.get_by_role("button", name="Place Order").click()

        # --- Admin Verification ---

        # 11. Log back in as admin
        page.goto("http://localhost:8000/admin/index.php")
        page.locator('input[name="username"]').fill("admin")
        page.locator('input[name="password"]').fill("password")
        page.get_by_role("button", name="Sign in").click()
        expect(page.get_by_role("heading", name="Admin Dashboard")).to_be_visible()

        # 12. Go to manage orders page
        page.goto("http://localhost:8000/admin/manage_orders.php")

        # 13. Find the order and view details
        page.locator("a:has-text('View Details')").first.click()

        # 14. Verify and take screenshot
        expect(page.get_by_role("heading", name=re.compile(r"Order Details for #\d+"))).to_be_visible()
        page.screenshot(path="jules-scratch/verification/admin_order_detail_with_order.png")

    finally:
        browser.close()

with sync_playwright() as playwright:
    run(playwright)
