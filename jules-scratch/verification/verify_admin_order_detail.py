
import re
from playwright.sync_api import sync_playwright, Page, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    try:
        # Go to admin login page
        page.goto("http://localhost:8000/admin/index.php")

        # Fill in login credentials
        page.get_by_label("Username").fill("admin")
        page.get_by_label("Password").fill("password")

        # Click login button
        page.get_by_role("button", name="Sign in").click()

        # Wait for the dashboard to load by checking for the "Admin Dashboard" heading
        expect(page.get_by_role("heading", name="Admin Dashboard")).to_be_visible()

        # Go to manage orders page
        page.goto("http://localhost:8000/admin/manage_orders.php")

        # Click the first "View Details" link
        page.locator("a:has-text('View Details')").first.click()

        # Wait for the order detail page to load by checking for a unique element
        expect(page.get_by_role("heading", name=re.compile(r"Order Details for #\d+"))).to_be_visible()

        # Take a screenshot
        page.screenshot(path="jules-scratch/verification/admin_order_detail.png")

    finally:
        browser.close()

with sync_playwright() as playwright:
    run(playwright)
