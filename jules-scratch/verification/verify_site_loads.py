from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Navigate to the products page
    page.goto("http://localhost:8080/products.php")

    # Wait for the page to load
    page.wait_for_load_state("networkidle")

    # Take a screenshot of the page
    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)