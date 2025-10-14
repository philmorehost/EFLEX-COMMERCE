from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Navigate to the checkout page
    page.goto("http://localhost:8080/checkout.php")

    # Wait for the navigation to the login page
    page.wait_for_url("**/login.php")

    # Log in
    page.fill("input[name='username']", "testuser")
    page.fill("input[name='password']", "password")
    page.click("input[type='submit']")

    # Wait for the navigation back to the checkout page
    page.wait_for_url("**/checkout.php")

    # Take a screenshot of the checkout page
    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)