from playwright.sync_api import sync_playwright, Page, expect
import re

def debug_add_product_page(page: Page):
    """
    This script logs in and navigates to the add product page to capture a screenshot for debugging.
    """
    # 1. Log in
    page.goto("http://localhost:8000/admin/index.php")
    page.locator('input[name="username"]').fill("admin")
    page.locator('input[name="password"]').fill("password")
    page.locator('button[type="submit"]').click()
    expect(page).to_have_url(re.compile(r".*dashboard\.php$"))

    # 2. Navigate to Add Product page
    page.goto("http://localhost:8000/admin/add_product.php")

    # 3. Take screenshot
    page.screenshot(path="jules-scratch/verification/debug_add_product.png")
    print("Screenshot of add_product.php taken.")


def main():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        debug_add_product_page(page)
        browser.close()

if __name__ == "__main__":
    main()
