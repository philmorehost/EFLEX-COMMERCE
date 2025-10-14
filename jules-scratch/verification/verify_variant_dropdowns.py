from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Navigate to a product detail page with variants
    # I'll need to find a product with variants first. I'll assume product 2 has variants.
    page.goto("http://localhost:8080/product_detail.php?id=2")

    # Select an option from the first dropdown
    page.select_option("select.variant-select", index=1)

    # Wait for the price to update to be sure the javascript has run
    expect(page.locator("#product-price")).not_to_contain_text("12.00")

    # Take a screenshot of the page with the dropdowns
    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)